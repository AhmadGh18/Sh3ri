<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Poetry\Models\UserPoemComment */
class UserPoemCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'body' => $this->body,
            'author' => $this->when(
                $this->relationLoaded('author') && $this->author,
                fn () => ['uuid' => $this->author->uuid, 'name' => $this->author->name],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            // Only the author (or a moderator) can delete — surface this so
            // the frontend can render a trash icon without a probe request.
            'can_delete' => $this->when(
                $request->user() !== null,
                fn () => $request->user()?->id === $this->user_id
                    || $request->user()?->can('submission.review'),
            ),
        ];
    }
}
