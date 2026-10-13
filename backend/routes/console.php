<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jalankan satu command yang mengiterasi seluruh master project aktif.
Schedule::command('app:sync-google-sheet')->everyFiveMinutes();
Schedule::command('wbs:generate-ai-summary')->dailyAt('06:00')->timezone('Asia/Jakarta')->withoutOverlapping();
