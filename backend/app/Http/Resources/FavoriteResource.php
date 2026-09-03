<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\FavoritableType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Poetry\Models\Favorite */
class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->favoritable_type instanceof FavoritableType
                ? $this->favoritable_type->value
                : (string) $this->favoritable_type,
            'poem' => $this->when(
                $this->favoritable_type === FavoritableType::Poem && $this->relationLoaded('poem'),
                fn () => PoemResource::make($this->poem),
            ),
            'verse' => $this->when(
                $this->favoritable_type === FavoritableType::Verse && $this->relationLoaded('verse'),
                fn () => VerseResource::make($this->verse),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
