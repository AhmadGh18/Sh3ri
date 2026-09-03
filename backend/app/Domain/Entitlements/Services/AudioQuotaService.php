<?php

declare(strict_types=1);

namespace App\Domain\Entitlements\Services;

use App\Domain\Entitlements\Models\AudioPlay;
use App\Domain\Entitlements\Models\Entitlement;
use App\Domain\Entitlements\Models\Plan;
use App\Enums\EntitlementStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Central authority for "can this listener play one more verse today?".
 *
 * Authenticated users are metered against a DB row-count in `audio_plays`.
 * Guests are metered by IP via the cache — cheap and works without a
 * schema. Both share the same shape of `remaining()` / `record()` so the
 * controller only needs one code path.
 */
final class AudioQuotaService
{
    /** Guest tier fallback when no `guest` plan exists in the DB. */
    private const GUEST_DEFAULT_LIMIT = 3;

    public function currentPlan(?User $user): Plan
    {
        if ($user === null) {
            return $this->fetchPlan('guest') ?? $this->syntheticGuestPlan();
        }

        // Find the highest-tier active entitlement, then resolve to its Plan.
        // The `product_code` on entitlement rows equals `plans.code` — that's
        // the whole point of the shared vocabulary.
        $codes = Entitlement::query()
            ->where('user_id', $user->id)
            ->where('status', EntitlementStatus::Active)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->pluck('product_code')
            ->all();

        if (! empty($codes)) {
            $best = Plan::query()->whereIn('code', $codes)->orderByDesc('sort')->first();
            if ($best) return $best;
        }

        return $this->fetchPlan('free') ?? $this->syntheticFreePlan();
    }

    /**
     * How many plays remain in today's window. Null means unlimited.
     */
    public function remaining(?User $user, ?string $guestKey = null): ?int
    {
        $plan = $this->currentPlan($user);
        if ($plan->isUnlimited()) return null;

        $limit = (int) $plan->daily_audio_plays;
        return max(0, $limit - $this->usedToday($user, $guestKey));
    }

    public function canPlay(?User $user, ?string $guestKey = null): bool
    {
        $r = $this->remaining($user, $guestKey);
        return $r === null || $r > 0;
    }

    /**
     * Register one play. Idempotent-ish for a small window: if we already
     * charged this user for the same verse in the last 60 seconds (e.g. the
     * client resubscribed after a network blip), do NOT double-count.
     */
    public function record(?User $user, string $verseUuid, ?string $guestKey = null): void
    {
        if ($user) {
            $exists = AudioPlay::query()
                ->where('user_id', $user->id)
                ->where('verse_uuid', $verseUuid)
                ->where('played_at', '>=', now()->subMinute())
                ->exists();
            if ($exists) return;

            AudioPlay::create([
                'user_id'    => $user->id,
                'verse_uuid' => $verseUuid,
                'played_at'  => now(),
            ]);
            return;
        }

        if ($guestKey) {
            $key = $this->guestCacheKey($guestKey);
            Cache::add($key, 0, $this->secondsUntilMidnight());
            Cache::increment($key);
        }
    }

    /**
     * Number of plays consumed today. UTC day boundary — matches the
     * character-budget circuit-breaker so we don't drift.
     */
    public function usedToday(?User $user, ?string $guestKey = null): int
    {
        if ($user) {
            return (int) AudioPlay::query()
                ->where('user_id', $user->id)
                ->where('played_at', '>=', CarbonImmutable::now()->startOfDay())
                ->count();
        }
        if ($guestKey) {
            return (int) Cache::get($this->guestCacheKey($guestKey), 0);
        }
        return 0;
    }

    public function summary(?User $user, ?string $guestKey = null): array
    {
        $plan = $this->currentPlan($user);
        return [
            'plan' => [
                'code'          => $plan->code,
                'name_ar'       => $plan->name_ar,
                'name_en'       => $plan->name_en,
                'is_unlimited'  => $plan->isUnlimited(),
                'daily_limit'   => $plan->daily_audio_plays,   // null = unlimited
            ],
            'used_today' => $this->usedToday($user, $guestKey),
            'remaining'  => $this->remaining($user, $guestKey), // null = unlimited
        ];
    }

    // ---------- internals ----------

    private function fetchPlan(string $code): ?Plan
    {
        return Cache::remember("plan:$code", 300, fn () => Plan::where('code', $code)->first());
    }

    private function syntheticGuestPlan(): Plan
    {
        return new Plan([
            'code' => 'guest', 'name_ar' => 'زائر', 'name_en' => 'Guest',
            'price_cents' => 0, 'currency' => 'USD',
            'daily_audio_plays' => self::GUEST_DEFAULT_LIMIT,
            'allow_download' => false, 'is_public' => false, 'sort' => 0,
        ]);
    }

    private function syntheticFreePlan(): Plan
    {
        return new Plan([
            'code' => 'free', 'name_ar' => 'مجاني', 'name_en' => 'Free',
            'price_cents' => 0, 'currency' => 'USD',
            'daily_audio_plays' => 20,
            'allow_download' => false, 'is_public' => true, 'sort' => 1,
        ]);
    }

    private function guestCacheKey(string $guestKey): string
    {
        return 'sh3ri:audio-quota:guest:' . sha1($guestKey) . ':' . gmdate('Y-m-d');
    }

    private function secondsUntilMidnight(): int
    {
        return CarbonImmutable::now()->addDay()->startOfDay()->diffInSeconds(CarbonImmutable::now(), false) * -1;
    }
}
