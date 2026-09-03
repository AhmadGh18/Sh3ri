<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Users\Actions\IssueApiTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Dummy bcrypt hash used to normalize response time when the email
     * doesn't exist. Same cost factor as production hashes so a call to
     * Hash::check runs for the same duration whether the user exists or not,
     * closing the response-time oracle that would otherwise let an attacker
     * enumerate registered emails.
     * (Generated once from Hash::make('') with the default cost.)
     */
    private const DUMMY_HASH = '$2y$12$SmYyftO7Q/x4RXFXCVFPluNaQtYX8wHplUuk8vXTBLbTLL9tuflNe';

    public function __invoke(LoginRequest $request, IssueApiTokenAction $issueToken)
    {
        $data = $request->validated();
        $user = User::query()->where('email', $data['email'])->first();

        // ALWAYS call Hash::check so both branches take ~50ms (bcrypt cost=12);
        // never leak "does this email exist?" via response time.
        $hashOk = Hash::check($data['password'], $user?->password ?? self::DUMMY_HASH);

        if (! $user || ! $hashOk) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $issueToken->execute($user, $data['device_name']);

        return response()->json([
            'data' => [
                'user' => UserResource::make($user->load('roles')),
                'token' => $token['plainTextToken'],
                'expires_at' => $token['expires_at']->toIso8601String(),
            ],
        ]);
    }
}
