<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UpdateMeRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('roles');

        return UserResource::make($user);
    }

    public function update(UpdateMeRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // An email change means the account is now bound to a possibly-attacker
        // address; drop verification status until the new address is confirmed.
        // Also revoke every existing token — the attacker's token, if this is a
        // post-hijack pivot, dies here — and send a fresh verification link.
        $emailChanged = isset($data['email']) && strcasecmp((string) $data['email'], (string) $user->email) !== 0;

        $user->fill($data);
        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null]);
        }
        $user->save();

        if ($emailChanged) {
            $currentToken = $user->currentAccessToken();
            $currentTokenId = method_exists($currentToken, 'getKey') ? $currentToken->getKey() : null;
            // Revoke every OTHER token (keep this one so the client stays signed
            // in for the immediate confirmation flow).
            $user->tokens()->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))->delete();
            $user->sendEmailVerificationNotification();
        }

        return UserResource::make($user->load('roles'));
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(null, 204);
    }

    public function entitlements(Request $request)
    {
        $user = $request->user();

        // Open-access flag (project-entitlements-open-access memory): while
        // true, every authenticated user is granted every currently defined
        // product. Payment adapters (Apple/Google/Stripe) will write real
        // rows into the entitlements table later; when we flip this flag,
        // enforcement becomes real without touching any endpoint code.
        if (config('sh3ri.entitlements.open_access')) {
            return response()->json([
                'data' => [
                    [
                        'product_code' => 'premium',
                        'source' => 'open',
                        'status' => 'active',
                        'starts_at' => now()->toIso8601String(),
                        'ends_at' => null,
                    ],
                ],
                'meta' => ['open_access' => true],
            ]);
        }

        $entitlements = $user->entitlements()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get(['product_code', 'source', 'status', 'starts_at', 'ends_at']);

        return response()->json([
            'data' => $entitlements,
            'meta' => ['open_access' => false],
        ]);
    }
}
