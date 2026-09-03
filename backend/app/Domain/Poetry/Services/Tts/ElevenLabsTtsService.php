<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Tts;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final class ElevenLabsTtsService implements TtsProvider
{
    private const ENDPOINT = 'https://api.elevenlabs.io/v1/text-to-speech/';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $voiceId,
        private readonly string $modelId = 'eleven_multilingual_v2',
        private readonly float $stability = 0.5,
        private readonly float $similarityBoost = 0.75,
        private readonly float $style = 0.0,
        private readonly bool $useSpeakerBoost = true,
        private readonly int $timeoutSeconds = 30,
    ) {}

    /**
     * @param  string|null  $voiceOverride  Only honored by callers who verified
     *   the caller is authorized (dev preview tool). Ignored in production
     *   by convention — the controller gates that check.
     */
    public function synthesizeMp3(string $text, ?string $voiceOverride = null): string
    {
        // Config validation is deferred to call-time so DI never fails at
        // resolve-time (the controller's try/catch surfaces this as a
        // clean 503 to the client instead of a hard 500).
        if ($this->apiKey === '') {
            throw new TtsException(
                'ELEVENLABS_API_KEY is not set. Add it to .env and run: php artisan config:clear',
                statusHint: 503,
                errorKind: 'config',
            );
        }
        $voiceId = $voiceOverride !== null && $voiceOverride !== '' ? $voiceOverride : $this->voiceId;
        if ($voiceId === '') {
            throw new TtsException(
                'ELEVENLABS_VOICE_ID is not set. Pick a voice at elevenlabs.io/app/voice-library and paste its id into .env.',
                statusHint: 503,
                errorKind: 'config',
            );
        }

        $text = trim($text);
        if ($text === '') {
            throw new TtsException('Empty text passed to TTS.', statusHint: 400, errorKind: 'bad_input');
        }

        // Some Windows PHP installs ship without a CA bundle. Bundle one with
        // the project (storage/app/private/cacert.pem) and hand it to cURL if
        // it's there — otherwise trust the system store.
        $caPath = Storage::disk('local')->path('cacert.pem');
        $verify = is_file($caPath) ? $caPath : true;

        $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'accept' => 'audio/mpeg',
                'content-type' => 'application/json',
            ])
            ->withOptions(['verify' => $verify])
            ->timeout($this->timeoutSeconds)
            ->connectTimeout(5)
            ->retry(1, 500, throw: false)
            ->post(self::ENDPOINT . $voiceId, [
                'text' => $text,
                'model_id' => $this->modelId,
                'voice_settings' => [
                    'stability' => $this->stability,
                    'similarity_boost' => $this->similarityBoost,
                    'style' => $this->style,
                    'use_speaker_boost' => $this->useSpeakerBoost,
                ],
            ]);

        if (! $response->successful()) {
            // ElevenLabs returns JSON errors even when we asked for audio.
            $providerError = null;
            $ctype = $response->header('Content-Type') ?? '';
            $body = $response->body();
            if (str_contains(strtolower($ctype), 'json')) {
                $providerError = $body;
            } else {
                $providerError = 'HTTP ' . $response->status();
            }

            // Classify. ElevenLabs bundles quota_exceeded under 401 with a
            // JSON body carrying `detail.status === "quota_exceeded"`. Sniff
            // the body cheaply so callers can differentiate.
            $kind = match ($response->status()) {
                401, 403 => 'auth',
                429      => 'rate_limited',
                422, 400 => 'bad_input',
                default  => 'unavailable',
            };
            if (str_contains($providerError ?? '', 'quota_exceeded')) {
                $kind = 'quota_exceeded';
            }

            throw new TtsException(
                "ElevenLabs synthesis failed (HTTP {$response->status()})",
                statusHint: match ($response->status()) {
                    401, 403 => 503,          // hide provider auth errors from clients
                    422, 400 => 400,          // bad text (too long, banned content)
                    429      => 429,          // upstream rate limit → propagate
                    default  => 502,
                },
                providerError: $providerError,
                errorKind: $kind,
            );
        }

        $body = $response->body();

        // Sanity: verify we got MP3 bytes and not a stray JSON error body.
        // MP3 frames start with 0xFF 0xFB (or 0xFF 0xF3/0xFA), or an ID3 tag.
        if (strlen($body) < 128 || (! str_starts_with($body, "\xFF") && ! str_starts_with($body, 'ID3'))) {
            throw new TtsException(
                'ElevenLabs returned a body that does not look like MP3.',
                statusHint: 502,
                providerError: substr($body, 0, 300),
                errorKind: 'unavailable',
            );
        }

        return $body;
    }

    public function name(): string
    {
        return 'elevenlabs:' . $this->modelId . ':' . $this->voiceId;
    }
}
