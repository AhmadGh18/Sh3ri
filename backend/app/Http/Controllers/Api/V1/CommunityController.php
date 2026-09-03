<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\UserPoem;
use App\Domain\Poetry\Models\UserPoemUpvote;
use App\Enums\UserPoemStatus;
use App\Enums\UserPoemVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserPoemResource;
use Illuminate\Http\Request;

/**
 * Public feed of user-authored poems that their authors have both
 * published (status=published) AND made public (visibility=public).
 * Anything else stays visible only to its owner via /me/user-poems.
 */
class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));
        $sort = $request->query('sort') === 'new' ? 'new' : 'top'; // default = most upvoted
        $categorySlug = (string) $request->query('filter.category', $request->input('filter.category', ''));

        $query = UserPoem::query()
            ->with(['user:id,uuid,name', 'era', 'category'])
            ->withCount(['upvotes', 'comments'])
            ->where('status', UserPoemStatus::Published)
            ->where('visibility', UserPoemVisibility::Public);

        if ($categorySlug !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        // Top: most upvoted first, tie-break on recency. new: newest first.
        if ($sort === 'top') {
            $query->orderByDesc('upvotes_count')->orderByDesc('published_at');
        } else {
            $query->orderByDesc('published_at');
        }

        $poems = $query->cursorPaginate($perPage);
        $this->annotateUpvoted($request, $poems->getCollection());

        return UserPoemResource::collection($poems);
    }

    public function show(Request $request, UserPoem $userPoem)
    {
        abort_unless(
            $userPoem->status === UserPoemStatus::Published
            && $userPoem->visibility === UserPoemVisibility::Public,
            404,
        );
        $userPoem->load(['user:id,uuid,name', 'era', 'category'])
                 ->loadCount(['upvotes', 'comments']);
        $this->annotateUpvoted($request, collect([$userPoem]));

        return UserPoemResource::make($userPoem);
    }

    /** Set `upvoted_by_me` attribute on each poem for the current user. */
    private function annotateUpvoted(Request $request, \Illuminate\Support\Collection $poems): void
    {
        $user = $request->user();
        if ($user === null || $poems->isEmpty()) return;

        $mine = UserPoemUpvote::query()
            ->where('user_id', $user->id)
            ->whereIn('user_poem_id', $poems->pluck('id'))
            ->pluck('user_poem_id')
            ->flip();

        foreach ($poems as $p) {
            $p->setAttribute('upvoted_by_me', $mine->has($p->id));
        }
    }
}
