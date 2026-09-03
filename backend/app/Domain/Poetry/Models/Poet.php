<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poet extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'slug', 'name_ar', 'name_en', 'bio_ar', 'bio_en',
        'era_id', 'country_id', 'birth_year', 'death_year', 'is_contested',
        'image_url', 'source', 'source_external_id', 'import_meta',
    ];

    protected $casts = [
        'birth_year' => 'integer',
        'death_year' => 'integer',
        'is_contested' => 'boolean',
        'import_meta' => 'array',
    ];

    // name_normalized / search_tsv are Postgres-generated; keep readable but not writable
    protected $guarded_generated = ['name_normalized', 'search_tsv'];

    public function era(): BelongsTo
    {
        return $this->belongsTo(Era::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function poems(): HasMany
    {
        return $this->hasMany(Poem::class);
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
