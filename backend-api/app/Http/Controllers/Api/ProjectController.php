<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GanttActivity;
use App\Models\Procurement;
use App\Models\MasterProject;
use App\Models\ProjectLink;
use App\Models\SystemSync;
use App\Services\CurrencyService;
use App\Services\GoogleSheetSyncService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected GoogleSheetSyncService $syncService;

    public function __construct(GoogleSheetSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function getProjects()
    {
        return response()->json([
            'status' => 'success',
            'data' => MasterProject::select('id', 'project_name', 'project_code', 'spreadsheet_id', 'description')->get(),
        ]);
    }

    protected function resolveProject(Request $request): ?MasterProject
    {
        $idParam = $request->query('id') ?? $request->query('project_id') ?? $request->query('master_project_id');
        if ($idParam) {
            if (is_numeric($idParam)) {
                $project = MasterProject::find($idParam);
                if ($project) {
                    return $project;
                }
            }
            $project = MasterProject::where('spreadsheet_id', $idParam)->first();
            if ($project) {
                return $project;
            }
        }

        return MasterProject::first();
    }

    public function getMonitoringData(Request $request)
    {
        try {
            $project = $this->resolveProject($request);
            $projectId = $project?->id;

            $query = ProjectLink::where('sheet_key', 'progress_revenue');
            // Fallback bila sheet_key null (data ERD): cari by sheet_name
            $record = null;
            if ($projectId) {
                $record = (clone $query)->where('master_project_id', $projectId)->first()
                    ?? ProjectLink::where('master_project_id', $projectId)
                        ->where('sheet_name', 'like', '%Progress%')->first();
            }

            if (! $record || empty($record->data) || $request->has('sync')) {
                $syncRes = $this->syncService->syncProgressAndRevenue($projectId);
                if (! $syncRes['success']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Gagal mengambil data dari Google Sheets API: ' . ($syncRes['message'] ?? ''),
                    ], 500);
                }
                $record = ProjectLink::where('master_project_id', $projectId)
                    ->where('sheet_key', 'progress_revenue')->first()
                    ?? ProjectLink::where('master_project_id', $projectId)->first();
            }

            return response()->json([
                'status' => 'success',
                'source' => 'sqlite_database',
                'project' => $project ? [
                    'id' => $project->id,
                    'project_name' => $project->project_name,
                    'project_code' => $project->project_code,
                    'spreadsheet_id' => $project->spreadsheet_id,
                    'description' => $project->description,
                ] : null,
                'data' => $record->data ?? [],
                'last_synced_at' => $record->last_synced_at?->toIso8601String(),
            ], 200)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server saat membaca database.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProcurementData(Request $request, CurrencyService $currencyService)
    {
        try {
            $project = $this->resolveProject($request);
            $projectId = $project?->id;

            $record = null;
            if ($projectId) {
                $record = ProjectLink::where('master_project_id', $projectId)
                    ->where('sheet_key', 'procurement')->first()
                    ?? ProjectLink::where('master_project_id', $projectId)
                        ->where('sheet_name', 'like', '%Procurement%')->first();
            }

            if (! $record || empty($record->data) || $request->has('sync')) {
                $syncRes = $this->syncService->syncProcurement($projectId);
                if (! $syncRes['success']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Gagal mengambil data dari Google Sheets API: ' . ($syncRes['message'] ?? ''),
                    ], 500);
                }
                $record = ProjectLink::where('master_project_id', $projectId)->first();
            }

            $data = $record->data ?? [];
            $exchangeRates = $currencyService->getExchangeRates();

            return response()->json([
                'status' => 'success',
                'source' => 'sqlite_database',
                'project' => $project ? [
                    'id' => $project->id,
                    'project_name' => $project->project_name,
                    'project_code' => $project->project_code,
                    'spreadsheet_id' => $project->spreadsheet_id,
                    'description' => $project->description,
                ] : null,
                'data' => $data,
                'exchange_rates' => $exchangeRates,
                'last_synced_at' => $record->last_synced_at?->toIso8601String(),
            ], 200)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server saat membaca database.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncSheets(Request $request)
    {
        $targetSheet = $request->input('sheet');
        $project = $this->resolveProject($request);
        $projectId = $project?->id;

        if ($targetSheet === 'progress_revenue') {
            $result = $this->syncService->syncProgressAndRevenue($projectId);
        } elseif ($targetSheet === 'procurement') {
            $result = $this->syncService->syncProcurement($projectId);
        } else {
            $result = $this->syncService->syncAll($projectId);
        }

        $statusCode = ($result['status'] ?? ($result['success'] ?? false)) ? 200 : 500;

        return response()->json([
            'status' => $statusCode === 200 ? 'success' : 'error',
            'result' => $result,
        ], $statusCode);
    }

    public function getDatabaseStats()
    {
        return response()->json([
            'status' => 'success',
            'database' => [
                'type' => 'SQLite',
                'file' => database_path('database.sqlite'),
                'projects_count' => MasterProject::count(),
                'master_projects_count' => MasterProject::count(),
                'gantt_activities_count' => GanttActivity::count(),
                'procurements_count' => Procurement::count(),
                'sheet_snapshots_count' => ProjectLink::count(),
                'project_links_count' => ProjectLink::count(),
                'sync_history' => SystemSync::all(),
            ],
        ]);
    }

    public function updateDescription(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'description' => 'nullable|string|max:1000',
        ]);

        $project = MasterProject::find($request->id);
        if (! $project) {
            return response()->json(['status' => 'error', 'message' => 'Project not found'], 404);
        }

        $project->update(['description' => $request->description]);

        return response()->json(['status' => 'success', 'description' => $project->description]);
    }

    public function updateProcurement(Request $request)
    {
        $request->validate([
            'master_project_id' => 'required',
            'no' => 'required|string',
            'field' => 'required|in:progress,priority,status',
            'value' => 'nullable|string',
        ]);

        $mp = MasterProject::find($request->master_project_id)
            ?? MasterProject::where('spreadsheet_id', $request->master_project_id)->first()
            ?? MasterProject::first();
        $projectId = $mp?->id ?? (int) $request->master_project_id;

        $procurement = Procurement::where('master_project_id', $projectId)
            ->where('no', $request->no)
            ->first();

        // Format value sesuai aturan validasi Google Sheets dan Database
        $dbValue = $request->value;
        $sheetValue = $request->value ?? '';

        if ($request->field === 'priority') {
            $p = strtolower(trim((string) $request->value));
            if ($p === 'high') {
                $dbValue = 'High';
                $sheetValue = 'HIGH';
            } elseif ($p === 'low') {
                $dbValue = 'Low';
                $sheetValue = 'LOW';
            } else {
                $dbValue = 'Medium';
                $sheetValue = 'NORMAL';
            }
        } elseif ($request->field === 'progress') {
            $sheetValue = strtoupper(trim((string) $request->value));
            $dbValue = $sheetValue;
        }

        if ($procurement) {
            $procurement->update([$request->field => $dbValue]);
        }

        // Update ProjectLink cached data array so GET /api/procurement-monitoring immediately returns the fresh value
        $link = ProjectLink::where('master_project_id', $projectId)
            ->where('sheet_key', 'procurement')
            ->first()
            ?? ProjectLink::where('master_project_id', $projectId)
                ->where('sheet_name', 'like', '%Procurement%')
                ->first();

        if ($link && is_array($link->data)) {
            $rows = $link->data;
            $fieldColIndex = [
                'status' => 4,
                'progress' => 5,
                'priority' => 6,
            ];
            $colIdx = $fieldColIndex[$request->field] ?? null;
            if ($colIdx !== null) {
                foreach ($rows as $rIdx => $r) {
                    if (isset($r[0]) && trim((string) $r[0]) === trim((string) $request->no)) {
                        $rows[$rIdx][$colIdx] = $sheetValue;
                        break;
                    }
                }
                $link->data = $rows;
                $link->save();
            }
        }

        // Write back to Google Sheet dinonaktifkan agar spreadsheet milik mentor aman dan tidak terubah dari website
        $sheetWritten = false;

        return response()->json([
            'status' => 'success',
            'message' => 'Updated',
            'sheet_written' => $sheetWritten,
            'db_updated' => (bool) $procurement,
            'sheet_value' => $sheetValue,
        ]);
    }
}
