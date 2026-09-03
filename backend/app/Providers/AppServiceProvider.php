<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Poetry\Models\UserPoem;
use App\Domain\Poetry\Services\Tts\ElevenLabsTtsService;
use App\Domain\Poetry\Services\Tts\TtsException;
use App\Domain\Poetry\Services\Tts\TtsProvider;
use App\Domain\Search\Contracts\SearchDriver;
use App\Domain\Search\Drivers\PostgresSearchDriver;
use App\Domain\Submissions\Models\Submission;
use App\Domain\Users\Policies\UserPoemPolicy;
use App\Domain\Submissions\Policies\SubmissionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchDriver::class, function ($app) {
            return match (config('sh3ri.search.driver', 'postgres')) {
                'postgres' => $app->make(PostgresSearchDriver::class),
                default => $app->make(PostgresSearchDriver::class),
            };
        });

        $this->app->bind(TtsProvider::class, function () {
            $provider = config('sh3ri.tts.provider', 'elevenlabs');
            if ($provider !== 'elevenlabs') {
                throw new TtsException("Unknown TTS provider: {$provider}", statusHint: 503);
            }
            $cfg = config('sh3ri.tts.elevenlabs');
            return new ElevenLabsTtsService(
                apiKey: (string) ($cfg['api_key'] ?? ''),
                voiceId: (string) ($cfg['voice_id'] ?? ''),
                modelId: (string) ($cfg['model_id'] ?? 'eleven_multilingual_v2'),
                stability: (float) ($cfg['stability'] ?? 0.5),
                similarityBoost: (float) ($cfg['similarity_boost'] ?? 0.75),
                style: (float) ($cfg['style'] ?? 0.0),
                useSpeakerBoost: (bool) ($cfg['use_speaker_boost'] ?? true),
                timeoutSeconds: (int) ($cfg['timeout_seconds'] ?? 30),
            );
        });
    }

    public function boot(): void
    {
        // Fail loudly on N+1 in non-production.
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::policy(UserPoem::class, UserPoemPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);

        // Central password policy — any FormRequest using Password::defaults()
        // gets this. `uncompromised()` hits HaveIBeenPwned's API; skip in
        // testing env so the suite doesn't need the network.
        Password::defaults(function () {
            // 9 chars minimum by product decision; still requires at least
            // one letter, one number, and one symbol so short passwords
            // can't be plain dictionary words.
            $rule = Password::min(9)->letters()->numbers()->symbols();
            return $this->app->environment('testing') ? $rule : $rule->uncompromised();
        });

        // Named limiters used across the /api/v1 surface.
        RateLimiter::for('api-public', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));
        RateLimiter::for('api-auth',   fn (Request $r) => Limit::perMinute(120)->by(optional($r->user())->id ?: $r->ip()));
        RateLimiter::for('auth',       fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('search',     fn (Request $r) => Limit::perMinute(30)->by(optional($r->user())->id ?: $r->ip()));
        RateLimiter::for('submissions', fn (Request $r) => Limit::perDay(10)->by(optional($r->user())->id ?: $r->ip()));
        // TTS: same-verse audio hits the disk cache and never touches the
        // upstream; new-verse synthesis calls ElevenLabs (~$0.003/verse).
        RateLimiter::for('audio', fn (Request $r) => Limit::perMinute(30)->by(optional($r->user())->id ?: $r->ip()));
    }
}
