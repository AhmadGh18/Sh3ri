<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Import;

use App\Domain\Poetry\Models\Category;
use App\Domain\Poetry\Models\Country;
use App\Domain\Poetry\Models\Era;
use App\Domain\Poetry\Models\Meter;
use App\Domain\Poetry\Support\ArabicNormalizer;

/**
 * Fuzzy-matches free-text taxonomy strings from the source CSV
 * to seeded taxonomy rows via normalize_arabic() equivalence.
 *
 * Loaded once per import run and cached in-memory so we don't
 * hit the DB per row for the same era / category.
 */
final class TaxonomyResolver
{
    /** @var array<string, int>|null */
    private ?array $eraByNormalized = null;
    /** @var array<string, int>|null */
    private ?array $categoryByNormalized = null;
    /** @var array<string, int>|null */
    private ?array $countryByNormalized = null;
    /** @var array<string, int>|null */
    private ?array $meterByNormalized = null;

    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    public function eraId(?string $arabic): ?int
    {
        return $this->lookup($arabic, $this->eras());
    }

    public function categoryId(?string $arabic): ?int
    {
        return $this->lookup($arabic, $this->categories());
    }

    public function countryId(?string $arabic): ?int
    {
        return $this->lookup($arabic, $this->countries());
    }

    public function meterId(?string $arabic): ?int
    {
        return $this->lookup($arabic, $this->meters());
    }

    /** @param  array<string, int>  $index */
    private function lookup(?string $arabic, array $index): ?int
    {
        if ($arabic === null || $arabic === '') {
            return null;
        }
        $key = $this->normalizer->normalize($arabic);

        return $index[$key] ?? null;
    }

    /** @return array<string, int> */
    private function eras(): array
    {
        if ($this->eraByNormalized === null) {
            $this->eraByNormalized = $this->buildIndex(Era::query()->pluck('name_ar', 'id')->all());
        }

        return $this->eraByNormalized;
    }

    /** @return array<string, int> */
    private function categories(): array
    {
        if ($this->categoryByNormalized === null) {
            $this->categoryByNormalized = $this->buildIndex(Category::query()->pluck('name_ar', 'id')->all());
        }

        return $this->categoryByNormalized;
    }

    /** @return array<string, int> */
    private function countries(): array
    {
        if ($this->countryByNormalized === null) {
            $this->countryByNormalized = $this->buildIndex(Country::query()->pluck('name_ar', 'id')->all());
        }

        return $this->countryByNormalized;
    }

    /** @return array<string, int> */
    private function meters(): array
    {
        if ($this->meterByNormalized === null) {
            $this->meterByNormalized = $this->buildIndex(Meter::query()->pluck('name_ar', 'id')->all());
        }

        return $this->meterByNormalized;
    }

    /**
     * @param  array<int, string>  $rows  id => name_ar
     * @return array<string, int>         normalized_name => id
     */
    private function buildIndex(array $rows): array
    {
        $out = [];
        foreach ($rows as $id => $name) {
            $key = $this->normalizer->normalize($name);
            $out[$key] = $id;
        }

        return $out;
    }
}
