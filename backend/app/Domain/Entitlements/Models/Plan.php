<?php

declare(strict_types=1);

namespace App\Domain\Entitlements\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code', 'name_ar', 'name_en', 'tagline_ar',
        'price_cents', 'currency',
        'daily_audio_plays', 'allow_download',
        'is_public', 'sort',
    ];

    protected $casts = [
        'price_cents'       => 'integer',
        'daily_audio_plays' => 'integer',   // nullable in the schema — null = unlimited
        'allow_download'    => 'boolean',
        'is_public'         => 'boolean',
        'sort'              => 'integer',
    ];

    public function isUnlimited(): bool
    {
        return $this->daily_audio_plays === null;
    }

    public function isFree(): bool
    {
        return $this->price_cents === 0;
    }
}
