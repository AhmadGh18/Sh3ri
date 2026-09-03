<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPoemUpvote extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_poem_id', 'user_id', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function poem(): BelongsTo
    {
        return $this->belongsTo(UserPoem::class, 'user_poem_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
