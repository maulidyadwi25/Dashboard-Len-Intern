<?php

namespace App\Http\Controllers;

use App\Http\Resources\WbsItemResource;
use App\Models\MasterProject;
use App\Models\WbsAiSummary;
use App\Models\WbsItem;
use App\Models\WbsProgressEntry;
use App\Models\WbsWeeklyValue;
use App\Services\GoogleSheetsService;
use App\Services\WbsAiSummaryService;
use App\Support\WbsDateHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class WbsController extends Controller
{
    /**
     * Get the WBS tree with rollup summary metrics for a MasterProject.
     */
    public function show(int|string $id): JsonResponse
    {
        $project = MasterProject::with('wbsItems')->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $items = $project->wbsItems->sortBy('order_index')->values();

        $items = $this->hydrateDerivedMetrics($items, $project->wbsProgressEntries()->orderBy('year')->orderBy('month')->orderBy('week')->get()->groupBy('wbs_no'), $this->resolveYear($project));

        return response()->json([
            'status' => 'success',
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'project_code' => $project->project_code,
                    'project_name' => $project->project_name,
                ],
                'year' => $this->resolveYear($project),
                'summary' => $this->summarize($items),
                'items' => WbsItemResource::collection($items)->resolve(),
            ],
        ], 200);
    }

    /**
     * Get flat weekly values for the Gantt grid.
     *
     * Supports ?month=1..12 filtering. Invalid month values are silently
     * ignored, mirroring ProgressRevenueResource::resolveMonthFilter().
     */
    public function weekly(Request $request, int|string $id, GoogleSheetsService $sheets): JsonResponse
    {
        $project = MasterProject::find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $year = $this->resolveYear($project);
        $month = $this->resolveMonthFilter($request);

        $periods = collect($sheets->wbsWeekPeriods($year))
            ->when($month !== null, fn (Collection $periods) => $periods->where('month', $month)->values())
            ->all();

        $values = WbsWeeklyValue::query()
            ->join('progress_wbs_items', 'progress_wbs_items.id', '=', 'progress_wbs_weekly_values.progress_wbs_item_id')
            ->where('progress_wbs_items.master_project_id', $project->id)
            ->when($month !== null, fn ($query) => $query->where('progress_wbs_weekly_values.month', $month))
            ->orderBy('progress_wbs_items.order_index')
            ->orderBy('progress_wbs_weekly_values.type')
            ->orderBy('progress_wbs_weekly_values.month')
            ->orderBy('progress_wbs_weekly_values.week')
            ->get([
                'progress_wbs_items.wbs_no',
                'progress_wbs_weekly_values.type',
                'progress_wbs_weekly_values.year',
                'progress_wbs_weekly_values.month',
                'progress_wbs_weekly_values.week',
                'progress_wbs_weekly_values.period_label',
                'progress_wbs_weekly_values.value',
            ])
            ->map(fn ($row) => [
                'wbs_no' => $row->wbs_no,
                'type' => $row->type,
                'year' => (int) $row->year,
                'month' => (int) $row->month,
                'week' => (int) $row->week,
                'period_label' => $row->period_label,
                'value' => $row->value === null ? null : (float) $row->value,
            ])->values();

        $progressRows = WbsProgressEntry::query()
            ->where('master_project_id', $project->id)
            ->when($month !== null, fn ($query) => $query->where('month', $month))
            ->orderBy('wbs_no')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('week')
            ->get()
            ->map(fn (WbsProgressEntry $entry) => [
                'wbs_no' => $entry->wbs_no,
                'type' => 'progress',
                'year' => (int) $entry->year,
                'month' => (int) $entry->month,
                'week' => (int) $entry->week,
                'period_label' => WbsDateHelper::periodLabel((int) $entry->month, (int) $entry->week),
                'value' => (float) $entry->percent,
            ])->values();

        $mergedValues = collect($values->all())
            ->merge($progressRows->all())
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'year' => $year,
                'periods' => $periods,
                'values' => $mergedValues,
            ],
        ], 200);
    }

    /**
     * Trigger WBS sync for a single MasterProject.
     */
    public function sync(int|string $id, GoogleSheetsService $sheets): JsonResponse
    {
        $project = MasterProject::find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $sheets->syncWbsData($project),
        ], 200);
    }

    public function weekSummary(int|string $id): JsonResponse
    {
        $project = MasterProject::find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->buildWeekSummaryData($project),
        ], 200);
    }

    public function getAiSummary(int|string $id): JsonResponse
    {
        $project = MasterProject::with('wbsAiSummaries')->find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $summary = $project->wbsAiSummaries()->orderByDesc('summary_date')->first();

        return response()->json([
            'status' => 'success',
            'data' => $summary ? [
                'content' => $summary->content,
                'source' => $summary->source,
                'summary_date' => $summary->summary_date->toDateString(),
                'generated_at' => $summary->generated_at?->toIso8601String(),
            ] : null,
        ], 200);
    }

    public function generateAiSummary(Request $request, int|string $id, GoogleSheetsService $sheets, WbsAiSummaryService $service): JsonResponse
    {
        $project = MasterProject::find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $key = "wbs-ai-summary-project:{$id}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests. Please wait a moment before generating again.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $lock = Cache::lock($key, 10);
        if (! $lock->get()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Another AI summary generation is already running for this project.',
            ], 409);
        }

        try {
            $syncWarning = null;

            try {
                $sheets->syncWbsData($project);
            } catch (\Throwable $e) {
                $syncWarning = 'Google Sheets sync failed; using latest saved WBS data.';
                Log::warning('[WBS AI Summary] Sync warning', ['project_id' => $project->id, 'message' => $e->getMessage()]);
            }

            $result = $service->generateForProject($project, true);

            if ($syncWarning !== null) {
                $result['sync_warning'] = $syncWarning;
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            $existing = $project->wbsAiSummaries()
                ->whereDate('summary_date', now('Asia/Jakarta')->toDateString())
                ->latest('generated_at')
                ->first();

            return response()->json([
                'status' => 'error',
                'message' => 'AI summary generation failed. Please try again later.',
                'previous_summary' => $existing ? [
                    'content' => $existing->content,
                    'source' => $existing->source,
                    'summary_date' => $existing->summary_date->toDateString(),
                    'generated_at' => $existing->generated_at?->toIso8601String(),
                ] : null,
            ], 502);
        } finally {
            $lock->release();
            RateLimiter::clear($key);
        }
    }

    public function updateAiSummary(Request $request, int|string $id, WbsAiSummaryService $service): JsonResponse
    {
        $project = MasterProject::find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        try {
            $validated = $service->validateSummaryStructure($request->input('content'));
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        $summaryDate = now('Asia/Jakarta')->toDateString();
        $summary = WbsAiSummary::query()
            ->where('master_project_id', $project->id)
            ->whereDate('summary_date', $summaryDate)
            ->first();

        if ($summary) {
            $summary->fill([
                'content' => $validated,
                'source' => 'manual',
                'generated_at' => now('Asia/Jakarta'),
            ]);
            $summary->save();
        } else {
            $summary = WbsAiSummary::create([
                'master_project_id' => $project->id,
                'summary_date' => $summaryDate,
                'content' => $validated,
                'source' => 'manual',
                'generated_at' => now('Asia/Jakarta'),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'content' => $summary->content,
                'source' => $summary->source,
                'summary_date' => $summary->summary_date->toDateString(),
                'generated_at' => $summary->generated_at?->toIso8601String(),
            ],
        ], 200);
    }

    public function updateProgress(Request $request, int|string $id, string $wbsNo): JsonResponse
    {
        $project = MasterProject::find($id);

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => "MasterProject with ID [{$id}] not found.",
            ], 404);
        }

        $item = $project->wbsItems()->where('wbs_no', $wbsNo)->first();

        if (! $item) {
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

        $percent = $request->input('percent');

        if (! is_numeric($percent) || (float) $percent < 0 || (float) $percent > 100) {
            return response()->json([
                'status' => 'error',
                'message' => 'Percent must be a numeric value between 0 and 100.',
            ], 422);
        }

        $hasCompletedEntry = $project->wbsProgressEntries()
            ->where('wbs_no', $wbsNo)
            ->where('percent', 100)
            ->exists();

        if ($hasCompletedEntry) {
            return response()->json([
                'status' => 'error',
                'message' => "WBS item [{$wbsNo}] already has a 100% progress entry and cannot receive additional input.",
            ], 422);
        }

        $weekInfo = WbsDateHelper::resolveWeekInfo(now('Asia/Jakarta'));

        $entry = WbsProgressEntry::query()->updateOrCreate(
            [
                'master_project_id' => $project->id,
                'wbs_no' => $wbsNo,
                'year' => $weekInfo['year'],
                'month' => $weekInfo['month'],
                'week' => $weekInfo['week'],
            ],
            [
                'percent' => (float) $percent,
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'project_id' => $project->id,
                'wbs_no' => $entry->wbs_no,
                'year' => $entry->year,
                'month' => $entry->month,
                'week' => $entry->week,
                'percent' => (float) $entry->percent,
                'latest_progress_percent' => $this->resolveLatestProgressPercent($project, $wbsNo),
                'bobot_real' => $this->resolveLeafBobotReal($item, $project, $wbsNo),
                'status' => $this->resolveLeafStatusForItem($item, $project, $wbsNo),
            ],
        ], 200);
    }

    /**
     * Rollup metrics computed from leaf items only, so parents are not
     * counted twice.
     *
     * @param  Collection<int, WbsItem>  $items
     * @return array<string, float|int>
     */
    private function summarize(Collection $items): array
    {
        $parentWbsNumbers = $items->pluck('parent_wbs_no')->filter()->flip();
        $leaves = $items->reject(fn (WbsItem $item) => $parentWbsNumbers->has($item->wbs_no));

        $totalBobotPlan = (float) $leaves->sum(fn (WbsItem $item) => (float) $item->bobot_plan);
        $totalBobotReal = (float) $leaves->sum(fn (WbsItem $item) => (float) $item->bobot_real);

        return [
            'total_items' => $leaves->count(),
            'total_on_track' => $leaves->where('status', 'on_track')->count(),
            'total_overdue' => $leaves->where('status', 'overdue')->count(),
            'total_critical' => $leaves->where('status', 'critical')->count(),
            'total_not_started' => $leaves->where('status', 'not_started')->count(),
            'total_bobot_plan' => round($totalBobotPlan, 4),
            'total_bobot_real' => round($totalBobotReal, 4),
            // Capaian bobot. Asumsi baru: ProgressRevenueResource tidak punya
            // rumus progres agregat, jadi ini bukan replikasi resource itu.
            'progres' => $totalBobotPlan > 0
                ? round($totalBobotReal / $totalBobotPlan, 4)
                : 0.0,
        ];
    }

    private function resolveYear(MasterProject $project): int
    {
        return $project->progress_revenue_year !== null
            ? (int) $project->progress_revenue_year
            : (int) now()->year;
    }

    private function hydrateDerivedMetrics(Collection $items, Collection $entriesByWbsNo, int $year): Collection
    {
        $childrenByParent = $items->groupBy('parent_wbs_no');
        $parentWbsNumbers = $items->pluck('parent_wbs_no')->filter()->flip();

        $items = $items->map(function (WbsItem $item) use ($entriesByWbsNo, $year, $parentWbsNumbers) {
            if ($parentWbsNumbers->has($item->wbs_no)) {
                return $item;
            }

            $entries = $entriesByWbsNo->get($item->wbs_no, collect())
                ->sortBy(fn (WbsProgressEntry $entry) => WbsDateHelper::periodOrdinal((int) $entry->year, (int) $entry->month, (int) $entry->week));

            $leafMetrics = $this->resolveLeafMetrics($item, $entries, $year);

            foreach ($leafMetrics as $key => $value) {
                $item->{$key} = $value;
            }

            return $item;
        });

        $parents = $items
            ->filter(fn (WbsItem $item) => $parentWbsNumbers->has($item->wbs_no))
            ->sortByDesc('level');

        foreach ($parents as $item) {

            $children = $childrenByParent->get($item->wbs_no, collect());
            $directChildren = $children->reject(fn (WbsItem $child) => $parentWbsNumbers->has($child->wbs_no));
            $parentChildren = $children->filter(fn (WbsItem $child) => $parentWbsNumbers->has($child->wbs_no));

            $item->bobot_plan = (float) $directChildren->sum(fn (WbsItem $child) => (float) $child->bobot_plan);
            $item->bobot_real = (float) $directChildren->sum(fn (WbsItem $child) => (float) $child->bobot_real);
            $item->bobot_plan += (float) $parentChildren->sum(fn (WbsItem $child) => (float) $child->bobot_plan);
            $item->bobot_real += (float) $parentChildren->sum(fn (WbsItem $child) => (float) $child->bobot_real);
            $item->progress_sum = (float) $directChildren->sum(fn (WbsItem $child) => (float) ($child->latest_progress_percent ?? 0))
                + (float) $parentChildren->sum(fn (WbsItem $child) => (float) ($child->progress_sum ?? 0));
            $item->actual_start_week = $children->map(fn (WbsItem $child) => $child->actual_start_week ?? PHP_INT_MAX)
                ->filter(fn ($value) => $value !== PHP_INT_MAX)
                ->min();
            $item->actual_finish_week = $children->map(fn (WbsItem $child) => $child->actual_finish_week ?? 0)
                ->filter(fn ($value) => $value > 0)
                ->max();
            $childSources = $children->pluck('actual_source')->filter()->unique()->values();
            $item->actual_source = $childSources->contains('mixed') || $childSources->count() > 1
                ? 'mixed'
                : $childSources->first();
            $item->status = null;

            if ($item->actual_start_week === null) {
                $item->actual_start_week = null;
            }

            if ($item->actual_finish_week === null || $item->actual_finish_week === 0) {
                $item->actual_finish_week = null;
            }
        }

        return $items;
    }

    private function resolveLeafMetrics(WbsItem $item, Collection $entries, int $year): array
    {
        $latestProgress = $entries->last();
        $latestPercent = $latestProgress ? (float) $latestProgress->percent : null;
        $bobotReal = $latestPercent === null ? 0.0 : ((float) $item->bobot_plan * ($latestPercent / 100));

        $sheetStartOrdinal = $item->actual_start_period
            ? WbsDateHelper::periodOrdinalFromLabel((string) $item->actual_start_period, $year)
            : null;
        $sheetFinishOrdinal = $item->actual_end_period
            ? WbsDateHelper::periodOrdinalFromLabel((string) $item->actual_end_period, $year)
            : null;
        $webStartOrdinal = $entries->isNotEmpty()
            ? WbsDateHelper::periodOrdinal(
                (int) $entries->first()->year,
                (int) $entries->first()->month,
                (int) $entries->first()->week,
            )
            : null;
        $completedEntry = $entries->first(fn (WbsProgressEntry $entry) => (float) $entry->percent >= 100.0);
        $webFinishOrdinal = $completedEntry
            ? WbsDateHelper::periodOrdinal(
                (int) $completedEntry->year,
                (int) $completedEntry->month,
                (int) $completedEntry->week,
            )
            : null;

        if ($entries->isEmpty()) {
            $actualStartOrdinal = $sheetStartOrdinal;
            $actualFinishOrdinal = $sheetFinishOrdinal;
            $actualSource = $sheetStartOrdinal !== null || $sheetFinishOrdinal !== null
                ? 'sheet'
                : null;
        } else {
            $actualStartOrdinal = collect([$sheetStartOrdinal, $webStartOrdinal])
                ->filter(fn (?int $ordinal) => $ordinal !== null)
                ->min();
            $actualFinishOrdinal = $webFinishOrdinal;
            $sheetProvidesStart = $sheetStartOrdinal !== null
                && ($webStartOrdinal === null || $sheetStartOrdinal <= $webStartOrdinal);
            $actualSource = $sheetProvidesStart && $webFinishOrdinal !== null
                ? 'mixed'
                : ($sheetProvidesStart ? 'sheet' : 'web');
        }

        $actualStartWeek = $actualStartOrdinal !== null
            ? $actualStartOrdinal - ($year * 48)
            : null;
        $actualFinishWeek = $actualFinishOrdinal !== null
            ? $actualFinishOrdinal - ($year * 48)
            : null;

        $planEndOrdinal = $this->resolvePlanEndOrdinal((string) $item->plan_end_period, $year);
        $currentWeekOrdinal = $this->resolveCurrentWeekOrdinal($year);
        $status = $this->resolveDerivedLeafStatus($actualStartWeek, $planEndOrdinal, $currentWeekOrdinal, $actualFinishWeek);

        return [
            'latest_progress_percent' => $latestPercent,
            'bobot_real' => round($bobotReal, 4),
            'actual_start_week' => $actualStartWeek,
            'actual_finish_week' => $actualFinishWeek,
            'actual_source' => $actualSource,
            'status' => $status,
        ];
    }

    private function resolveLeafStatusForItem(WbsItem $item, MasterProject $project, string $wbsNo): string
    {
        $entries = $project->wbsProgressEntries()->where('wbs_no', $wbsNo)->orderBy('year')->orderBy('month')->orderBy('week')->get();
        $metrics = $this->resolveLeafMetrics($item, $entries, $this->resolveYear($project));

        return $metrics['status'];
    }

    private function resolveLatestProgressPercent(MasterProject $project, string $wbsNo): ?float
    {
        $entry = $project->wbsProgressEntries()->where('wbs_no', $wbsNo)->orderBy('year')->orderBy('month')->orderBy('week')->latest()->first();

        return $entry ? (float) $entry->percent : null;
    }

    private function resolveLeafBobotReal(WbsItem $item, MasterProject $project, string $wbsNo): float
    {
        $latestPercent = $this->resolveLatestProgressPercent($project, $wbsNo);

        if ($latestPercent === null) {
            return 0.0;
        }

        return round(((float) $item->bobot_plan * ($latestPercent / 100)), 4);
    }

    private function resolveDerivedLeafStatus(?int $actualStartWeek, ?int $planEndOrdinal, int $currentWeekOrdinal, ?int $actualFinishWeek): string
    {
        if ($actualStartWeek === null) {
            if ($planEndOrdinal === null || $currentWeekOrdinal <= $planEndOrdinal) {
                return 'not_started';
            }

            $delay = $currentWeekOrdinal - $planEndOrdinal;

            return $delay > 1 ? 'critical' : 'overdue';
        }

        if ($actualFinishWeek !== null) {
            $delay = $actualFinishWeek - ($planEndOrdinal ?? $actualFinishWeek);

            if ($delay <= 0) {
                return 'on_track';
            }

            return $delay > 1 ? 'critical' : 'overdue';
        }

        if ($planEndOrdinal !== null && $currentWeekOrdinal > $planEndOrdinal) {
            $delay = $currentWeekOrdinal - $planEndOrdinal;

            return $delay > 1 ? 'critical' : 'overdue';
        }

        return 'on_track';
    }

    private function resolvePlanEndOrdinal(?string $planEndPeriod, int $year): ?int
    {
        if ($planEndPeriod === null || trim((string) $planEndPeriod) === '') {
            return null;
        }

        $absolute = WbsDateHelper::periodOrdinalFromLabel((string) $planEndPeriod, $year);

        if ($absolute === null) {
            return null;
        }

        return $absolute - ($year * 48);
    }

    private function resolveCurrentWeekOrdinal(int $year): int
    {
        $info = WbsDateHelper::resolveWeekInfo(now('Asia/Jakarta'));

        return WbsDateHelper::periodOrdinal($year, (int) $info['month'], (int) $info['week']) - ($year * 48);
    }

    /**
     * month=all returns null (no filtering); 1..12 filters. Out-of-range and
     * malformed values fall back to no filtering.
     */
    private function buildWeekSummaryData(MasterProject $project): array
    {
        $items = $project->wbsItems()->orderBy('order_index')->get();
        $entriesByWbsNo = $project->wbsProgressEntries()
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('week')
            ->get()
            ->groupBy('wbs_no');

        $hydrated = $this->hydrateDerivedMetrics(
            $items,
            $entriesByWbsNo,
            $this->resolveYear($project),
        );

        $year = $this->resolveYear($project);
        $weekInfo = WbsDateHelper::resolveWeekInfo(now('Asia/Jakarta'));
        $currentOrdinal = WbsDateHelper::periodOrdinal($year, (int) $weekInfo['month'], (int) $weekInfo['week']) - ($year * 48);
        $parentWbsNumbers = $items->pluck('parent_wbs_no')->filter()->flip();

        $activeItems = $hydrated
            ->reject(fn (WbsItem $item) => $parentWbsNumbers->has($item->wbs_no))
            ->filter(fn (WbsItem $item) => $this->isWeekActiveLeaf($item, $currentOrdinal, $year))
            ->values();

        $rows = $activeItems->map(function (WbsItem $item) use ($currentOrdinal, $year) {
            $latestProgress = (float) ($item->latest_progress_percent ?? 0.0);
            $plannedPercent = $this->resolveLeafPlannedPercent($item, $year, $currentOrdinal);

            return [
                'wbs_no' => $item->wbs_no,
                'activity_name' => $item->activity_name ?? $item->wbs_no,
                'status' => $item->status ?? 'not_started',
                'latest_progress_percent' => round($latestProgress, 2),
                'bobot_plan' => (float) $item->bobot_plan,
                'planned_percent' => round($plannedPercent, 2),
                'deviation_percent' => round($latestProgress - $plannedPercent, 2),
            ];
        })->sortByDesc(fn (array $row) => abs((float) $row['deviation_percent']))->values()->all();

        return [
            'project_id' => $project->id,
            'project_name' => $project->project_name,
            'year' => $year,
            'week_key' => $year.'-'.(string) $weekInfo['month'].'-'.(string) $weekInfo['week'],
            'week_label' => WbsDateHelper::periodLabel((int) $weekInfo['month'], (int) $weekInfo['week']),
            'items' => $rows,
        ];
    }

    private function isWeekActiveLeaf(WbsItem $item, int $currentOrdinal, int $year): bool
    {
        $actualStartOrdinal = $item->actual_start_period !== null
            ? WbsDateHelper::periodOrdinalFromLabel((string) $item->actual_start_period, $year)
            : null;
        $actualEndOrdinal = $item->actual_end_period !== null
            ? WbsDateHelper::periodOrdinalFromLabel((string) $item->actual_end_period, $year)
            : null;
        $planEndOrdinal = $item->plan_end_period !== null
            ? WbsDateHelper::periodOrdinalFromLabel((string) $item->plan_end_period, $year)
            : null;
        $progress = (float) ($item->latest_progress_percent ?? 0.0);

        if ($actualStartOrdinal !== null) {
            $withinActualRange = $currentOrdinal >= ($actualStartOrdinal - ($year * 48))
                && ($actualEndOrdinal === null || $currentOrdinal <= ($actualEndOrdinal - ($year * 48)));

            if ($withinActualRange) {
                return true;
            }
        }

        if ($planEndOrdinal !== null) {
            $planEndRelative = $planEndOrdinal - ($year * 48);

            if ($currentOrdinal <= $planEndRelative) {
                return true;
            }

            if ($currentOrdinal > $planEndRelative && $progress < 100.0) {
                return true;
            }
        }

        return false;
    }

    private function resolveLeafPlannedPercent(WbsItem $item, int $year, int $currentWeekOrdinal): float
    {
        $values = WbsWeeklyValue::query()
            ->where('progress_wbs_item_id', $item->id)
            ->where('type', 'plan')
            ->get();

        $total = (float) $values->sum('value');
        if ($total <= 0) {
            return 0.0;
        }

        $accumulated = (float) $values->filter(function (WbsWeeklyValue $value) use ($year, $currentWeekOrdinal) {
            $ordinal = WbsDateHelper::periodOrdinal((int) $value->year, (int) $value->month, (int) $value->week) - ($year * 48);

            return $ordinal <= $currentWeekOrdinal;
        })->sum('value');

        return ($accumulated / $total) * 100;
    }

    private function resolveMonthFilter(Request $request): ?int
    {
        $month = $request->query('month', 'all');

        if ($month === 'all' || $month === null || $month === '') {
            return null;
        }

        $month = (int) $month;

        if ($month < 1 || $month > 12) {
            return null;
        }

        return $month;
    }
}
