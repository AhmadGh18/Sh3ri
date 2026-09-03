<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Import;

/**
 * Single source of truth for source-CSV → domain-field mapping.
 *
 * When the real Kaggle dataset is downloaded and the actual column names
 * are known, edit ONLY the COLUMNS constant. Every other importer class
 * consumes fields through this map — no other file should reference
 * source column names directly.
 *
 * Each key on the right side (the domain field) is stable.
 * The value on the right is the canonical source column name;
 * `aliases` lists any tolerated alternatives.
 */
final class CsvColumnMap
{
    /**
     * Kaggle `ahmedabelal/arabic-poetry` columns (verified 2026-08-17
     * against a real download of the CSV — 54,944 rows, 5 columns).
     *
     * NB: `category` in this source is not a genre — it is either an era
     * name ("العصر العباسي") for classical poets or a country name
     * ("مصر", "لبنان", …) for modern poets. PoetryImporter routes it
     * through TaxonomyResolver against both eras and countries.
     *
     * Other logical fields (poet_bio, poet_era, poet_country, poem_meter)
     * have no source column and stay null on import; they can be enriched
     * later via admin edits or a second data source.
     *
     * @var array<string, array{primary: string, aliases: list<string>}>
     */
    public const COLUMNS = [
        'poet_name'     => ['primary' => 'poet_name',    'aliases' => ['poet', 'author', 'شاعر', 'الشاعر']],
        'poem_title'    => ['primary' => 'poem_title',   'aliases' => ['title', 'العنوان', 'اسم_القصيدة']],
        'poem_text'     => ['primary' => 'poem_text',    'aliases' => ['text', 'body', 'poem', 'content', 'النص']],
        'category'      => ['primary' => 'category',     'aliases' => ['poem_category', 'era', 'age', 'country', 'العصر', 'البلد']],
        'source_id'     => ['primary' => 'id',           'aliases' => ['poem_id', 'row_id']],
        // The following logical fields have no source column in ahmedabelal;
        // resolve() will return null for them, project() will emit null.
        'poet_bio'      => ['primary' => 'poet_bio',     'aliases' => ['bio', 'description', 'poet_description']],
        'poem_meter'    => ['primary' => 'poem_meter',   'aliases' => ['meter', 'bahr', 'البحر']],
    ];

    /**
     * Resolve the actual column key present in $header for our logical field.
     * Returns null if the CSV has no column matching this field.
     *
     * @param  list<string>  $header
     */
    public static function resolve(string $field, array $header): ?string
    {
        if (! isset(self::COLUMNS[$field])) {
            return null;
        }

        $normalizedHeader = array_map(static fn (string $h): string => mb_strtolower(trim($h)), $header);

        $spec = self::COLUMNS[$field];
        $candidates = array_merge([$spec['primary']], $spec['aliases']);

        foreach ($candidates as $cand) {
            $needle = mb_strtolower(trim($cand));
            $idx = array_search($needle, $normalizedHeader, true);
            if ($idx !== false) {
                return $header[$idx];
            }
        }

        return null;
    }

    /**
     * Build a projection of a source row into our stable field names.
     * Missing source columns yield null values.
     *
     * @param  array<string, scalar|null>  $row
     * @param  array<string, string|null>  $resolved  Field => actual CSV column
     * @return array<string, string|null>
     */
    public static function project(array $row, array $resolved): array
    {
        $out = [];
        foreach (array_keys(self::COLUMNS) as $field) {
            $col = $resolved[$field] ?? null;
            $out[$field] = ($col !== null && array_key_exists($col, $row)) ? $row[$col] : null;
            if (is_string($out[$field])) {
                $out[$field] = trim($out[$field]);
                if ($out[$field] === '') {
                    $out[$field] = null;
                }
            }
        }

        return $out;
    }

    /**
     * Resolve every field against a header row.
     *
     * @param  list<string>  $header
     * @return array<string, string|null>
     */
    public static function resolveAll(array $header): array
    {
        $out = [];
        foreach (array_keys(self::COLUMNS) as $field) {
            $out[$field] = self::resolve($field, $header);
        }

        return $out;
    }
}
