<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Submissions\Models\Submission;
use App\Domain\Submissions\Models\SubmissionRevision;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));

        $q = Submission::query()->orderByDesc('created_at');
        if (in_array($status, ['pending', 'approved', 'rejected', 'changes_requested'], true)) {
            $q->where('status', $status);
        }

        $rows = $q->cursorPaginate($perPage);

        return SubmissionResource::collection($rows);
    }

    public function approve(Request $request, Submission $submission)
    {
        $this->authorize('review', $submission);
        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:2000']]);

        DB::transaction(function () use ($submission, $request, $data) {
            $submission->status = SubmissionStatus::Approved;
            $submission->reviewed_by = $request->user()->id;
            $submission->reviewed_at = now();
            $submission->review_notes = $data['review_notes'] ?? null;
            $submission->save();

            SubmissionRevision::create([
                'submission_id' => $submission->id,
                'editor_id' => $request->user()->id,
                'diff' => null,
                'snapshot' => $submission->payload,
                'note' => 'approved',
                'created_at' => now(),
            ]);
        });

        // NB: this deliberately does NOT auto-apply the payload to the canonical
        // poem/poet tables. That should happen through a dedicated
        // `ApplySubmissionAction` (poem-add, poet-add, correction-merge) so
        // curators can review the diff one last time before promotion.

        return SubmissionResource::make($submission->refresh());
    }

    public function reject(Request $request, Submission $submission)
    {
        $this->authorize('review', $submission);
        $data = $request->validate(['review_notes' => ['required', 'string', 'max:2000']]);

        $submission->status = SubmissionStatus::Rejected;
        $submission->reviewed_by = $request->user()->id;
        $submission->reviewed_at = now();
        $submission->review_notes = $data['review_notes'];
        $submission->save();

        SubmissionRevision::create([
            'submission_id' => $submission->id,
            'editor_id' => $request->user()->id,
            'diff' => null,
            'snapshot' => $submission->payload,
            'note' => 'rejected: ' . $data['review_notes'],
            'created_at' => now(),
        ]);

        return SubmissionResource::make($submission);
    }

    public function requestChanges(Request $request, Submission $submission)
    {
        $this->authorize('review', $submission);
        $data = $request->validate(['review_notes' => ['required', 'string', 'max:2000']]);

        $submission->status = SubmissionStatus::ChangesRequested;
        $submission->reviewed_by = $request->user()->id;
        $submission->reviewed_at = now();
        $submission->review_notes = $data['review_notes'];
        $submission->save();

        return SubmissionResource::make($submission);
    }
}
