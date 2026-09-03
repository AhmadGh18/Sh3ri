<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Poetry\Models\Report;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Resources\ReportResource;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request)
    {
        $data = $request->validated();

        $report = new Report([
            'user_id' => $request->user()->id,
            'reportable_type' => $data['reportable_type'],
            'reportable_id' => $data['reportable_id'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
        ]);
        // `status` is intentionally NOT in $fillable — set explicitly.
        $report->status = 'open';
        $report->save();
        $report->refresh(); // pick up DB-generated uuid

        return ReportResource::make($report)->response()->setStatusCode(201);
    }
}
