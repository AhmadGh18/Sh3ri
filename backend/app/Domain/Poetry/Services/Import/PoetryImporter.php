<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Import;

use App\Domain\Poetry\Models\Poem;
use App\Domain\Poetry\Models\Poet;
use App\Domain\Poetry\Support\ArabicNormalizer;
use App\Domain\Poetry\Support\SlugGenerator;
use App\Enums\PoemStatus;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;

/**
 * Orchestrates the Kaggle → Postgres pipeline.
 *
 * Pipeline per row:
 *   read → project (CsvColumnMap) → validate → normalize → dedupe → upsert → verses
 *
 * Idempotency:
 *   Poet   uniqueness = normalize_arabic(name_ar) [within same source]
 *   Poem   uniqueness = (source, source_external_id) if present,
 *                       else (poet_id, normalize_arabic(title), verse_count)
 *   Verses = replaced iff the joined normalized text hash changed
 */
final class PoetryImporter
{
    /** Rows past this era's cutoff are quarantined by default (copyright caution). */
    private const MODERN_ERA_CUTOFF_YEAR = 1950;

    public function __construct(
        private readonly ArabicNormalizer $normalizer,
        private readonly VerseSplitter $verseSplitter,
        private readonly PoetryRowValidator $validator,
        private readonly TaxonomyResolver $taxonomy,
        private readonly SlugGenerator $slugs,
    ) {}

    /**
     * @param  callable(int, string):void|null  $onProgress  called every 100 rows
     */
    public function importCsv(
        string $filePath,
        string $sourceName,
        bool $dryRun = false,
        bool $quarantineModern = true,
        ?int $limit = null,
        ?callable $onProgress = null,
    ): ImportReport {
        $report = new ImportReport();

        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setHeaderOffset(0);
        $header = $reader->getHeader();
        $resolved = CsvColumnMap::resolveAll($header);

        $statement = Statement::create();
        if ($limit !== null) {
            $statement = $statement->limit($limit);
        }

        $rows = $statement->process($reader);

        $rowIndex = 0;
        foreach ($rows as $row) {
            $rowIndex++;
            $report->rowRead();

            $projected = CsvColumnMap::project($row, $resolved);

            $reason = $this->validator->validate($projected);
            if ($reason !== null) {
                $report->rejected($rowIndex, $reason, (string) ($projected['poem_title'] ?? ''));
                $onProgress && $rowIndex % 100 === 0 && $onProgress($rowIndex, "rejected: {$reason}");
                continue;
            }

            if ($dryRun) {
                // Count what would happen; touch nothing.
                $report->imported_poets += 1;
                $report->imported_poems += 1;
                $report->imported_verses += count($this->verseSplitter->split((string) $projected['poem_text']));
                $onProgress && $rowIndex % 100 === 0 && $onProgress($rowIndex, 'dry-run');
                continue;
            }

            try {
                DB::transaction(function () use ($projected, $sourceName, $quarantineModern, $report): void {
                    $this->upsertRow($projected, $sourceName, $quarantineModern, $report);
                });
            } catch (\Throwable $e) {
                $report->rejected($rowIndex, 'db_error:' . $e->getMessage(), (string) ($projected['poem_title'] ?? ''));
            }

            if ($onProgress && $rowIndex % 100 === 0) {
                $onProgress($rowIndex, 'ok');
            }
        }

        return $report;
    }

    /** @param  array<string, string|null>  $p */
    private function upsertRow(array $p, string $sourceName, bool $quarantineModern, ImportReport $report): void
    {
        $poet = $this->upsertPoet($p, $sourceName, $report);

        $status = $this->decideStatus($p, $poet, $quarantineModern);
        $poem = $this->upsertPoem($p, $poet, $sourceName, $status, $report);
        $this->syncVerses($poem, (string) $p['poem_text'], $report);

        if ($status === PoemStatus::Quarantined) {
            $report->quarantined();
        }
    }

    /** @param  array<string, string|null>  $p */
    private function upsertPoet(array $p, string $sourceName, ImportReport $report): Poet
    {
        $nameAr = (string) $p['poet_name'];
        $normalized = $this->normalizer->normalize($nameAr);

        // Prefer provenance-based match if source_id present on poet row (rare in Aldiwan).
        // Otherwise match on normalized name within this source.
        $existing = Poet::query()
            ->where('source', $sourceName)
            ->whereRaw('normalize_arabic(name_ar) = ?', [$normalized])
            ->first();

        // `category` field carries either an era OR a country (Aldiwan/Kaggle
        // convention — classical poets tagged by era, modern by country).
        // Try both; whichever binds wins.
        [$eraId, $countryId] = $this->routeCategoryForPoet($p);

        $attrs = [
            'name_ar' => $nameAr,
            'bio_ar' => $p['poet_bio'] ?: null,
            'era_id' => $eraId,
            'country_id' => $countryId,
        ];

        if ($existing) {
            // Enrich only: never overwrite an already-populated field.
            $enrich = [];
            foreach ($attrs as $k => $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                if ($existing->{$k} === null || $existing->{$k} === '') {
                    $enrich[$k] = $v;
                }
            }
            if ($enrich !== []) {
                $existing->fill($enrich)->save();
                $report->updated_poets++;
            }

            return $existing;
        }

        $poet = new Poet($attrs);
        $poet->slug = $this->slugs->generate($nameAr, 'poets');
        $poet->source = $sourceName;
        $poet->source_external_id = null; // Aldiwan doesn't ship poet ids
        $poet->import_meta = ['normalized_name' => $normalized];
        $poet->save();

        $report->imported_poets++;

        return $poet;
    }

    /**
     * @param  array<string, string|null>  $p
     */
    private function decideStatus(array $p, Poet $poet, bool $quarantineModern): PoemStatus
    {
        if (! $quarantineModern) {
            return PoemStatus::Published;
        }

        // Reliable "modern" signal in ahmedabelal = category text literally
        // says الحديث. We deliberately do NOT infer "modern" from country
        // tags — this dataset uses country tags for classical poets from
        // that region too (e.g. classical Emirati poets in the "الإمارات"
        // category), so country-based inference would quarantine tens of
        // thousands of legitimate pre-modern poems.
        $catNormalized = $this->normalizer->normalize((string) ($p['category'] ?? ''));
        $isModern = $catNormalized !== '' && str_contains($catNormalized, 'الحديث');

        if ($isModern && ($poet->death_year === null || $poet->death_year >= self::MODERN_ERA_CUTOFF_YEAR)) {
            return PoemStatus::Quarantined;
        }

        return PoemStatus::Published;
    }

    /**
     * `category` is either an era string or a country string.
     * Resolve against both taxonomies; return [era_id, country_id].
     *
     * @param  array<string, string|null>  $p
     * @return array{0: int|null, 1: int|null}
     */
    private function routeCategoryForPoet(array $p): array
    {
        // Legacy fields (poet_era / poet_country) still take precedence when a
        // richer source is used; ahmedabelal only sets `category`.
        $eraId = $this->taxonomy->eraId($p['poet_era'] ?? null)
            ?? $this->taxonomy->eraId($p['category'] ?? null);

        $countryId = $this->taxonomy->countryId($p['poet_country'] ?? null)
            ?? $this->taxonomy->countryId($p['category'] ?? null);

        return [$eraId, $countryId];
    }

    /** @param  array<string, string|null>  $p */
    private function upsertPoem(array $p, Poet $poet, string $sourceName, PoemStatus $status, ImportReport $report): Poem
    {
        $title = (string) $p['poem_title'];
        $rawText = (string) $p['poem_text'];
        $verseCount = count($this->verseSplitter->split($rawText));
        $titleNormalized = $this->normalizer->normalize($title);
        $sourceExternalId = $p['source_id'] ?: null;

        $query = Poem::query()->where('source', $sourceName)->where('poet_id', $poet->id);
        $existing = $sourceExternalId
            ? (clone $query)->where('source_external_id', $sourceExternalId)->first()
            : (clone $query)
                ->whereRaw('normalize_arabic(title_ar) = ?', [$titleNormalized])
                ->where('verse_count', $verseCount)
                ->first();

        [$eraFromCategory, $_countryFromCategory] = $this->routeCategoryForPoet($p);

        $attrs = [
            'poet_id' => $poet->id,
            'title_ar' => $title,
            'era_id' => $eraFromCategory ?? $poet->era_id,
            // ahmedabelal's `category` is era-or-country, not a genre — so
            // `poem.category_id` (genre) stays null unless a richer source
            // provides poem_category explicitly. We can classify by genre
            // later via NLP or admin curation.
            'category_id' => $this->taxonomy->categoryId($p['poem_category'] ?? null),
            'meter_id' => $this->taxonomy->meterId($p['poem_meter'] ?? null),
            'raw_source_text' => $rawText,
            'verse_count' => $verseCount,
            'status' => $status,
        ];

        if ($existing) {
            $existing->fill($attrs);
            if ($existing->isDirty()) {
                $existing->save();
                $report->updated_poems++;
            }

            return $existing;
        }

        $poem = new Poem($attrs);
        $poem->slug = $this->slugs->generate($poet->name_ar . ' ' . $title, 'poems');
        $poem->language = 'ar';
        $poem->published_at = $status === PoemStatus::Published ? now() : null;
        $poem->source = $sourceName;
        $poem->source_external_id = $sourceExternalId;
        $poem->import_meta = ['content_hash' => md5($this->normalizer->normalize($rawText))];
        $poem->save();

        $report->imported_poems++;

        return $poem;
    }

    private function syncVerses(Poem $poem, string $rawText, ImportReport $report): void
    {
        $split = $this->verseSplitter->split($rawText);
        $newHash = md5($this->normalizer->normalize($rawText));
        $existingHash = $poem->import_meta['content_hash'] ?? null;

        if ($existingHash === $newHash && $poem->verses()->count() === count($split)) {
            return; // idempotent no-op
        }

        $poem->verses()->delete();

        foreach ($split as $i => $v) {
            $poem->verses()->create([
                'position' => $i + 1,
                'hemistich_a' => $v['hemistich_a'],
                'hemistich_b' => $v['hemistich_b'],
            ]);
        }

        $poem->verse_count = count($split);
        $meta = $poem->import_meta ?? [];
        $meta['content_hash'] = $newHash;
        $poem->import_meta = $meta;
        $poem->save();

        $report->imported_verses += count($split);
    }
}
