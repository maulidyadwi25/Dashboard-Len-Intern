<?php

namespace App\Console\Commands;

use App\Models\MasterProject;
use App\Models\WbsAiSummary;
use App\Services\WbsAiSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateWbsAiSummary extends Command
{
    protected $signature = 'wbs:generate-ai-summary {--project= : Project ID} {--force : Force generate even if a manual summary exists today}';

    protected $description = 'Generate daily AI summary for active WBS projects.';

    public function handle(WbsAiSummaryService $service): int
    {
        $projects = MasterProject::query()
            ->when($this->option('project'), fn ($query, $projectId) => $query->whereKey($projectId))
            ->where('is_active', true)
            ->get();

        if ($projects->isEmpty()) {
            $this->warn('Tidak ada project aktif yang memiliki WBS.');

            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            if (! $project->wbsItems()->exists()) {
                continue;
            }

            $summaryDate = now('Asia/Jakarta')->toDateString();
            $manualSummaryExists = WbsAiSummary::query()
                ->where('master_project_id', $project->id)
                ->whereDate('summary_date', $summaryDate)
                ->where('source', 'manual')
                ->exists();

            if ($manualSummaryExists && ! $this->option('force')) {
                $this->info("Melewati {$project->project_name}: sudah ada ringkasan manual hari ini.");

                continue;
            }

            try {
                $service->generateForProject($project, true);
                $this->info("Ringkasan AI berhasil dibuat untuk {$project->project_name}.");
            } catch (\Throwable $e) {
                Log::error('[WBS AI summary] generate failed for project', [
                    'project_id' => $project->id,
                    'message' => $e->getMessage(),
                ]);
                $this->warn("Gagal membuat ringkasan AI untuk {$project->project_name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
