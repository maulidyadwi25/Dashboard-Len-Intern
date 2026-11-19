<?php

namespace App\Http\Controllers;

use App\Models\MasterProject;
use App\Models\WbsItem;
use App\Models\WbsProgressChangeLog;
use App\Models\WbsProgressChangeRequest;
use App\Models\WbsProgressEntry;
use App\Support\WbsDateHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WbsProgressChangeRequestController extends Controller
{
    public function store(Request $request, int|string $id, string $wbsNo): JsonResponse
    {
        $validated = $request->validate([
            'percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);
        $project = MasterProject::query()->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $user = $request->user();
        $weekInfo = WbsDateHelper::resolveWeekInfo(now('Asia/Jakarta'));

        $result = DB::transaction(function () use ($project, $wbsNo, $id, $weekInfo, $validated, $user) {
            $item = $project->wbsItems()
                ->where('wbs_no', $wbsNo)
                ->lockForUpdate()
                ->first();

            if (! $item instanceof WbsItem) {
                return response()->json([
                    'status' => 'error',
                    'message' => "WBS item [{$wbsNo}] not found for project [{$id}].",
                ], 404);
            }

            if ($project->wbsItems()->where('parent_wbs_no', $item->wbs_no)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "WBS item [{$wbsNo}] is not a leaf item and cannot receive progress input.",
                ], 422);
            }

            $latestEntry = WbsProgressEntry::query()
                ->where('master_project_id', $project->id)
                ->where('wbs_no', $wbsNo)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->orderByDesc('week')
                ->lockForUpdate()
                ->first();
            $previousPercent = $latestEntry ? (float) $latestEntry->percent : null;

            if ($previousPercent === null || (float) $validated['percent'] >= $previousPercent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengajuan hanya dapat dibuat untuk pengurangan dari progres terbaru.',
                    'errors' => ['percent' => ['Nilai pengajuan harus lebih kecil dari progres terbaru.']],
                ], 422);
            }

            $hasPendingRequest = WbsProgressChangeRequest::query()
                ->where('master_project_id', $project->id)
                ->where('wbs_no', $wbsNo)
                ->where('status', 'pending')
                ->exists();
            if ($hasPendingRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengajuan perubahan untuk task ini masih menunggu persetujuan VP.',
                ], 409);
            }

            return WbsProgressChangeRequest::query()->create([
                'master_project_id' => $project->id,
                'requested_by' => $user->id,
                'requester_name' => $user->name,
                'wbs_no' => $wbsNo,
                'year' => $weekInfo['year'],
                'month' => $weekInfo['month'],
                'week' => $weekInfo['week'],
                'previous_percent' => $previousPercent,
                'requested_percent' => (float) $validated['percent'],
                'comment' => trim($validated['comment']),
                'status' => 'pending',
            ]);
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->requestData($result),
        ], 201);
    }

    public function index(Request $request, int|string $id): JsonResponse
    {
        $project = MasterProject::query()->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $statusFilter = $request->query('status', 'pending');
        $isReviewed = in_array($statusFilter, ['reviewed', 'approved', 'rejected'], true);

        $query = WbsProgressChangeRequest::query()
            ->leftJoin('progress_wbs_items', function ($join) {
                $join->on('progress_wbs_items.master_project_id', '=', 'wbs_progress_change_requests.master_project_id')
                    ->on('progress_wbs_items.wbs_no', '=', 'wbs_progress_change_requests.wbs_no');
            })
            ->where('wbs_progress_change_requests.master_project_id', $project->id);

        if ($isReviewed) {
            $query->leftJoin('users as reviewers', 'reviewers.id', '=', 'wbs_progress_change_requests.reviewed_by');

            if ($statusFilter === 'approved') {
                $query->where('wbs_progress_change_requests.status', 'approved');
            } elseif ($statusFilter === 'rejected') {
                $query->where('wbs_progress_change_requests.status', 'rejected');
            } else {
                $query->whereIn('wbs_progress_change_requests.status', ['approved', 'rejected']);
            }

            $query->orderByDesc('wbs_progress_change_requests.reviewed_at')
                ->orderByDesc('wbs_progress_change_requests.id');
        } else {
            $query->where('wbs_progress_change_requests.status', 'pending')
                ->orderBy('wbs_progress_change_requests.created_at')
                ->orderBy('wbs_progress_change_requests.id');
        }

        $columns = [
            'wbs_progress_change_requests.*',
            'progress_wbs_items.activity_name',
            'progress_wbs_items.bobot_plan',
            'progress_wbs_items.status as wbs_status',
        ];
        if ($isReviewed) {
            $columns[] = 'reviewers.name as reviewer_name';
        }

        $requests = $query->paginate(20, $columns);

        $pendingCount = WbsProgressChangeRequest::query()
            ->where('master_project_id', $project->id)
            ->where('status', 'pending')
            ->count();
        $approvedCount = WbsProgressChangeRequest::query()
            ->where('master_project_id', $project->id)
            ->where('status', 'approved')
            ->count();
        $rejectedCount = WbsProgressChangeRequest::query()
            ->where('master_project_id', $project->id)
            ->where('status', 'rejected')
            ->count();
        $directLogsCount = WbsProgressChangeLog::query()
            ->where('master_project_id', $project->id)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => collect($requests->items())->map(fn (WbsProgressChangeRequest $changeRequest) => [
                ...$this->requestData($changeRequest),
                'activity_name' => $changeRequest->getAttribute('activity_name'),
                'wbs_status' => $changeRequest->getAttribute('wbs_status') ?? 'on_track',
                'reviewer_name' => $changeRequest->getAttribute('reviewer_name'),
            ])->values(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
                'counts' => [
                    'pending' => $pendingCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                    'reviewed' => $approvedCount + $rejectedCount,
                    'direct_logs' => $directLogsCount,
                ],
            ],
        ]);
    }

    public function mine(Request $request, int|string $id): JsonResponse
    {
        $project = MasterProject::query()->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $statusFilter = $request->query('status', 'all');
        if (! in_array($statusFilter, ['all', 'pending', 'approved', 'rejected'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status pengajuan tidak valid.',
            ], 422);
        }

        $query = WbsProgressChangeRequest::query()
            ->leftJoin('progress_wbs_items', function ($join) {
                $join->on('progress_wbs_items.master_project_id', '=', 'wbs_progress_change_requests.master_project_id')
                    ->on('progress_wbs_items.wbs_no', '=', 'wbs_progress_change_requests.wbs_no');
            })
            ->where('wbs_progress_change_requests.master_project_id', $project->id)
            ->where('wbs_progress_change_requests.requested_by', $request->user()->id);

        if ($statusFilter !== 'all') {
            $query->where('wbs_progress_change_requests.status', $statusFilter);
        }

        $requests = $query
            ->orderByRaw("CASE WHEN wbs_progress_change_requests.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('wbs_progress_change_requests.created_at')
            ->orderByDesc('wbs_progress_change_requests.id')
            ->paginate(10, [
                'wbs_progress_change_requests.*',
                'progress_wbs_items.activity_name',
            ]);

        $counts = WbsProgressChangeRequest::query()
            ->where('master_project_id', $project->id)
            ->where('requested_by', $request->user()->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return response()->json([
            'status' => 'success',
            'data' => collect($requests->items())->map(fn (WbsProgressChangeRequest $changeRequest) => [
                ...$this->requestData($changeRequest),
                'activity_name' => $changeRequest->getAttribute('activity_name'),
            ])->values(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
                'counts' => [
                    'pending' => (int) ($counts['pending'] ?? 0),
                    'approved' => (int) ($counts['approved'] ?? 0),
                    'rejected' => (int) ($counts['rejected'] ?? 0),
                ],
            ],
        ]);
    }

    public function review(Request $request, int|string $id, int|string $requestId): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
        ]);
        $project = MasterProject::query()->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $result = DB::transaction(function () use ($project, $requestId, $validated, $request) {
            $changeRequest = WbsProgressChangeRequest::query()
                ->where('master_project_id', $project->id)
                ->whereKey($requestId)
                ->lockForUpdate()
                ->first();

            if (! $changeRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengajuan perubahan progres tidak ditemukan.',
                ], 404);
            }

            if ($changeRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengajuan ini sudah ditinjau.',
                ], 409);
            }

            if ($validated['decision'] === 'approved') {
                $latestEntry = WbsProgressEntry::query()
                    ->where('master_project_id', $project->id)
                    ->where('wbs_no', $changeRequest->wbs_no)
                    ->orderByDesc('year')
                    ->orderByDesc('month')
                    ->orderByDesc('week')
                    ->lockForUpdate()
                    ->first();
                $latestPercent = $latestEntry ? (float) $latestEntry->percent : null;

                if ($latestPercent !== $changeRequest->previous_percent) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Progres task berubah sejak pengajuan dibuat. Perubahan tidak diterapkan.',
                    ], 409);
                }

                WbsProgressEntry::query()->updateOrCreate(
                    [
                        'master_project_id' => $project->id,
                        'wbs_no' => $changeRequest->wbs_no,
                        'year' => $changeRequest->year,
                        'month' => $changeRequest->month,
                        'week' => $changeRequest->week,
                    ],
                    ['percent' => $changeRequest->requested_percent],
                );

                $approver = $request->user();
                WbsProgressChangeLog::query()->create([
                    'master_project_id' => $project->id,
                    'user_id' => $approver->id,
                    'actor_name' => $approver->name,
                    'actor_role' => $approver->role,
                    'wbs_no' => $changeRequest->wbs_no,
                    'year' => $changeRequest->year,
                    'month' => $changeRequest->month,
                    'week' => $changeRequest->week,
                    'previous_percent' => $changeRequest->previous_percent,
                    'new_percent' => $changeRequest->requested_percent,
                    'comment' => "Pengajuan oleh {$changeRequest->requester_name}: {$changeRequest->comment}",
                ]);
            }

            $changeRequest->update([
                'status' => $validated['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            return $changeRequest->fresh();
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->requestData($result),
        ]);
    }

    /** @return array<string, mixed> */
    private function requestData(WbsProgressChangeRequest $changeRequest): array
    {
        $bobotPlan = $changeRequest->getAttribute('bobot_plan');
        $baselinePercent = null;
        if ($bobotPlan !== null && (float) $bobotPlan > 0) {
            $val = (float) $bobotPlan;
            $baselinePercent = $val <= 1.0 ? round($val * 100, 2) : round($val, 2);
        }

        return [
            'id' => $changeRequest->id,
            'wbs_no' => $changeRequest->wbs_no,
            'requester_name' => $changeRequest->requester_name,
            'year' => (int) $changeRequest->year,
            'month' => (int) $changeRequest->month,
            'week' => (int) $changeRequest->week,
            'previous_percent' => $changeRequest->previous_percent !== null ? (float) $changeRequest->previous_percent : null,
            'requested_percent' => $changeRequest->requested_percent !== null ? (float) $changeRequest->requested_percent : null,
            'baseline_percent' => $baselinePercent,
            'wbs_status' => $changeRequest->getAttribute('wbs_status') ?? 'on_track',
            'comment' => $changeRequest->comment,
            'status' => $changeRequest->status,
            'created_at' => $changeRequest->created_at?->toIso8601String(),
            'reviewed_at' => $changeRequest->reviewed_at?->toIso8601String(),
        ];
    }
}
