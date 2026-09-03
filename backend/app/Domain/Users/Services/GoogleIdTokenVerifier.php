<?php

declare(strict_types=1);

namespace App\Domain\Users\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Verifies a Google Identity Services (GSI) ID token client-side users
 * present to us. We fetch Google's rotating public keys (JWKS), pick the
 * one whose `kid` matches the token header, cryptographically verify the
 * signature via firebase/php-jwt, then check standard OIDC claims.
 *
 * No client_secret. No OAuth redirect. No round-trip to Google beyond the
 * one-hour-cached JWKS fetch. This is what modern SPA + Bearer stacks do.
 */
final class GoogleIdTokenVerifier
{
    private const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];
    private const CACHE_KEY = 'sh3ri:google:jwks';
    private const CACHE_TTL = 3600; // 1h — Google rotates keys, but rarely faster.

    public function __construct(private readonly string $expectedAudience) {}

    /**
     * @return array{sub: string, email: string, email_verified: bool, name: ?string, picture: ?string}
     * @throws RuntimeException on any verification failure. Message is intended
     *   for logs, not clients — the controller returns a generic 401 on catch.
     */
    public function verify(string $idToken): array
    {
        if ($this->expectedAudience === '') {
            throw new RuntimeException('GOOGLE_CLIENT_ID is not configured on this server.');
        }

        $jwks = $this->jwks();
        $keys = JWK::parseKeySet($jwks);

        try {
            $decoded = (array) JWT::decode($idToken, $keys);
        } catch (\Throwable $e) {
            throw new RuntimeException('Google ID token signature invalid: ' . $e->getMessage(), previous: $e);
        }

        // Standard OIDC claims.
        if (! in_array($decoded['iss'] ?? '', self::ISSUERS, true)) {
            throw new RuntimeException('Google ID token has unexpected issuer.');
        }
        if (($decoded['aud'] ?? '') !== $this->expectedAudience) {
            throw new RuntimeException('Google ID token audience mismatch (wrong client_id).');
        }
        if (empty($decoded['sub'])) {
            throw new RuntimeException('Google ID token missing sub claim.');
        }
        if (empty($decoded['email'])) {
            throw new RuntimeException('Google ID token missing email claim.');
        }
        if (($decoded['email_verified'] ?? false) !== true && ($decoded['email_verified'] ?? '') !== 'true') {
            throw new RuntimeException('Google reports email as not verified.');
        }
        if ((int) ($decoded['exp'] ?? 0) < time()) {
            throw new RuntimeException('Google ID token expired.');
        }

        return [
            'sub' => (string) $decoded['sub'],
            'email' => (string) $decoded['email'],
            'email_verified' => true,
            'name' => isset($decoded['name']) ? (string) $decoded['name'] : null,
            'picture' => isset($decoded['picture']) ? (string) $decoded['picture'] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function jwks(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            // Reuse the CA bundle we ship for ElevenLabs; some Windows PHP
            // installs have no system trust store.
            $caPath = Storage::disk('local')->path('cacert.pem');
            $verify = is_file($caPath) ? $caPath : true;

            $response = Http::withOptions(['verify' => $verify])
                ->timeout(10)
                ->get(self::JWKS_URL);

            if (! $response->successful()) {
                throw new RuntimeException('Failed to fetch Google JWKS: HTTP ' . $response->status());
            }
            $body = $response->json();
            if (! is_array($body) || empty($body['keys'])) {
                throw new RuntimeException('Google JWKS response malformed.');
            }
            return $body;
        });
    }
}
