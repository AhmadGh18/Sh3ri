<?php

declare(strict_types=1);

namespace App\Domain\Submissions\Policies;

use App\Domain\Submissions\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id || $user->can('submission.review');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function review(User $user, Submission $submission): bool
    {
        return $user->can('submission.review');
    }
}
