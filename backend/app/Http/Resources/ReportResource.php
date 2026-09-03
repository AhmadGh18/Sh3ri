<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Poetry\Models\Report */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Never expose the integer id — moderators access reports via uuid.
            'uuid' => $this->uuid,
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'handled_at' => $this->handled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
