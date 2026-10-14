<?php

namespace App\Http\Controllers;

use App\Models\MasterProject;
use App\Services\CurrencyService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternSheetController extends Controller
{
    /**
     * Process Read: Murni membaca dari MySQL DB (Super Cepat, Tanpa Delay Google API)
     */
    public function index(CurrencyService $currency, GoogleSheetsService $sheets): JsonResponse
    {
        try {
            $activeMasterProject = MasterProject::query()
                ->where('is_active', true)
                ->with(['budgetProfile', 'budgetBreakdowns'])
                ->first() ?: MasterProject::with(['budgetProfile', 'budgetBreakdowns'])->first();

            $rates = $currency->getRates();

            $breakdowns = $activeMasterProject ? $activeMasterProject->budgetBreakdowns : collect();
            $profile = $activeMasterProject?->budgetProfile ?: $activeMasterProject?->profile('budget_control') ?: $activeMasterProject?->profile();

            $allocatedSum = (float) $breakdowns->sum('total_amount');
            $usedSum = (float) $breakdowns->sum('estimated_need');
            $variance = (float) $breakdowns->sum('variance');

            $namaProyek = $profile?->project_name ?? $activeMasterProject?->project_name ?? 'REFURBISHMENT KRI SIGMA';
            $kodeProyek = $activeMasterProject?->project_code ?? 'B-1234567';
            $namaPimpro = $profile?->nama_pimpro ?? $activeMasterProject?->leader_name ?? 'SETIAJI';
            $namaClient = $activeMasterProject?->client_name ?? 'TNI';
            $nilaiKontrak = (float) ($profile?->nilai_kontrak ?? $activeMasterProject?->net_contract_value ?? 0);

            $formattedData = [
                'metadata' => [
                    'nama_proyek' => $namaProyek,
                    'kode_proyek' => $kodeProyek,
                    'nama_pimpro' => $namaPimpro,
                    'nama_pa' => $profile?->nama_PA ?? 'KURNIAWAN',
                    'nama_client' => $namaClient,
                    'net_contract_value' => $nilaiKontrak,
                    'spreadsheet_url' => $activeMasterProject?->spreadsheet_url,
                    'tanggal_update' => $activeMasterProject?->sheet_updated_at ?? $activeMasterProject?->updated_at?->toDateString() ?? now()->toDateString(),
                ],
                'budget_summary' => [
                    'sisa_anggaran_sap' => (float) $breakdowns->sum('sap_remaining'),
                    'wbs_belum_input' => (float) $breakdowns->sum('wbs_uninput'),
                    'total' => $allocatedSum,
                    'estimasi_kebutuhan' => $usedSum,
                    'selisih_variance' => $variance,
                ],
                'budget_breakdown' => $breakdowns->map(function ($b, $idx) {
                    $alloc = (float) ($b->allocated_amount ?? $b->total_amount ?? 0);
                    $used = (float) ($b->used_amount ?? $b->estimated_need ?? 0);
                    $sisa = (float) ($b->sap_remaining ?? ($alloc - $used));

                    return [
                        'no' => $idx + 1,
                        'wbs_element' => $b->cost_center,
                        'cost_center' => $b->cost_center,
                        'sisa_anggaran_sap' => $sisa,
                        'wbs_belum_input' => (float) ($b->wbs_uninput ?? 0),
                        'total' => $alloc,
                        'allocated_amount' => $alloc,
                        'estimasi_kebutuhan' => $used,
                        'used_amount' => $used,
                        'selisih_variance' => (float) ($b->variance ?? ($alloc - $used)),
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
                ->where('is_active', true)
                ->first() ?: MasterProject::first();

            if (! $activeMasterProject) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Master project tidak ditemukan.',
                ], 404);
            }

            $validated = $request->validate([
                'project_code' => 'sometimes|string',
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

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diupdate di DB',
                'data' => $activeMasterProject->fresh(['budgetBreakdowns', 'profile']),
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
