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
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'project_code', 'project_name']);

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
