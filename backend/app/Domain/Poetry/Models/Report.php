<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasFactory;

    // status / handled_by / handled_at are set only by moderator actions,
    // never via mass assignment from the user-facing StoreReportRequest.
    protected $fillable = [
        'user_id', 'reportable_type', 'reportable_id',
        'reason', 'description',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function target(): MorphTo
    {
        return $this->morphTo(name: 'reportable');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
