<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Config;

/**
 * Mints a Sanctum personal access token for one device/session.
 *
 * Token abilities carry the user's role names so downstream middleware
 * can gate admin endpoints via `abilities:admin` without hitting the DB.
 * Token expiry is sliding: SANCTUM_TOKEN_EXPIRATION minutes from now,
 * refreshed on each authenticated request (extended in an event listener).
 */
final class IssueApiTokenAction
{
    /** @return array{plainTextToken: string, expires_at: \Illuminate\Support\Carbon} */
    public function execute(User $user, string $deviceName): array
    {
        // Tokens carry a single generic `access` ability. Role-based checks
        // (admin/moderator) go through `can:` middleware which reads roles
        // LIVE from the DB, so revoking a role in the admin UI takes effect
        // immediately for existing tokens — no stale abilities baked in.
        $abilities = ['access'];

        $expiresAt = now()->addMinutes((int) Config::get('sanctum.expiration') ?: 20160);

        $token = $user->createToken(
            name: $deviceName,
            abilities: $abilities,
            expiresAt: $expiresAt,
        );

        return [
            'plainTextToken' => $token->plainTextToken,
            'expires_at' => $expiresAt,
        ];
    }
}
