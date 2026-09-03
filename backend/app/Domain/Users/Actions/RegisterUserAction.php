<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class RegisterUserAction
{
    /** @param  array{name: string, email: string, password: string, locale?: string}  $data */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'locale' => $data['locale'] ?? 'ar',
            ]);

            // Re-hydrate DB-generated columns (uuid via uuid_generate_v4()).
            $user->refresh();

            $user->assignRole('user');

            Event::dispatch(new Registered($user));

            return $user;
        });
    }
}
