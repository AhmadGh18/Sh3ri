<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps the per-verse MP3 cache from growing without bound.
 *
 * Strategy: while total file count > MAX_FILES OR total bytes > MAX_BYTES,
 * delete the oldest file by last-access (mtime as a proxy — most filesystems
 * update mtime on write and atime is often disabled for perf reasons).
 * Legitimate corpus ~742k verses × ~50 KB = ~37 GB — hence the sane defaults
 * that keep only the hot subset.
 *
 * Runs daily via routes/console.php; can also be invoked manually.
 */
class PruneVerseAudioCommand extends Command
{
    protected $signature = 'sh3ri:prune-verse-audio
        {--max-files= : override cache_max_files}
        {--max-bytes= : override cache_max_bytes}
        {--dry-run : report only, do not delete}';

    protected $description = 'Evict oldest verse audio MP3s until under configured caps.';

    public function handle(): int
    {
        $disk = config('sh3ri.tts.cache_disk', 'local');
        $path = trim((string) config('sh3ri.tts.cache_path', 'verse_audio'), '/');
        $maxFiles = (int) ($this->option('max-files') ?? config('sh3ri.tts.cache_max_files'));
        $maxBytes = (int) ($this->option('max-bytes') ?? config('sh3ri.tts.cache_max_bytes'));
        $dryRun   = (bool) $this->option('dry-run');

        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            $this->info("Cache dir does not exist yet: {$path}");
            return self::SUCCESS;
        }

        // Enumerate — pull (name, size, mtime) so we can sort by age and cap.
        $absDir = $storage->path($path);
        $files = [];
        $totalBytes = 0;
        foreach (scandir($absDir) ?: [] as $name) {
            if ($name === '.' || $name === '..') continue;
            $abs = $absDir . DIRECTORY_SEPARATOR . $name;
            if (! is_file($abs)) continue;
            $sz = filesize($abs) ?: 0;
            $files[] = ['path' => $abs, 'name' => $name, 'size' => $sz, 'mtime' => filemtime($abs) ?: 0];
            $totalBytes += $sz;
        }

        $this->line("Before: " . count($files) . " files, " . $this->human($totalBytes));

        if (count($files) <= $maxFiles && $totalBytes <= $maxBytes) {
            $this->info('Under caps — nothing to prune.');
            return self::SUCCESS;
        }

        // Oldest-first (LRU-ish via mtime).
        usort($files, fn ($a, $b) => $a['mtime'] <=> $b['mtime']);

        $deleted = 0; $freed = 0;
        foreach ($files as $f) {
            if (count($files) - $deleted <= $maxFiles && $totalBytes - $freed <= $maxBytes) break;
            if ($dryRun) {
                $this->line("would delete: {$f['name']} (" . $this->human($f['size']) . ')');
            } else {
                @unlink($f['path']);
            }
            $deleted++;
            $freed += $f['size'];
        }

        $this->line(($dryRun ? 'Would delete ' : 'Deleted ') . $deleted . ' file(s), freeing ' . $this->human($freed));
        return self::SUCCESS;
    }

    private function human(int $bytes): string
    {
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0; $b = (float) $bytes;
        while ($b >= 1024 && $i < count($u) - 1) { $b /= 1024; $i++; }
        return round($b, 1) . ' ' . $u[$i];
    }
}
