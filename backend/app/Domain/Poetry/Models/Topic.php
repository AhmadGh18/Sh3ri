<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name_ar', 'name_en', 'color'];

    public function poems(): BelongsToMany
    {
        return $this->belongsToMany(Poem::class, 'poem_topic');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
