<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name_ar', 'name_en', 'parent_id', 'display_order'];

    protected $casts = [
        'parent_id' => 'integer',
        'display_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function poems(): HasMany
    {
        return $this->hasMany(Poem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
