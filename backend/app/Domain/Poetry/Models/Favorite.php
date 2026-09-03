<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use App\Enums\FavoritableType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'favoritable_type', 'favoritable_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'favoritable_type' => FavoritableType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function poem(): BelongsTo
    {
        return $this->belongsTo(Poem::class, 'favoritable_id')
            ->where('favorites.favoritable_type', FavoritableType::Poem->value);
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'favoritable_id')
            ->where('favorites.favoritable_type', FavoritableType::Verse->value);
    }
}
