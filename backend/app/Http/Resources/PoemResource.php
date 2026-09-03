<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Poetry\Models\Poem */
class PoemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'language' => $this->language,
            'verse_count' => $this->verse_count,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toIso8601String(),
            'poet' => new PoetResource($this->whenLoaded('poet')),
            'era' => new EraResource($this->whenLoaded('era')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'meter' => new MeterResource($this->whenLoaded('meter')),
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'verses' => VerseResource::collection($this->whenLoaded('verses')),
        ];
    }
}
