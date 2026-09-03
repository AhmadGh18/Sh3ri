<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Submissions\Models\Submission;
use App\Domain\Submissions\Models\SubmissionRevision;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Submission::class);
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));

        $subs = Submission::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return SubmissionResource::collection($subs);
    }

    public function show(Request $request, Submission $submission)
    {
        $this->authorize('view', $submission);

        return SubmissionResource::make($submission);
    }

    public function store(StoreSubmissionRequest $request)
    {
        $this->authorize('create', Submission::class);
        $data = $request->validated();

        $submission = DB::transaction(function () use ($data, $request) {
            $sub = new Submission([
                'type' => $data['type'],
                'target_type' => $data['target_type'] ?? null,
                'target_id' => $data['target_id'] ?? null,
                'payload' => $data['payload'],
                'status' => SubmissionStatus::Pending->value,
            ]);
            $sub->user_id = $request->user()->id;
            $sub->save();
            $sub->refresh();

            SubmissionRevision::create([
                'submission_id' => $sub->id,
                'editor_id' => $request->user()->id,
                'diff' => null,
                'snapshot' => $data['payload'],
                'note' => 'initial submission',
                'created_at' => now(),
            ]);

            return $sub;
        });

        return SubmissionResource::make($submission)->response()->setStatusCode(201);
    }
}
