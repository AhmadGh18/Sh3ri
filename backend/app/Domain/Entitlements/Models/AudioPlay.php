<?php

declare(strict_types=1);

namespace App\Domain\Entitlements\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioPlay extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'verse_uuid', 'played_at'];

    protected $casts = ['played_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
