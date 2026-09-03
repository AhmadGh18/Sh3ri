<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPoemComment extends Model
{
    use HasFactory;
    use SoftDeletes;

    // NB: user_id is never mass-assignable — always set explicitly.
    protected $fillable = ['body'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function poem(): BelongsTo
    {
        return $this->belongsTo(UserPoem::class, 'user_poem_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
