<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly LRU eviction of the verse audio cache. Keeps disk usage under the
// caps in config/sh3ri.php (defaults 100k files / 5 GB), oldest-first.
Schedule::command('sh3ri:prune-verse-audio')
    ->dailyAt('03:15')
    ->onOneServer()
    ->withoutOverlapping();
