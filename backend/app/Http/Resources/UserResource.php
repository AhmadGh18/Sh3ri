<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'avatar_url' => $this->avatar_url,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'has_google' => $this->google_id !== null,
            'roles' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->roles->pluck('name')->all(),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
