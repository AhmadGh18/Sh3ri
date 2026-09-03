<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Poetry\Models\Poet */
class PoetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'bio_ar' => $this->when($this->bio_ar !== null, $this->bio_ar),
            'bio_en' => $this->when($this->bio_en !== null, $this->bio_en),
            'birth_year' => $this->birth_year,
            'death_year' => $this->death_year,
            'is_contested' => $this->is_contested,
            'image_url' => $this->image_url,
            'era' => new EraResource($this->whenLoaded('era')),
            'country' => new CountryResource($this->whenLoaded('country')),
            'poem_count' => $this->whenCounted('poems'),
        ];
    }
}
