<?php

declare(strict_types=1);

namespace App\Domain\Users\Policies;

use App\Domain\Poetry\Models\UserPoem;
use App\Models\User;

class UserPoemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserPoem $poem): bool
    {
        // Owner can always view their own; others only if public + published.
        if ($user->id === $poem->user_id) {
            return true;
        }
        return $poem->status->value === 'published' && $poem->visibility->value === 'public';
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserPoem $poem): bool
    {
        return $user->id === $poem->user_id;
    }

    public function delete(User $user, UserPoem $poem): bool
    {
        return $user->id === $poem->user_id;
    }
}
