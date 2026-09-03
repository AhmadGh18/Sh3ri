<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Poetry\Models\Meter */
class MeterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'pattern' => $this->pattern,
            'family' => $this->family,
        ];
    }
}
