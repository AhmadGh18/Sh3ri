<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Poem;
use App\Enums\PoemStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PoemResource;
use App\Http\Resources\VerseResource;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PoemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));

        $poems = QueryBuilder::for(Poem::query()->where('status', PoemStatus::Published))
            ->with(['poet', 'era', 'category', 'meter'])
            ->allowedFilters(
                AllowedFilter::exact('poet', 'poet_id'),
                AllowedFilter::exact('era_id'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('meter_id'),
                AllowedFilter::callback('era', fn ($q, $v) => $q->whereHas('era', fn ($e) => $e->where('slug', $v))),
                AllowedFilter::callback('category', fn ($q, $v) => $q->whereHas('category', fn ($c) => $c->where('slug', $v))),
                AllowedFilter::callback('meter', fn ($q, $v) => $q->whereHas('meter', fn ($m) => $m->where('slug', $v))),
                AllowedFilter::callback('poet_slug', fn ($q, $v) => $q->whereHas('poet', fn ($p) => $p->where('slug', $v))),
            )
            ->allowedSorts(
                AllowedSort::field('published_at'),
                AllowedSort::field('created_at'),
                AllowedSort::field('verse_count'),
            )
            ->defaultSort('-published_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        return PoemResource::collection($poems);
    }

    public function show(Poem $poem)
    {
        abort_unless($poem->status === PoemStatus::Published, 404);
        $poem->load(['poet.era', 'poet.country', 'era', 'category', 'meter', 'topics', 'verses']);

        return PoemResource::make($poem);
    }

    public function verses(Request $request, Poem $poem)
    {
        abort_unless($poem->status === PoemStatus::Published, 404);
        $perPage = min((int) $request->query('per_page', 50), config('sh3ri.pagination.max_per_page'));

        $verses = $poem->verses()->cursorPaginate($perPage)->withQueryString();

        return VerseResource::collection($verses);
    }
}
