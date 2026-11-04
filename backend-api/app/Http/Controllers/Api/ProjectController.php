<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GanttActivity;
use App\Models\Procurement;
use App\Models\MasterProject;
use App\Models\ProjectLink;
use App\Models\ProjectProfile;
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
            'data' => MasterProject::select('id', 'project_name', 'type', 'project_code', 'spreadsheet_id', 'description')->get(),
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
                    'type' => $project->type,
                    'project_code' => $project->project_code,
                    'spreadsheet_id' => $project->spreadsheet_id,
                    'description' => $project->description,
                ] : null,
                'profile' => $projectId ? (ProjectProfile::where('master_project_id', $projectId)->where('type', 'progress_revenue')->first() ?? ProjectProfile::where('master_project_id', $projectId)->first()) : null,
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
                $record = ProjectLink::where('master_project_id', $projectId)
                    ->where('sheet_key', 'procurement')->first();
            }

            $data = $record->data ?? [];
            $exchangeRates = $currencyService->getExchangeRates();

            return response()->json([
                'status' => 'success',
                'source' => 'sqlite_database',
                'project' => $project ? [
                    'id' => $project->id,
                    'project_name' => $project->project_name,
                    'type' => $project->type,
                    'project_code' => $project->project_code,
                    'spreadsheet_id' => $project->spreadsheet_id,
                    'description' => $project->description,
                ] : null,
                'profile' => $projectId ? (ProjectProfile::where('master_project_id', $projectId)->where('type', 'procurement')->first() ?? ProjectProfile::where('master_project_id', $projectId)->first()) : null,
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
            'field' => 'required|in:progress,priority,status,kontrak_status,payment_priority,delivery_status',
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
        } elseif ($request->field === 'payment_priority') {
            $p = strtolower(trim((string) $request->value));
            if ($p === 'high') {
                $dbValue = 'High';
                $sheetValue = 'HIGH';
            } elseif ($p === 'low') {
                $dbValue = 'Low';
                $sheetValue = 'LOW';
            } else {
                $dbValue = 'Medium';
                $sheetValue = 'MEDIUM';
            }
        } elseif ($request->field === 'progress') {
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
                'kontrak_status' => 18,
                'payment_priority' => 53,
                'delivery_status' => 55,
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

        // Write back ke Google Sheet mentor (sudah ada izin): tulis nilai ke sel yang sama
        $sheetWritten = false;
        $colLetters = [
            'status' => 'E',
            'progress' => 'F',
            'priority' => 'G',
            'kontrak_status' => 'S',
            'payment_priority' => 'BB',
            'delivery_status' => 'BD',
        ];
        $colLetter = $colLetters[$request->field] ?? null;
        if ($colLetter !== null) {
            $sheetRow = $this->syncService->findProcurementRow($projectId, trim((string) $request->no));
            if ($sheetRow !== null) {
                $sheetWritten = $this->syncService->writeBackToSheet('6. Procurement', "{$colLetter}{$sheetRow}", (string) $sheetValue);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Updated',
            'sheet_written' => $sheetWritten,
            'db_updated' => (bool) $procurement,
            'sheet_value' => $sheetValue,
        ]);
    }

    /**
     * Simpan data termin (DP / Termin 1-6) + 2 kolom status dari halaman detail.
     * Update: cache ProjectLink.data + write-back ke Google Sheet (per blok termin).
     *
     * Blok kolom (0-based index di sheet):
     *   DP   = 25..28 (Z..AC),  Termin1 = 29..32 (AD..AG), ... Termin6 = 49..52 (AX..BA)
     *   Payment Status  = 56..62 (BE..BK)  -> dp,t1..t6
     *   Status Termin    = 63..69 (BL..BR) -> dp,t1..t6 (kolom baru)
     */
    public function updateProcurementTermin(Request $request)
    {
        $request->validate([
            'master_project_id' => 'required',
            'no' => 'required|string',
            'termin_key' => 'required|in:dp,t1,t2,t3,t4,t5,t6',
            'tanggal_submit' => 'nullable|string|max:64',
            'nilai' => 'nullable|string|max:64',
            'gr_ses' => 'nullable|string|max:64',
            'no_spp' => 'nullable|string|max:64',
            'status_termin' => 'nullable|string|max:32',
            'payment_status' => 'nullable|string|max:32',
        ]);

        $mp = MasterProject::find($request->master_project_id)
            ?? MasterProject::where('spreadsheet_id', $request->master_project_id)->first()
            ?? MasterProject::first();
        $projectId = $mp?->id ?? (int) $request->master_project_id;
        $no = trim((string) $request->no);
        $key = $request->termin_key;

        $blockStart = match ($key) {
            'dp' => 25,
            't1' => 29,
            't2' => 33,
            't3' => 37,
            't4' => 41,
            't5' => 45,
            't6' => 49,
        };
        $offset = $key === 'dp' ? 0 : (int) substr($key, 1); // 0..6
        $paymentStatusIdx = 56 + $offset;
        $terminStatusIdx = 63 + $offset;

        // Normalisasi status (sheet pakai huruf besar)
        $normStatus = function (?string $v): ?string {
            if ($v === null) {
                return null;
            }
            $t = strtoupper(trim($v));
            return $t === '' ? null : $t;
        };

        $updates = [
            'tanggal_submit' => [$blockStart, $request->tanggal_submit],
            'nilai' => [$blockStart + 1, $request->nilai],
            'gr_ses' => [$blockStart + 2, $request->gr_ses],
            'no_spp' => [$blockStart + 3, $request->no_spp],
            'payment_status' => [$paymentStatusIdx, $normStatus($request->payment_status)],
            'status_termin' => [$terminStatusIdx, $normStatus($request->status_termin)],
        ];

        // 1) Update cache ProjectLink.data (sumber GET /api/procurement-monitoring)
        $link = ProjectLink::where('master_project_id', $projectId)
            ->where('sheet_key', 'procurement')
            ->first()
            ?? ProjectLink::where('master_project_id', $projectId)
                ->where('sheet_name', 'like', '%Procurement%')
                ->first();

        $rowIdx = null;
        if ($link && is_array($link->data)) {
            $rows = $link->data;
            foreach ($rows as $i => $r) {
                if (isset($r[0]) && trim((string) $r[0]) === $no) {
                    $rowIdx = $i;
                    break;
                }
            }
            if ($rowIdx !== null) {
                foreach ($updates as [$colIdx, $val]) {
                    if ($val !== null) {
                        $rows[$rowIdx][$colIdx] = $val;
                    }
                }
                $link->data = $rows;
                $link->save();
            }
        }

        // 2) Write-back permanen ke sheet mentor
        $sheetRow = $this->syncService->findProcurementRow($projectId, $no);
        $written = [];
        if ($sheetRow !== null) {
            // Kolom baru BL..BR: pastikan grid sheet cukup sebelum menulis status termin
            if (array_key_exists('status_termin', $updates) && $updates['status_termin'][1] !== null) {
                $this->syncService->ensureProcurementColumnsPublic('6. Procurement', config('services.google.sheet_id') ?? env('GOOGLE_SHEET_ID', ''));
            }
            foreach ($updates as $name => [$colIdx, $val]) {
                if ($val === null) {
                    continue;
                }
                $letter = $this->columnLetter($colIdx);
                $ok = $this->syncService->writeBackToSheet('6. Procurement', "{$letter}{$sheetRow}", (string) $val);
                $written[$name] = $ok;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Termin updated',
            'sheet_written' => $written,
            'sheet_row' => $sheetRow,
        ]);
    }

    /** 0-based column index -> huruf kolom sheet (0=A, 25=Z, 26=AA, ...) */
    private function columnLetter(int $index): string
    {
        $letters = '';
        $i = $index;
        while ($i >= 0) {
            $letters = chr(65 + ($i % 26)) . $letters;
            $i = intdiv($i, 26) - 1;
        }
        return $letters;
    }

    public function destroyProcurement(Request $request)
    {
        $request->validate([
            'master_project_id' => 'required',
            'no' => 'required|string',
        ]);

        $mp = MasterProject::find($request->master_project_id)
            ?? MasterProject::where('spreadsheet_id', $request->master_project_id)->first()
            ?? MasterProject::first();
        $projectId = $mp?->id ?? (int) $request->master_project_id;
        $no = trim((string) $request->no);

        // Hapus baris + anak-anaknya (no = X atau X.*) dari tabel procurements
        $deleted = Procurement::where('master_project_id', $projectId)
            ->where(function ($q) use ($no) {
                $q->where('no', $no)->orWhere('no', 'like', $no . '.%');
            })
            ->delete();

        // Hapus juga dari cache ProjectLink.data agar GET langsung bersih
        $link = ProjectLink::where('master_project_id', $projectId)
            ->where('sheet_key', 'procurement')
            ->first()
            ?? ProjectLink::where('master_project_id', $projectId)
                ->where('sheet_name', 'like', '%Procurement%')
                ->first();

        if ($link && is_array($link->data)) {
            $rows = array_values(array_filter($link->data, function ($r) use ($no) {
                $cellNo = trim((string) ($r[0] ?? ''));
                return $cellNo !== '' && $cellNo !== $no && ! str_starts_with($cellNo, $no . '.');
            }));
            $link->data = $rows;
            $link->row_count = count($rows);
            $link->save();
        }

        // Sheet milik mentor tidak disentuh; sync penuh berikutnya akan memunculkan lagi baris ini
        return response()->json([
            'status' => 'success',
            'message' => 'Deleted',
            'deleted' => $deleted,
        ]);
    }
}
