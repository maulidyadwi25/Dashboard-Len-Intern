<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetSyncService;
use Illuminate\Console\Command;

class SyncGoogleSheetsCommand extends Command
{
    protected $signature = 'sheets:sync 
                            {--sheet= : Tentukan sheet tertentu: progress_revenue atau procurement}
                            {--project= : Tentukan ID Project tertentu (default: semua project)}';

    protected $description = 'Sinkronisasi data dari Google Sheets ke SQLite Database untuk Master Project';

    public function handle(GoogleSheetSyncService $syncService): int
    {
        set_time_limit(300); // Set batas waktu eksekusi jadi 5 menit (300 detik)

        $this->info('Memulai sinkronisasi Google Sheets ke Master Project SQLite...');

        $targetSheet = $this->option('sheet');
        $projectId = $this->option('project');

        $projects = $projectId 
            ? \App\Models\MasterProject::where('id', $projectId)->get() 
            : \App\Models\MasterProject::all();

        if ($projects->isEmpty()) {
            $projects = collect([null]); // Fallback ke default .env jika belum ada baris project
        }

        $hasError = false;

        foreach ($projects as $proj) {
            $pId = $proj?->id;
            $pName = $proj?->project_name ?? 'Default Project';
            $this->line("\n==========================================");
            $this->info("Menyinkronkan Project [ID: {$pId}]: {$pName}...");

            if ($targetSheet === 'progress_revenue') {
                $result = $syncService->syncProgressAndRevenue($pId);
                if ($result['success']) {
                    $this->info("✓ Berhasil: {$result['message']}");
                } else {
                    $this->error("✗ Gagal: {$result['message']}");
                    $hasError = true;
                }
            } elseif ($targetSheet === 'procurement') {
                $result = $syncService->syncProcurement($pId);
                if ($result['success']) {
                    $this->info("✓ Berhasil: {$result['message']}");
                } else {
                    $this->error("✗ Gagal: {$result['message']}");
                    $hasError = true;
                }
            } else {
                $results = $syncService->syncAll($pId);
                if ($results['status'] === 'success') {
                    $this->info("✓ Sukses menyinkronkan seluruh Sheet:");
                    $this->line("  - Progress & Revenue: " . ($results['progress_and_revenue']['rows'] ?? 0) . " baris");
                    $this->line("  - Procurement: " . ($results['procurement']['rows'] ?? 0) . " baris");
                } else {
                    $this->warn("Sinkronisasi selesai dengan beberapa catatan:");
                    if (!($results['progress_and_revenue']['success'] ?? false)) {
                        $this->error("  - Progress & Revenue Gagal: " . ($results['progress_and_revenue']['message'] ?? ''));
                        $hasError = true;
                    }
                    if (!($results['procurement']['success'] ?? false)) {
                        $this->error("  - Procurement Gagal: " . ($results['procurement']['message'] ?? ''));
                        $hasError = true;
                    }
                }
            }
        }

        return $hasError ? Command::FAILURE : Command::SUCCESS;
    }
}