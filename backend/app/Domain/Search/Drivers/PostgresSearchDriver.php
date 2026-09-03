<?php

declare(strict_types=1);

namespace App\Domain\Search\Drivers;

use App\Domain\Poetry\Models\Poem;
use App\Domain\Poetry\Models\Poet;
use App\Domain\Poetry\Models\Verse;
use App\Domain\Poetry\Support\ArabicNormalizer;
use App\Domain\Search\Contracts\SearchDriver;
use App\Enums\PoemStatus;
use Illuminate\Support\Facades\DB;

final class PostgresSearchDriver implements SearchDriver
{
    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    public function search(string $query, string $type = 'all', int $limit = 20): array
    {
        $prepared = $this->normalizer->prepareQuery($query);
        $q = $prepared['normalized'];

        $poems = collect();
        $poets = collect();
        $verses = collect();

        if ($q === '') {
            return compact('poems', 'poets', 'verses') + ['query' => $query];
        }

        if ($type === 'all' || $type === 'poem') {
            $poems = Poem::query()
                ->with(['poet', 'era', 'category'])
                ->where('status', PoemStatus::Published)
                ->where(function ($qb) use ($q) {
                    $qb->whereRaw("search_tsv @@ plainto_tsquery('arabic_simple', ?)", [$q])
                       ->orWhereRaw('title_normalized % ?', [$q]);
                })
                ->orderByRaw("
                    GREATEST(
                        ts_rank_cd(search_tsv, plainto_tsquery('arabic_simple', ?)),
                        similarity(title_normalized, ?)
                    ) DESC
                ", [$q, $q])
                ->limit($limit)
                ->get();
        }

        if ($type === 'all' || $type === 'poet') {
            $poets = Poet::query()
                ->with(['era', 'country'])
                ->where(function ($qb) use ($q) {
                    $qb->whereRaw("search_tsv @@ plainto_tsquery('arabic_simple', ?)", [$q])
                       ->orWhereRaw('name_normalized % ?', [$q]);
                })
                ->orderByRaw('similarity(name_normalized, ?) DESC', [$q])
                ->limit($limit)
                ->get();
        }

        if ($type === 'all' || $type === 'verse') {
            $verses = Verse::query()
                ->with(['poem.poet'])
                ->whereRaw("search_tsv @@ plainto_tsquery('arabic_simple', ?)", [$q])
                ->orderByRaw("ts_rank_cd(search_tsv, plainto_tsquery('arabic_simple', ?)) DESC", [$q])
                ->limit($limit)
                ->get();
        }

        return compact('poems', 'poets', 'verses') + ['query' => $query];
    }
}
