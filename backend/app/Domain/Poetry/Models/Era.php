<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Era extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name_ar', 'name_en', 'start_year', 'end_year', 'display_order'];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
        'display_order' => 'integer',
    ];

    public function poets(): HasMany
    {
        return $this->hasMany(Poet::class);
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
