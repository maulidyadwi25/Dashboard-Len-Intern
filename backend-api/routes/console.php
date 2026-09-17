<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\MasterProject;
use App\Models\ProjectLink;
use App\Services\GoogleSheetSyncService;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sheets:sync')->everyFiveMinutes();

Artisan::command('project:add {spreadsheet_id} {--name=} {--code=} {--description=}', function ($spreadsheetId, GoogleSheetSyncService $syncService) {
    if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $spreadsheetId, $matches)) {
        $spreadsheetId = $matches[1];
    }

    $existing = MasterProject::where('spreadsheet_id', $spreadsheetId)->first();
    if ($existing) {
        $this->warn("Project dengan Spreadsheet ID ini sudah ada [ID: {$existing->id} - {$existing->project_name}].");
        $this->info('Menyinkronkan data...');
        $syncService->syncAll($existing->id);
        $this->info('✓ Selesai diperbarui.');
        return 0;
    }

    $projectName = $this->option('name') ?: 'Proyek Baru (' . substr($spreadsheetId, 0, 8) . ')';
    $projectCode = $this->option('code') ?: 'PRJ-' . time();

    $project = MasterProject::create([
        'project_name' => $projectName,
        'project_code' => $projectCode,
        'spreadsheet_id' => $spreadsheetId,
        'description' => $this->option('description'),
        'last_synced_at' => now(),
    ]);

    $this->info("✓ Master Project berhasil didaftarkan [ID: {$project->id}]!");
    $syncService->syncAll($project->id);

    $refreshed = $project->fresh();
    $this->line('==========================================');
    $this->info('Detail Project Tersimpan:');
    $this->line("  - ID di Database : {$refreshed->id}");
    $this->line("  - Nama Project   : {$refreshed->project_name}");
    $this->line("  - Kode Project   : {$refreshed->project_code}");
    $this->line("  - Spreadsheet ID : {$refreshed->spreadsheet_id}");
    $this->line('==========================================');
    return 0;
})->purpose('Mendaftarkan Master Project baru dan langsung menyinkronkan datanya');
