<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    /** POST /api/v1/auth/email/verification-notification (auth required) */
    public function send(Request $request)
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['data' => ['message' => 'already_verified']]);
        }
        $user->sendEmailVerificationNotification();

        return response()->json(['data' => ['message' => 'verification_link_sent']]);
    }

    /** GET /api/v1/auth/email/verify/{id}/{hash}?expires=&signature= (signed URL) */
    public function verify(Request $request, string $id, string $hash)
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['error' => ['type' => 'invalid_signature', 'message' => 'Invalid or expired link.']], 403);
        }

        $user = User::findOrFail($id);
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['error' => ['type' => 'invalid_hash', 'message' => 'Invalid verification link.']], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            Event::dispatch(new Verified($user));
        }

        return response()->json(['data' => ['message' => 'email_verified']]);
    }
}
