<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Poetry\Models\Verse */
class VerseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'position' => $this->position,
            'hemistich_a' => $this->hemistich_a,
            'hemistich_b' => $this->hemistich_b,
            'poem' => new PoemResource($this->whenLoaded('poem')),
        ];
    }
}
