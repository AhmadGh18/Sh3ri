<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Poet;
use App\Enums\PoemStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PoemResource;
use App\Http\Resources\PoetResource;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PoetController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));

        $poets = QueryBuilder::for(Poet::query())
            ->with(['era', 'country'])
            ->withCount(['poems' => fn ($q) => $q->where('status', PoemStatus::Published)])
            ->allowedFilters(
                AllowedFilter::exact('era_id'),
                AllowedFilter::exact('country_id'),
                AllowedFilter::callback('era', fn ($q, $v) => $q->whereHas('era', fn ($e) => $e->where('slug', $v))),
                AllowedFilter::callback('country', fn ($q, $v) => $q->whereHas('country', fn ($c) => $c->where('slug', $v))),
            )
            ->allowedSorts(
                AllowedSort::field('name_ar'),
                AllowedSort::field('birth_year'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('name_ar')
            ->cursorPaginate($perPage)
            ->withQueryString();

        return PoetResource::collection($poets);
    }

    public function show(Poet $poet)
    {
        $poet->load(['era', 'country'])
             ->loadCount(['poems' => fn ($q) => $q->where('status', PoemStatus::Published)]);

        return PoetResource::make($poet);
    }

    public function poems(Request $request, Poet $poet)
    {
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));

        $poems = $poet->poems()
            ->with(['era', 'category', 'meter'])
            ->where('status', PoemStatus::Published)
            ->orderByDesc('published_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        return PoemResource::collection($poems);
    }
}
