<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Poetry\Models\UserPoem */
class UserPoemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title_ar' => $this->title_ar,
            'raw_text' => $this->raw_text,
            'status' => $this->status->value,
            'visibility' => $this->visibility->value,
            'era' => new EraResource($this->whenLoaded('era')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            // Author is only exposed when the `user` relation is explicitly
            // loaded — the /me/* endpoints don't need it (owner is implicit),
            // but the public /community endpoints do.
            'author' => $this->when(
                $this->relationLoaded('user') && $this->user,
                fn () => ['uuid' => $this->user->uuid, 'name' => $this->user->name],
            ),
            // Community engagement counts are always safe to expose publicly;
            // populated via ->withCount(['upvotes','comments']) in the query.
            'upvote_count'  => $this->when($this->upvotes_count  !== null, fn () => (int) $this->upvotes_count),
            'comment_count' => $this->when($this->comments_count !== null, fn () => (int) $this->comments_count),
            // Whether the current viewer has upvoted this poem — only set by
            // the controller when it can compute it (i.e. request is authed).
            'upvoted_by_me' => $this->when(
                property_exists($this, 'upvotedByMe') || isset($this->attributes['upvoted_by_me']),
                fn () => (bool) ($this->attributes['upvoted_by_me'] ?? false),
            ),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
