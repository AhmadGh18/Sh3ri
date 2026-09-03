<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verse extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'poem_id', 'position', 'hemistich_a', 'hemistich_b'];

    protected $casts = ['position' => 'integer'];

    public function poem(): BelongsTo
    {
        return $this->belongsTo(Poem::class);
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
