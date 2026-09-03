<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\UserPoem;
use App\Domain\Poetry\Models\UserPoemComment;
use App\Domain\Poetry\Models\UserPoemUpvote;
use App\Enums\UserPoemStatus;
use App\Enums\UserPoemVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserPoemCommentResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityInteractionController extends Controller
{
    /** Toggle an upvote. Returns new count + whether current user upvoted. */
    public function toggleUpvote(Request $request, UserPoem $userPoem)
    {
        $this->assertPublic($userPoem);

        $user = $request->user();
        $existing = UserPoemUpvote::query()
            ->where('user_poem_id', $userPoem->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $upvoted = false;
        } else {
            UserPoemUpvote::create([
                'user_poem_id' => $userPoem->id,
                'user_id'      => $user->id,
                'created_at'   => now(),
            ]);
            $upvoted = true;
        }

        $count = UserPoemUpvote::where('user_poem_id', $userPoem->id)->count();

        return response()->json([
            'data' => ['upvoted_by_me' => $upvoted, 'upvote_count' => $count],
        ]);
    }

    /** Paginated list of comments (public — no auth needed to read). */
    public function listComments(Request $request, UserPoem $userPoem)
    {
        $this->assertPublic($userPoem);
        $perPage = min((int) $request->query('per_page', 30), config('sh3ri.pagination.max_per_page'));

        $comments = UserPoemComment::query()
            ->with(['author:id,uuid,name'])
            ->where('user_poem_id', $userPoem->id)
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return UserPoemCommentResource::collection($comments);
    }

    /** Post a new comment on a community poem. Auth required. */
    public function storeComment(Request $request, UserPoem $userPoem)
    {
        $this->assertPublic($userPoem);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $c = new UserPoemComment(['body' => trim($data['body'])]);
        $c->user_poem_id = $userPoem->id;
        $c->user_id = $request->user()->id;
        $c->save();
        $c->refresh(); // pick up uuid + timestamps
        $c->load('author:id,uuid,name');

        return UserPoemCommentResource::make($c)->response()->setStatusCode(201);
    }

    /** Delete a comment. Author OR moderator. */
    public function deleteComment(Request $request, UserPoem $userPoem, UserPoemComment $comment)
    {
        $this->assertPublic($userPoem);
        abort_unless(
            (int) $comment->user_poem_id === (int) $userPoem->id,
            404,
        );

        $user = $request->user();
        abort_unless(
            $user->id === $comment->user_id || $user->can('submission.review'),
            403,
        );

        $comment->delete();
        return response()->json(null, 204);
    }

    private function assertPublic(UserPoem $p): void
    {
        abort_unless(
            $p->status === UserPoemStatus::Published
            && $p->visibility === UserPoemVisibility::Public,
            404,
        );
    }
}
