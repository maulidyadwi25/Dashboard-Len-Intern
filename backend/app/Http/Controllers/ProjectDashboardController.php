<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectDashboardResource;
use App\Models\MasterProject;
use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;

class ProjectDashboardController extends Controller
{
    /**
     * Get aggregate dashboard for a MasterProject along with procurementItems and budgetBreakdowns.
     */
    public function show(int|string $id): JsonResponse
    {
        $project = MasterProject::with([
            'spreadsheets',
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
     * Trigger Multi-Spreadsheet synchronization for a MasterProject.
     */
    public function sync(int|string $id, GoogleSheetsService $sheetsService): JsonResponse
    {
        $project = MasterProject::with('spreadsheets')->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $syncResults = $sheetsService->syncProject($project);

        $project->load([
            'spreadsheets',
            'procurementItems',
            'budgetBreakdowns',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Multi-Spreadsheet sync completed.',
            'sync_results' => $syncResults,
            'data' => new ProjectDashboardResource($project),
        ], 200);
    }
}
