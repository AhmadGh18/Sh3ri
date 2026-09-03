<?php

declare(strict_types=1);

namespace App\Domain\Submissions\Models;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'user_id', 'type', 'target_type', 'target_id',
        'payload', 'original_snapshot', 'status',
        'assigned_to', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'payload' => 'array',
        'original_snapshot' => 'array',
        'reviewed_at' => 'datetime',
        'type' => SubmissionType::class,
        'status' => SubmissionStatus::class,
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SubmissionRevision::class)->orderBy('created_at');
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
