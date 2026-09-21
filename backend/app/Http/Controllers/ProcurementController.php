<?php

namespace App\Http\Controllers;

use App\Models\MasterProject;
use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcurementController extends Controller
{
    // Injeksi service melalui properti, tidak lagi manual pakai 'new'
    protected GoogleSheetsService $sheetsService;

    public function __construct(GoogleSheetsService $sheetsService)
    {
        $this->sheetsService = $sheetsService;
    }

    public function index(): JsonResponse
    {
        try {
            $activeMasterProject = MasterProject::query()
                ->whereHas('spreadsheets', fn ($q) => $q->where('is_active', true))
                ->first() ?: MasterProject::first();

            if (! $activeMasterProject) {
                return response()->json([
                    'project' => null,
                    'items' => [],
                    'procurement_items' => [],
                    'status_counts' => [],
                    'total' => 0,
                    'main_items_total' => 0,
                ]);
            }

            /** Backfill priority if not yet populated. */
            if ($activeMasterProject->procurementItems()->whereNull('priority')->exists()) {
                $this->sheetsService->syncProject($activeMasterProject);
            }

            $items = $activeMasterProject->procurementItems()
                ->orderByRaw("CAST(SUBSTRING_INDEX(no, '.', 1) AS UNSIGNED), 
                              CAST(NULLIF(SUBSTRING_INDEX(no, '.', -1), SUBSTRING_INDEX(no, '.', 1)) AS UNSIGNED)")
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'no' => $item->no,
                        'is_sub_item' => (bool) $item->is_sub_item,
                        'main_no' => $item->main_no,
                        'item_name' => $item->item_name,
                        'nama_pengadaan' => $item->item_name,
                        'category' => $item->category,
                        'jenis' => $item->category,
                        'vendor' => $item->vendor,
                        'nama_vendor' => $item->vendor,
                        'target_date' => $item->target_date,
                        'target_tgl_pengadaan' => $item->target_date,
                        'status' => $item->status,
                        'priority' => $item->priority,
                        'start_date' => $item->start_date,
                    ];
                });

            // Compute summary stats for the chart
            $statusCounts = $items->groupBy('status')->map->count();

            return response()->json([
                'project' => [
                    'code' => $activeMasterProject->project_code,
                    'name' => $activeMasterProject->project_name,
                    'leader_name' => $activeMasterProject->leader_name,
                    'client_name' => $activeMasterProject->client_name,
                    'analyst_name' => 'KURNIAWAN',
                    'contract_value' => (float) $activeMasterProject->net_contract_value,
                ],
                'items' => $items->values(),
                'procurement_items' => $items->values(),
                'status_counts' => $statusCounts,
                'total' => $items->count(),
                'main_items_total' => $items->where('is_sub_item', false)->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('ProcurementController@index error: '.$e->getMessage());

            return response()->json([
                'error' => 'Gagal memuat data procurement: '.$e->getMessage(),
            ], 500);
        }
    }

    public function sync(): JsonResponse
    {
        try {
            $activeMasterProject = MasterProject::query()
                ->whereHas('spreadsheets', fn ($q) => $q->where('is_active', true))
                ->first() ?: MasterProject::first();

            if ($activeMasterProject) {
                $this->sheetsService->syncProject($activeMasterProject);
            }

            return response()->json([
                'message' => 'Sync berhasil',
                'total' => $activeMasterProject ? $activeMasterProject->procurementItems()->count() : 0,
            ]);

        } catch (\Exception $e) {
            Log::error('ProcurementController@sync error: '.$e->getMessage());

            return response()->json([
                'error' => 'Gagal melakukan sinkronisasi data: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateStartDate(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
            ]);

            $activeMasterProject = MasterProject::query()
                ->whereHas('spreadsheets', fn ($q) => $q->where('is_active', true))
                ->first() ?: MasterProject::first();

            if (! $activeMasterProject) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada project aktif',
                ], 404);
            }

            $item = $activeMasterProject->procurementItems()->find($id);

            if (! $item) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item procurement tidak ditemukan',
                ], 404);
            }

            // Konversi tanggal dari YYYY-MM-DD (browser) ke DD/MM/YYYY (format database)
            $rawDate = $request->start_date;
            $convertedDate = \Carbon\Carbon::parse($rawDate)->format('d/m/Y');

            // Update menggunakan timestamp untuk mencegah overwrite oleh Google Sheets sync
            $item->update([
                'start_date' => $convertedDate,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Start date berhasil diperbarui',
                'data' => [
                    'id' => $item->id,
                    'start_date' => $item->start_date,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('ProcurementController@updateStartDate error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui start date: '.$e->getMessage(),
            ], 500);
        }
    }
}
