<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\UserPoem;
use App\Enums\UserPoemStatus;
use App\Enums\UserPoemVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserPoem\StoreUserPoemRequest;
use App\Http\Requests\UserPoem\UpdateUserPoemRequest;
use App\Http\Resources\UserPoemResource;
use Illuminate\Http\Request;

class UserPoemController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', UserPoem::class);
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));

        $poems = UserPoem::query()
            ->with(['era', 'category'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return UserPoemResource::collection($poems);
    }

    public function show(UserPoem $userPoem)
    {
        $this->authorize('view', $userPoem);
        $userPoem->load(['era', 'category']);

        return UserPoemResource::make($userPoem);
    }

    public function store(StoreUserPoemRequest $request)
    {
        $this->authorize('create', UserPoem::class);

        $data = $request->validated();
        $poem = new UserPoem([
            'title_ar' => $data['title_ar'],
            'raw_text' => $data['raw_text'],
            'era_id' => $data['era_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'visibility' => $data['visibility'] ?? UserPoemVisibility::Private->value,
        ]);
        // user_id / status / published_at are set explicitly (excluded from
        // $fillable — see model comment). New user poems always start as drafts.
        $poem->user_id = $request->user()->id;
        $poem->status = UserPoemStatus::Draft;
        $poem->save();
        $poem->refresh(); // pick up DB-defaulted uuid

        return UserPoemResource::make($poem->load(['era', 'category']))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateUserPoemRequest $request, UserPoem $userPoem)
    {
        // Belt-and-braces authorization: the FormRequest already gates on
        // UserPoemPolicy@update, but we assert it again so this endpoint
        // stays safe even if UpdateUserPoemRequest's authorize() is later
        // simplified or the FormRequest is swapped for a plain Request.
        $this->authorize('update', $userPoem);
        $userPoem->fill($request->validated())->save();

        return UserPoemResource::make($userPoem->load(['era', 'category']));
    }

    public function destroy(Request $request, UserPoem $userPoem)
    {
        $this->authorize('delete', $userPoem);
        $userPoem->delete();

        return response()->json(null, 204);
    }

    public function publish(Request $request, UserPoem $userPoem)
    {
        $this->authorize('update', $userPoem);
        $userPoem->status = UserPoemStatus::Published;
        $userPoem->published_at = now();
        $userPoem->save();

        return UserPoemResource::make($userPoem->load(['era', 'category']));
    }

    public function unpublish(Request $request, UserPoem $userPoem)
    {
        $this->authorize('update', $userPoem);
        $userPoem->status = UserPoemStatus::Draft;
        $userPoem->published_at = null;
        $userPoem->save();

        return UserPoemResource::make($userPoem->load(['era', 'category']));
    }
}
