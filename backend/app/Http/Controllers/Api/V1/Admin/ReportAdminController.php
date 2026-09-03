<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Poetry\Models\Report;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $perPage = min((int) $request->query('per_page', 20), config('sh3ri.pagination.max_per_page'));

        $q = Report::query()->orderByDesc('created_at');
        if (in_array($status, ['open', 'closed', 'actioned'], true)) {
            $q->where('status', $status);
        }

        return ReportResource::collection($q->cursorPaginate($perPage));
    }

    public function action(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['close', 'action'])],
        ]);

        $report->status = $data['action'] === 'action' ? 'actioned' : 'closed';
        $report->handled_by = $request->user()->id;
        $report->handled_at = now();
        $report->save();

        return ReportResource::make($report);
    }
}
