<?php

namespace App\Http\Controllers;

use App\Models\MasterProject;
use App\Models\Project;
use App\Services\CurrencyService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InternSheetController extends Controller
{
    /**
     * Process Read: Murni membaca dari MySQL DB (Super Cepat, Tanpa Delay Google API)
     */
    public function index(CurrencyService $currency, GoogleSheetsService $sheets): JsonResponse
    {
        try {
            $activeMasterProject = MasterProject::query()
                ->whereHas('spreadsheets', fn ($q) => $q->where('is_active', true))
                ->with(['budgetBreakdowns', 'spreadsheets'])
                ->first() ?: MasterProject::with(['budgetBreakdowns', 'spreadsheets'])->first();

            $project = Project::first();

            $rates = $currency->getRates();

            $breakdowns = $activeMasterProject ? $activeMasterProject->budgetBreakdowns : collect();

            $allocatedSum = (float) $breakdowns->sum('total_amount');
            $usedSum = (float) $breakdowns->sum('estimated_need');
            $variance = (float) $breakdowns->sum('variance');

            $formattedData = [
                'metadata' => [
                    'nama_proyek' => $activeMasterProject?->project_name ?? ($project->name ?? 'REFURBISHMENT KRI SIGMA'),
                    'kode_proyek' => $activeMasterProject?->project_code ?? ($project->project_code ?? 'B-1234567'),
                    'nama_pimpro' => $activeMasterProject?->leader_name ?? ($project->leader_name ?? 'SETIAJI'),
                    'nama_client' => $activeMasterProject?->client_name ?? ($project->client_name ?? 'TNI'),
                    'net_contract_value' => (float) ($activeMasterProject?->net_contract_value ?? ($project->net_contract_value ?? 0)),
                    'tanggal_update' => $project->sheet_last_updated_at ?? now()->toDateString(),
                ],
                'budget_summary' => [
                    'sisa_anggaran_sap' => (float) $breakdowns->sum('sap_remaining'),
                    'wbs_belum_input' => (float) $breakdowns->sum('wbs_uninput'),
                    'total' => $allocatedSum,
                    'estimasi_kebutuhan' => $usedSum,
                    'selisih_variance' => $variance,
                ],
                'budget_breakdown' => $breakdowns->map(function ($b, $idx) {
                    $alloc = (float) $b->allocated_amount;
                    $used = (float) $b->used_amount;
                    $sisa = $alloc - $used;

                    return [
                        'no' => $idx + 1,
                        'wbs_element' => $b->cost_center,
                        'cost_center' => $b->cost_center,
                        'sisa_anggaran_sap' => $sisa,
                        'wbs_belum_input' => 0.0,
                        'total' => $alloc,
                        'allocated_amount' => $alloc,
                        'estimasi_kebutuhan' => $used,
                        'used_amount' => $used,
                        'selisih_variance' => $sisa,
                    ];
                })->toArray(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Data dashboard berhasil dimuat dari DB (MySQL)',
                'data' => [
                    'sheetData' => $formattedData,
                    'exchangeRates' => $rates,
                ],
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data dashboard: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Process Update/Write: Receive Input -> Update DB -> Sync DB & Sheet -> Done
     */
    public function update(Request $request, GoogleSheetsService $sheets): JsonResponse
    {
        try {
            $activeMasterProject = MasterProject::query()
                ->whereHas('spreadsheets', fn ($q) => $q->where('is_active', true))
                ->first() ?: MasterProject::first();

            if (! $activeMasterProject) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Master project tidak ditemukan.',
                ], 404);
            }

            $validated = $request->validate([
                'project_code' => 'required|string',
                'budget_summary' => 'sometimes|array',
                'budget_summary.sisa_anggaran_sap' => 'nullable|numeric',
                'budget_summary.wbs_belum_input' => 'nullable|numeric',
                'budget_summary.total' => 'nullable|numeric',
                'budget_summary.estimasi_kebutuhan' => 'nullable|numeric',
                'budget_summary.selisih_variance' => 'nullable|numeric',
                'budget_breakdown' => 'sometimes|array',
            ]);

            if (isset($validated['budget_breakdown'])) {
                foreach ($validated['budget_breakdown'] as $bItem) {
                    $costCenter = $bItem['wbs_element'] ?? $bItem['cost_center'] ?? null;

                    if (! $costCenter) {
                        continue;
                    }

                    $activeMasterProject->budgetBreakdowns()->updateOrCreate(
                        [
                            'cost_center' => $costCenter,
                        ],
                        [
                            'cost_center' => $costCenter,
                            'allocated_amount' => $bItem['allocated_amount'] ?? $bItem['total'] ?? 0,
                            'used_amount' => $bItem['used_amount'] ?? $bItem['estimasi_kebutuhan'] ?? 0,
                        ]
                    );
                }
            }

            // Sync DB ke Google Sheets
            $syncedToSheet = $sheets->writeDatabaseToSheet($activeMasterProject, $activeMasterProject);
            if ($syncedToSheet) {
                Cache::forget('google_sheet_last_update');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diupdate di DB'.($syncedToSheet ? ' dan disinkronkan ke Google Sheet' : ''),
                'data' => $activeMasterProject->fresh(['budgetBreakdowns']),
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate data: '.$e->getMessage(),
            ], 500);
        }
    }
}
