<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectDashboardResource;
use App\Models\MasterProject;
use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;

class ProjectDashboardController extends Controller
{
    /**
     * List all active MasterProjects for the project selector.
     */
    public function index(): JsonResponse
    {
        $projects = MasterProject::query()
            ->with(['progressRevenueProfile'])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function (MasterProject $project) {
                $pr = $project->progressRevenueProfile;

                return [
                    'id' => $project->id,
                    'project_code' => $pr?->project_code ?: $project->project_code,
                    'project_name' => $pr?->project_name ?: $project->project_name,
                    'client_name' => $pr?->client_name ?: $project->client_name,
                    'pimpro' => $pr?->nama_pimpro ?: $project->leader_name,
                    'analyst' => $pr?->nama_PA ?: null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $projects,
        ], 200);
    }

    /**
     * Get aggregate dashboard for a MasterProject along with profile, procurementItems and budgetBreakdowns.
     */
    public function show(int|string $id): JsonResponse
    {
        $project = MasterProject::with([
            'profiles',
            'budgetProfile',
            'procurementProfile',
            'procurementItems',
            'budgetBreakdowns',
        ])->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new ProjectDashboardResource($project),
        ], 200);
    }

    /**
     * Trigger synchronization for a MasterProject.
     */
    public function sync(int|string $id, GoogleSheetsService $sheetsService): JsonResponse
    {
        $project = MasterProject::with(['profiles'])->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $syncResults = $sheetsService->syncProject($project);

        $project->load([
            'profiles',
            'budgetProfile',
            'procurementProfile',
            'procurementItems',
            'budgetBreakdowns',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Project spreadsheet sync completed.',
            'sync_results' => $syncResults,
            'data' => new ProjectDashboardResource($project),
        ], 200);
    }
}
