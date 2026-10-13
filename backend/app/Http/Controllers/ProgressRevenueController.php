<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProgressRevenueResource;
use App\Models\MasterProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressRevenueController extends Controller
{
    /**
     * Get progress & revenue weekly indicators for a MasterProject.
     *
     * Supports ?month=all|1..12 filtering (backend-side).
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $project = MasterProject::with('progressRevenueProfile')->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new ProgressRevenueResource($project, $request),
        ], 200);
    }
}
