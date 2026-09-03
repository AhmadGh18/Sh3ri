<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meter extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name_ar', 'name_en', 'pattern', 'family'];

    public function poems(): HasMany
    {
        return $this->hasMany(Poem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
