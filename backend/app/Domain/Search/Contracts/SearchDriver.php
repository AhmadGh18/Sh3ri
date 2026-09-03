<?php

declare(strict_types=1);

namespace App\Domain\Search\Contracts;

/**
 * Search abstraction. MVP is PostgresSearchDriver. Later drivers
 * (Meilisearch, OpenSearch, Typesense) implement this same contract
 * so controllers/resources don't change.
 */
interface SearchDriver
{
    /**
     * @param  'all'|'poem'|'poet'|'verse'  $type
     * @return array{
     *     poems: \Illuminate\Support\Collection<int, \App\Domain\Poetry\Models\Poem>,
     *     poets: \Illuminate\Support\Collection<int, \App\Domain\Poetry\Models\Poet>,
     *     verses: \Illuminate\Support\Collection<int, \App\Domain\Poetry\Models\Verse>,
     *     query: string,
     * }
     */
    public function search(string $query, string $type = 'all', int $limit = 20): array;
}
