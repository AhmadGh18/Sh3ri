<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Entitlements\Services\AudioQuotaService;
use App\Domain\Poetry\Models\Verse;
use App\Domain\Poetry\Services\Tts\TtsException;
use App\Domain\Poetry\Services\Tts\TtsProvider;
use App\Enums\PoemStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves an MP3 rendering of a single verse.
 *
 * First call for a given verse UUID synthesizes via ElevenLabs and caches
 * the MP3 to disk. Subsequent calls stream from disk with a strong ETag +
 * long-lived Cache-Control. The verse text is immutable per UUID, so the
 * MP3 is safe to treat as immutable too — invalidation only happens if
 * an admin edits the verse (they can then delete the file directly).
 */
class VerseAudioController extends Controller
{
    public function show(Request $request, Verse $verse, TtsProvider $tts, AudioQuotaService $quota)
    {
        // Never synthesize audio for a verse whose parent poem is hidden /
        // quarantined / deleted. Matches the visibility of VerseController.
        $verse->loadMissing('poem');
        abort_unless(
            $verse->poem !== null && $verse->poem->status === PoemStatus::Published,
            404
        );

        // Per-user daily audio quota. Check BEFORE synth so we don't burn
        // ElevenLabs credits on a request we're about to refuse. Cache hits
        // also count — a "play" is a play regardless of provider cost, and
        // that keeps the meter honest and predictable for the user.
        $user = $request->user();
        $guestKey = $user ? null : (($request->cookie('sh3ri_guest') ?: '') . '|' . $request->ip());
        if (! $quota->canPlay($user, $guestKey)) {
            $summary = $quota->summary($user, $guestKey);
            return response()->json([
                'error' => [
                    'type'    => 'audio_quota_exceeded',
                    'message' => 'استنفدت حصّة الاستماع اليومية. طوّر خطتك لمتابعة الاستماع.',
                    'plan'    => $summary['plan'],
                    'used_today' => $summary['used_today'],
                    'trace_id' => bin2hex(random_bytes(6)),
                ],
            ], 402);
        }

        $disk = config('sh3ri.tts.cache_disk', 'local');
        $path = trim((string) config('sh3ri.tts.cache_path', 'verse_audio'), '/');

        // Dev-only voice override: pick any voice_id without touching .env.
        // Cache keys include the voice so different voices don't collide.
        // Validated to ElevenLabs' 20-char alphanumeric id format so we can
        // never inject arbitrary characters into the outbound URL.
        $voiceOverride = null;
        if (app()->environment('local', 'testing') && $request->filled('voice_id')) {
            $raw = (string) $request->query('voice_id');
            if (! preg_match('/^[A-Za-z0-9]{16,40}$/', $raw)) {
                return response()->json([
                    'error' => [
                        'type' => 'invalid_voice_id',
                        'message' => 'voice_id must be 16-40 alphanumeric characters.',
                        'trace_id' => bin2hex(random_bytes(6)),
                    ],
                ], 422);
            }
            $voiceOverride = $raw;
        }

        $file = $voiceOverride !== null
            ? $path . '/' . $verse->uuid . '.' . substr(sha1($voiceOverride), 0, 10) . '.mp3'
            : $path . '/' . $verse->uuid . '.mp3';

        $storage = Storage::disk($disk);

        // Serve from cache if present.
        if (! $storage->exists($file)) {
            $text = $this->composeText($verse);

            // Provider circuit-breaker: if we hit a quota/auth error recently,
            // fast-fail without touching ElevenLabs so a poem with 30 verses
            // doesn't spam 30 doomed API calls in as many seconds. Auto-clears
            // after the TTL — user rotates their key and normal traffic resumes.
            $breakerKey = 'sh3ri:tts:breaker:' . $tts->name();
            if ($tripped = Cache::get($breakerKey)) {
                return response()->json([
                    'error' => [
                        'type' => 'tts_provider_' . ($tripped['kind'] ?? 'unavailable'),
                        'message' => $tripped['message'] ?? 'خدمة الصوت غير متاحة مؤقتًا.',
                        'trace_id' => bin2hex(random_bytes(6)),
                    ],
                ], 503);
            }

            // Global daily character-budget circuit-breaker. Cache-hit requests
            // pass right through this branch — only genuine cache-miss
            // synthesis calls consume ElevenLabs credits and are counted.
            $budget = (int) config('sh3ri.tts.daily_char_budget', 0);
            if ($budget > 0) {
                $key = 'sh3ri:tts:chars:' . gmdate('Y-m-d');
                $used = (int) Cache::get($key, 0);
                if ($used + mb_strlen($text) > $budget) {
                    Log::warning('TTS daily budget reached', [
                        'used' => $used, 'budget' => $budget, 'verse' => $verse->uuid,
                    ]);
                    return response()->json([
                        'error' => [
                            'type' => 'tts_budget_reached',
                            'message' => 'Daily TTS budget reached. Try again after midnight UTC.',
                            'trace_id' => bin2hex(random_bytes(6)),
                        ],
                    ], 503);
                }
                Cache::add($key, 0, 86400);
                Cache::increment($key, mb_strlen($text));
            }

            try {
                $mp3 = $tts->synthesizeMp3($text, $voiceOverride);
            } catch (TtsException $e) {
                Log::warning('TTS synthesis failed', [
                    'verse' => $verse->uuid,
                    'kind' => $e->errorKind,
                    'message' => $e->getMessage(),
                    'provider_error' => $e->providerError,
                ]);

                // Trip the breaker on account-level failures so the next 29
                // verse requests don't repeat the mistake. Auth/config errors
                // linger longer because the fix requires human action.
                $ttl = match ($e->errorKind) {
                    'quota_exceeded' => 900,   // 15 min
                    'auth', 'config' => 900,   // same — key needs replacing
                    'rate_limited'   => 60,    // 1 min, transient
                    default          => null,  // don't trip on random 5xx
                };
                if ($ttl !== null) {
                    Cache::put($breakerKey, [
                        'kind' => $e->errorKind,
                        'message' => $this->userMessage($e->errorKind, $e->getMessage()),
                        'until' => now()->addSeconds($ttl)->toIso8601String(),
                    ], $ttl);
                }

                return response()->json([
                    'error' => [
                        'type' => 'tts_' . $e->errorKind,
                        'message' => $this->userMessage($e->errorKind, $e->getMessage()),
                        'trace_id' => bin2hex(random_bytes(6)),
                    ],
                ], $e->statusHint);
            }
            $storage->put($file, $mp3);
        }

        $absolutePath = $storage->path($file);
        $size = filesize($absolutePath);
        $etag = '"' . md5_file($absolutePath) . '"';

        // Conditional GET — save the wire. NOTE: we do NOT charge the meter
        // for 304 responses; the client already has the audio and is just
        // revalidating, which shouldn't count as a fresh listen.
        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        // Register the play. Do it right before returning bytes so we never
        // over-count a request that failed synthesis (that path returned
        // above with 5xx/402 before reaching here).
        $quota->record($user, (string) $verse->uuid, $guestKey);
        $summary = $quota->summary($user, $guestKey);

        return new StreamedResponse(function () use ($absolutePath) {
            $fh = fopen($absolutePath, 'rb');
            fpassthru($fh);
            fclose($fh);
        }, 200, [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => (string) $size,
            'ETag' => $etag,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Accept-Ranges' => 'none',
            'X-Content-Type-Options' => 'nosniff',
            'X-Audio-Plan' => $summary['plan']['code'],
            'X-Audio-Used-Today' => (string) $summary['used_today'],
            'X-Audio-Remaining' => $summary['remaining'] === null ? 'unlimited' : (string) $summary['remaining'],
        ]);
    }

    private function composeText(Verse $verse): string
    {
        // Two hemistiches → single utterance with a brief pause between.
        // ElevenLabs treats "…" as a short prosodic break in most models.
        $a = trim((string) $verse->hemistich_a);
        $b = trim((string) ($verse->hemistich_b ?? ''));
        return $b === '' ? $a : $a . ' … ' . $b;
    }

    /** Arabic-facing message for a TTS failure kind. */
    private function userMessage(string $kind, string $fallback): string
    {
        return match ($kind) {
            'quota_exceeded' => 'انتهت حصّة الصوت لدى مزوّد الخدمة. سيعود الاستماع لاحقًا.',
            'auth', 'config' => 'خدمة الصوت غير مهيّأة على الخادم حاليًا.',
            'rate_limited'   => 'ضغط على خدمة الصوت مرتفع الآن — جرّب بعد قليل.',
            'bad_input'      => 'تعذّر تحويل هذا البيت إلى صوت.',
            default          => $fallback,
        };
    }
}
