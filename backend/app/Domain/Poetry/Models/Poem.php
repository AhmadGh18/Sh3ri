<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use App\Enums\PoemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'slug', 'poet_id', 'era_id', 'category_id', 'meter_id',
        'title_ar', 'title_en', 'language', 'verse_count',
        'status', 'published_at', 'raw_source_text',
        'source', 'source_external_id', 'import_meta',
    ];

    protected $casts = [
        'verse_count' => 'integer',
        'published_at' => 'datetime',
        'import_meta' => 'array',
        'status' => PoemStatus::class,
    ];

    public function poet(): BelongsTo
    {
        return $this->belongsTo(Poet::class);
    }

    public function era(): BelongsTo
    {
        return $this->belongsTo(Era::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'poem_topic');
    }

    public function verses(): HasMany
    {
        return $this->hasMany(Verse::class)->orderBy('position');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
