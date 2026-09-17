<?php

namespace App\Console\Commands;

use App\Models\MasterProject;
use App\Services\GoogleSheetSyncService;
use Illuminate\Console\Command;

class AddProjectCommand extends Command
{
    protected $signature = 'project:add
                            {spreadsheet_id : ID Google Spreadsheet dari URL spreadsheet}
                            {--name= : Nama Project (opsional)}
                            {--code= : Kode Project (opsional)}
                            {--description= : Deskripsi (kolom ERD)}';

    protected $description = 'Mendaftarkan Master Project baru dan langsung menyinkronkan datanya';

    public function handle(GoogleSheetSyncService $syncService): int
    {
        $spreadsheetId = trim($this->argument('spreadsheet_id'));
        if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $spreadsheetId, $matches)) {
            $spreadsheetId = $matches[1];
        }
        if (empty($spreadsheetId)) {
            $this->error('Spreadsheet ID tidak valid!');
            return Command::FAILURE;
        }

        $existing = MasterProject::where('spreadsheet_id', $spreadsheetId)->first();
        if ($existing) {
            $this->warn("Project dengan Spreadsheet ID ini sudah ada [ID: {$existing->id} - {$existing->project_name}].");
            if ($this->confirm('Apakah Anda ingin menyinkronkan ulang data sekarang?', true)) {
                $syncService->syncAll($existing->id);
                $this->info('✓ Selesai diperbarui.');
            }
            return Command::SUCCESS;
        }

        $project = MasterProject::create([
            'project_name' => $this->option('name') ?: 'Proyek Baru (' . substr($spreadsheetId, 0, 8) . ')',
            'project_code' => $this->option('code') ?: 'PRJ-' . time(),
            'spreadsheet_id' => $spreadsheetId,
            'description' => $this->option('description'),
            'last_synced_at' => now(),
        ]);

        $this->info("✓ Master Project berhasil didaftarkan [ID: {$project->id}]!");
        $syncService->syncAll($project->id);

        return Command::SUCCESS;
    }
}
