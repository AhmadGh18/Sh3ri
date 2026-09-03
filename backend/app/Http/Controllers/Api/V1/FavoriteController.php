<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Favorite;
use App\Domain\Poetry\Models\Poem;
use App\Domain\Poetry\Models\Verse;
use App\Enums\FavoritableType;
use App\Http\Controllers\Controller;
use App\Http\Resources\PoemResource;
use App\Http\Resources\VerseResource;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type'); // optional filter: poem | verse
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));

        $query = Favorite::query()->where('user_id', $user->id);
        if (in_array($type, ['poem', 'verse'], true)) {
            $query->where('favoritable_type', $type);
        }

        $rows = $query->orderByDesc('created_at')->cursorPaginate($perPage);

        // Bulk-load target entities per type to avoid N+1.
        $poemIds = $rows->getCollection()->where('favoritable_type', FavoritableType::Poem)->pluck('favoritable_id')->all();
        $verseIds = $rows->getCollection()->where('favoritable_type', FavoritableType::Verse)->pluck('favoritable_id')->all();

        $poems = Poem::with(['poet', 'era'])->whereIn('id', $poemIds)->get()->keyBy('id');
        $verses = Verse::with(['poem.poet'])->whereIn('id', $verseIds)->get()->keyBy('id');

        $rows->getCollection()->transform(function (Favorite $f) use ($poems, $verses) {
            if ($f->favoritable_type === FavoritableType::Poem) {
                $f->setRelation('poem', $poems->get($f->favoritable_id));
            } else {
                $f->setRelation('verse', $verses->get($f->favoritable_id));
            }
            return $f;
        });

        return response()->json([
            'data' => $rows->getCollection()->map(fn (Favorite $f) => [
                'type' => $f->favoritable_type->value,
                'poem' => $f->favoritable_type === FavoritableType::Poem && $f->relationLoaded('poem') && $f->poem
                    ? PoemResource::make($f->poem)->resolve() : null,
                'verse' => $f->favoritable_type === FavoritableType::Verse && $f->relationLoaded('verse') && $f->verse
                    ? VerseResource::make($f->verse)->resolve() : null,
                'created_at' => $f->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'per_page' => $rows->perPage(),
                'next_cursor' => $rows->nextCursor()?->encode(),
                'prev_cursor' => $rows->previousCursor()?->encode(),
            ],
        ]);
    }

    public function favoritePoem(Request $request, Poem $poem)
    {
        Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'favoritable_type' => FavoritableType::Poem->value,
            'favoritable_id' => $poem->id,
        ], ['created_at' => now()]);

        return response()->json(null, 204);
    }

    public function unfavoritePoem(Request $request, Poem $poem)
    {
        Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('favoritable_type', FavoritableType::Poem->value)
            ->where('favoritable_id', $poem->id)
            ->delete();

        return response()->json(null, 204);
    }

    public function favoriteVerse(Request $request, Verse $verse)
    {
        Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'favoritable_type' => FavoritableType::Verse->value,
            'favoritable_id' => $verse->id,
        ], ['created_at' => now()]);

        return response()->json(null, 204);
    }

    public function unfavoriteVerse(Request $request, Verse $verse)
    {
        Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('favoritable_type', FavoritableType::Verse->value)
            ->where('favoritable_id', $verse->id)
            ->delete();

        return response()->json(null, 204);
    }
}
