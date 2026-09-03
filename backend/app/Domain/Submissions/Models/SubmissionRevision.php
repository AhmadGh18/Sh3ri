<?php

declare(strict_types=1);

namespace App\Domain\Submissions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionRevision extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['submission_id', 'editor_id', 'diff', 'snapshot', 'note', 'created_at'];

    protected $casts = [
        'diff' => 'array',
        'snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
