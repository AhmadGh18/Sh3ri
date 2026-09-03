<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name_ar', 'name_en', 'iso_code'];

    public function poets(): HasMany
    {
        return $this->hasMany(Poet::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
