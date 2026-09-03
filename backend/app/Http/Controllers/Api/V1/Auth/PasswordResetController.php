<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request)
    {
        // Always return the same shape/status to avoid email enumeration.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'data' => ['message' => 'If an account exists for that email, a reset link has been sent.'],
        ]);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete(); // sign out on all devices after reset
                Event::dispatch(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'error' => [
                    'type' => 'invalid_reset_token',
                    'message' => __($status),
                ],
            ], 422);
        }

        return response()->json([
            'data' => ['message' => __($status)],
        ]);
    }
}
