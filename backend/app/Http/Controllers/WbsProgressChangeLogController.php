<?php

namespace App\Http\Controllers;

use App\Models\MasterProject;
use App\Models\WbsProgressChangeLog;
use Illuminate\Http\JsonResponse;

class WbsProgressChangeLogController extends Controller
{
    public function index(int|string $id): JsonResponse
    {
        $project = MasterProject::query()->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $changes = WbsProgressChangeLog::query()
            ->leftJoin('progress_wbs_items', function ($join) {
                $join->on('progress_wbs_items.master_project_id', '=', 'wbs_progress_change_logs.master_project_id')
                    ->on('progress_wbs_items.wbs_no', '=', 'wbs_progress_change_logs.wbs_no');
            })
            ->where('wbs_progress_change_logs.master_project_id', $project->id)
            ->orderByDesc('wbs_progress_change_logs.created_at')
            ->orderByDesc('wbs_progress_change_logs.id')
            ->paginate(20, [
                'wbs_progress_change_logs.id',
                'wbs_progress_change_logs.actor_name',
                'wbs_progress_change_logs.actor_role',
                'wbs_progress_change_logs.wbs_no',
                'wbs_progress_change_logs.year',
                'wbs_progress_change_logs.month',
                'wbs_progress_change_logs.week',
                'wbs_progress_change_logs.previous_percent',
                'wbs_progress_change_logs.new_percent',
                'wbs_progress_change_logs.comment',
                'wbs_progress_change_logs.created_at',
                'progress_wbs_items.activity_name',
                'progress_wbs_items.status as wbs_status',
                'progress_wbs_items.bobot_plan',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => collect($changes->items())->map(function (WbsProgressChangeLog $change) {
                $bobotPlan = $change->getAttribute('bobot_plan');
                $baselinePercent = null;
                if ($bobotPlan !== null && (float) $bobotPlan > 0) {
                    $val = (float) $bobotPlan;
                    $baselinePercent = $val <= 1.0 ? round($val * 100, 2) : round($val, 2);
                }

                return [
                    'id' => $change->id,
                    'actor_name' => $change->actor_name,
                    'actor_role' => $change->actor_role,
                    'wbs_no' => $change->wbs_no,
                    'activity_name' => $change->getAttribute('activity_name'),
                    'wbs_status' => $change->getAttribute('wbs_status') ?? 'on_track',
                    'year' => (int) $change->year,
                    'month' => (int) $change->month,
                    'week' => (int) $change->week,
                    'previous_percent' => $change->previous_percent !== null ? (float) $change->previous_percent : null,
                    'new_percent' => $change->new_percent !== null ? (float) $change->new_percent : null,
                    'requested_percent' => $change->new_percent !== null ? (float) $change->new_percent : null,
                    'baseline_percent' => $baselinePercent,
                    'comment' => $change->comment,
                    'created_at' => $change->created_at?->toIso8601String(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $changes->currentPage(),
                'last_page' => $changes->lastPage(),
                'per_page' => $changes->perPage(),
                'total' => $changes->total(),
            ],
        ]);
    }
}
