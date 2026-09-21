<?php

namespace App\Console\Commands;

use App\Models\MasterProject;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleSheet extends Command
{
    /**
     * Nama perintah yang dipanggil via terminal
     */
    protected $signature = 'app:sync-google-sheet';

    /**
     * Deskripsi singkat perintah
     */
    protected $description = 'Cek timestamp Google Sheet dan lakukan sinkronisasi ke database MySQL jika data usang';

    /**
     * Eksekusi perintah
     */
    public function handle(GoogleSheetsService $sheets): int
    {
        $this->info('Mengecek pembaruan Google Sheet...');

        try {
            $masterProjects = MasterProject::query()
                ->whereHas('spreadsheets', fn ($q) => $q->where('is_active', true))
                ->get();

            if ($masterProjects->isEmpty()) {
                $this->warn('Belum ada master project aktif. Menggunakan spreadsheet dari konfigurasi lama.');
                $sheets->syncSheetToDatabase();
            } else {
                foreach ($masterProjects as $masterProject) {
                    $this->info("Memeriksa {$masterProject->project_name}...");
                    Log::info('[Cron] Sinkronisasi master project dimulai.', [
                        'master_project_id' => $masterProject->id,
                    ]);
                    $sheets->syncProject($masterProject);
                    $this->info('Sinkronisasi berhasil diselesaikan!');
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Gagal mengecek/sinkron Google Sheet: '.$e->getMessage());
            Log::error('[Cron] Gagal mengecek/sinkron Google Sheet: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
