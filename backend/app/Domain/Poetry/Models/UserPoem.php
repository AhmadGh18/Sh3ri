<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use App\Enums\UserPoemStatus;
use App\Enums\UserPoemVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPoem extends Model
{
    use HasFactory;
    use SoftDeletes;

    // NB: `user_id`, `status`, `published_at` are deliberately excluded from
    // mass assignment. They must be set explicitly by the store/publish
    // actions in UserPoemController. Keeping them out means a future
    // ->fill($request->all()) refactor cannot transfer ownership, self-publish,
    // or backdate a poem.
    protected $fillable = [
        'title_ar', 'raw_text',
        'era_id', 'category_id', 'visibility',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'status' => UserPoemStatus::class,
        'visibility' => UserPoemVisibility::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function era(): BelongsTo
    {
        return $this->belongsTo(Era::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function upvotes(): HasMany
    {
        return $this->hasMany(UserPoemUpvote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(UserPoemComment::class)->orderByDesc('created_at');
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
