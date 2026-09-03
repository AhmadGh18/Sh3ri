<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Users\Actions\IssueApiTokenAction;
use App\Domain\Users\Actions\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;

class RegisterController extends Controller
{
    public function __invoke(
        RegisterRequest $request,
        RegisterUserAction $register,
        IssueApiTokenAction $issueToken,
    ) {
        $data = $request->validated();
        $user = $register->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => $data['locale'] ?? 'ar',
        ]);

        $token = $issueToken->execute($user, $data['device_name']);

        return response()->json([
            'data' => [
                'user' => UserResource::make($user->load('roles')),
                'token' => $token['plainTextToken'],
                'expires_at' => $token['expires_at']->toIso8601String(),
            ],
        ], 201);
    }
}
