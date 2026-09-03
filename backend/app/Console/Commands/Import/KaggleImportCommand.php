<?php

declare(strict_types=1);

namespace App\Console\Commands\Import;

use App\Domain\Poetry\Services\Import\PoetryImporter;
use Illuminate\Console\Command;

class KaggleImportCommand extends Command
{
    protected $signature = 'sh3ri:import-kaggle
        {file : Path to the Kaggle CSV}
        {--source=kaggle_ahmedabelal : source identifier stored on each row}
        {--limit= : maximum number of rows to import}
        {--dry-run : parse and validate without writing to the DB}
        {--no-quarantine : import modern-era poets too (default: quarantine post-1950)}
        {--report=storage/app/import/report.json : where to write the JSON report}';

    protected $description = 'Import an Arabic-poetry CSV into the sh3ri database (idempotent).';

    public function handle(PoetryImporter $importer): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $this->info("Importing {$file}");
        $limit = $this->option('limit');
        $limit = $limit === null ? null : max(1, (int) $limit);

        $started = microtime(true);
        $report = $importer->importCsv(
            filePath: $file,
            sourceName: (string) $this->option('source'),
            dryRun: (bool) $this->option('dry-run'),
            quarantineModern: ! (bool) $this->option('no-quarantine'),
            limit: $limit,
            onProgress: function (int $n, string $status): void {
                $this->line("  row {$n} — {$status}");
            },
        );

        $elapsed = round(microtime(true) - $started, 2);
        $payload = $report->toArray();
        $payload['elapsed_seconds'] = $elapsed;
        $payload['dry_run'] = (bool) $this->option('dry-run');
        $payload['source'] = (string) $this->option('source');

        $out = base_path($this->option('report'));
        @mkdir(dirname($out), 0755, true);
        file_put_contents($out, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->line('---- Import report ----');
        $this->line("read              : {$payload['read']}");
        $this->line("imported poets    : {$payload['imported']['poets']}");
        $this->line("imported poems    : {$payload['imported']['poems']}");
        $this->line("imported verses   : {$payload['imported']['verses']}");
        $this->line("updated poets     : {$payload['updated']['poets']}");
        $this->line("updated poems     : {$payload['updated']['poems']}");
        $this->line("rejected          : {$payload['rejected']}");
        $this->line("quarantined       : {$payload['skipped_quarantined']}");
        $this->line("elapsed           : {$elapsed}s");
        $this->line("report written to : {$out}");

        if (! empty($payload['reject_reasons'])) {
            $this->newLine();
            $this->line('Reject reasons:');
            foreach ($payload['reject_reasons'] as $reason => $count) {
                $this->line("  {$reason}: {$count}");
            }
        }

        return $report->rejected === $report->read && $report->read > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
