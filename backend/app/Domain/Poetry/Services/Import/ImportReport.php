<?php

declare(strict_types=1);

namespace App\Domain\Poetry\Services\Import;

/**
 * Aggregates per-row outcomes into a JSON-friendly report.
 * Kept as a plain value object; the importer is responsible for calling it.
 */
final class ImportReport
{
    public int $read = 0;
    public int $imported_poets = 0;
    public int $imported_poems = 0;
    public int $updated_poets = 0;
    public int $updated_poems = 0;
    public int $imported_verses = 0;
    public int $rejected = 0;
    public int $skipped_quarantined = 0;
    public int $skipped_duplicate = 0;

    /** @var array<string, int> */
    public array $reject_reasons = [];

    /** @var list<array{row: int, reason: string, snippet: string}> */
    public array $sample_failures = [];

    private const SAMPLE_FAILURE_CAP = 20;

    public function rowRead(): void
    {
        $this->read++;
    }

    public function rejected(int $rowIndex, string $reason, string $snippet = ''): void
    {
        $this->rejected++;
        $this->reject_reasons[$reason] = ($this->reject_reasons[$reason] ?? 0) + 1;
        if (count($this->sample_failures) < self::SAMPLE_FAILURE_CAP) {
            $this->sample_failures[] = [
                'row' => $rowIndex,
                'reason' => $reason,
                'snippet' => mb_substr($snippet, 0, 160),
            ];
        }
    }

    public function quarantined(): void
    {
        $this->skipped_quarantined++;
    }

    public function duplicate(): void
    {
        $this->skipped_duplicate++;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'read' => $this->read,
            'imported' => [
                'poets' => $this->imported_poets,
                'poems' => $this->imported_poems,
                'verses' => $this->imported_verses,
            ],
            'updated' => [
                'poets' => $this->updated_poets,
                'poems' => $this->updated_poems,
            ],
            'rejected' => $this->rejected,
            'skipped_quarantined' => $this->skipped_quarantined,
            'skipped_duplicate' => $this->skipped_duplicate,
            'reject_reasons' => $this->reject_reasons,
            'sample_failures' => $this->sample_failures,
        ];
    }
}
