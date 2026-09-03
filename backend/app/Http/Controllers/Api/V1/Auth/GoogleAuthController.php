<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Users\Actions\IssueApiTokenAction;
use App\Domain\Users\Services\GoogleIdTokenVerifier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Exchange a Google Identity Services ID token for a Sanctum PAT.
     *
     * Response shape matches /auth/login and /auth/register so the frontend
     * can treat them interchangeably.
     */
    public function __invoke(GoogleAuthRequest $request, IssueApiTokenAction $issueToken)
    {
        $clientId = (string) config('services.google.client_id', '');
        if ($clientId === '') {
            return response()->json([
                'error' => [
                    'type' => 'google_signin_disabled',
                    'message' => 'Google sign-in is not configured on this server.',
                    'trace_id' => bin2hex(random_bytes(6)),
                ],
            ], 503);
        }

        $verifier = new GoogleIdTokenVerifier($clientId);

        try {
            $claims = $verifier->verify((string) $request->validated()['id_token']);
        } catch (\Throwable $e) {
            // Never leak the underlying reason to clients — log it, respond generic.
            Log::warning('Google ID token verification failed', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => [
                    'type' => 'invalid_google_token',
                    'message' => 'Google sign-in failed. Please try again.',
                    'trace_id' => bin2hex(random_bytes(6)),
                ],
            ], 401);
        }

        $user = DB::transaction(function () use ($claims) {
            // Prefer an existing account keyed by google_id. Fall back to email
            // match (someone who signed up with password, now uses Google).
            $user = User::query()->where('google_id', $claims['sub'])->first()
                ?? User::query()->where('email', $claims['email'])->first();

            if ($user === null) {
                $user = User::create([
                    'name'  => $claims['name'] ?? $this->fallbackName($claims['email']),
                    'email' => $claims['email'],
                    // Random unusable password — user can set one later via
                    // /auth/forgot-password if they want a password login path.
                    'password' => Str::random(40),
                    'locale' => 'ar',
                ]);
                $user->refresh(); // pick up uuid default
                $user->assignRole('user');
            }

            // Bind the google_id + verified stamp. Also lift the avatar since
            // Google gives us a proper hosted URL for free.
            $dirty = false;
            if ($user->google_id !== $claims['sub'])   { $user->google_id = $claims['sub']; $dirty = true; }
            if ($user->email_verified_at === null)     { $user->email_verified_at = now(); $dirty = true; }
            if (! empty($claims['picture']) && $user->avatar_url !== $claims['picture']) {
                $user->avatar_url = $claims['picture']; $dirty = true;
            }
            if ($dirty) $user->save();

            return $user;
        });

        $token = $issueToken->execute($user, $request->validated()['device_name']);

        return response()->json([
            'data' => [
                'user' => UserResource::make($user->load('roles')),
                'token' => $token['plainTextToken'],
                'expires_at' => $token['expires_at']->toIso8601String(),
            ],
        ]);
    }

    private function fallbackName(string $email): string
    {
        return Str::of($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->toString();
    }
}
