<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Search\Contracts\SearchDriver;
use App\Http\Controllers\Controller;
use App\Http\Resources\PoemResource;
use App\Http\Resources\PoetResource;
use App\Http\Resources\VerseResource;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchDriver $driver)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:100',
            'type' => 'nullable|in:all,poem,poet,verse',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $results = $driver->search(
            query: $validated['q'],
            type: $validated['type'] ?? 'all',
            limit: (int) ($validated['limit'] ?? 20),
        );

        return response()->json([
            'data' => [
                'query' => $results['query'],
                'poems' => PoemResource::collection($results['poems'])->resolve(),
                'poets' => PoetResource::collection($results['poets'])->resolve(),
                'verses' => VerseResource::collection($results['verses'])->resolve(),
            ],
            'meta' => [
                'counts' => [
                    'poems' => $results['poems']->count(),
                    'poets' => $results['poets']->count(),
                    'verses' => $results['verses']->count(),
                ],
            ],
        ]);
    }
}
