<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $budgetBreakdowns = $this->budgetBreakdowns ?? collect();
        $procurementItems = $this->procurementItems ?? collect();

        // profile() is a helper method, not a relation, so it must be called
        // explicitly. Budget profile wins, mirroring InternSheetController.
        $profile = $this->resource->budgetProfile ?: $this->resource->profile();

        $totalSapRemaining = (float) $budgetBreakdowns->sum('sap_remaining');
        $totalWbsUninput = (float) $budgetBreakdowns->sum('wbs_uninput');
        $totalBudget = (float) $budgetBreakdowns->sum('total_amount');
        $totalEstimatedNeed = (float) $budgetBreakdowns->sum('estimated_need');
        $totalVariance = (float) $budgetBreakdowns->sum('variance');

        $procurementStatusSummary = $procurementItems
            ->groupBy('status')
            ->map(fn ($items) => $items->count());

        $budgetSummary = [
            'sisa_anggaran_sap' => $totalSapRemaining,
            'wbs_belum_input' => $totalWbsUninput,
            'total' => $totalBudget,
            'estimasi_kebutuhan' => $totalEstimatedNeed,
            'selisih_variance' => $totalVariance,
        ];

        $procurementMatrix = $procurementItems
            ->groupBy(function ($item) {
                $category = strtoupper(trim((string) ($item->category ?? '')));

                return match (true) {
                    $category === 'BARANG' => 'Pengadaan Barang',
                    $category === 'JASA' => 'Pengadaan Jasa',
                    $category === 'BARANG & JASA' => 'Pengadaan Barang & Jasa',
                    default => 'Lainnya',
                };
            })
            ->map(function ($items, $category) {
                $onTrack = $items->filter(fn ($item) => strtoupper(trim((string) ($item->status ?? ''))) === 'DONE')->count();
                $ongoing = $items->filter(fn ($item) => in_array(strtoupper(trim((string) ($item->status ?? ''))), ['IN PROGRESS', 'OPEN'], true))->count();
                $delay = $items->filter(fn ($item) => strtoupper(trim((string) ($item->status ?? ''))) === 'OVERDUE')->count();

                return [
                    'category' => $category,
                    'total' => $items->count(),
                    'onTrack' => $onTrack,
                    'ongoing' => $ongoing,
                    'delay' => $delay,
                ];
            })
            ->values()
            ->all();

        $budgetBreakdown = $budgetBreakdowns->map(function ($breakdown, $index) {
            $sapRemaining = (float) ($breakdown->sap_remaining ?? 0);
            $wbsUninput = (float) ($breakdown->wbs_uninput ?? 0);
            $totalAmount = (float) ($breakdown->total_amount ?? 0);
            $estimatedNeed = (float) ($breakdown->estimated_need ?? 0);
            $variance = (float) ($breakdown->variance ?? 0);

            return [
                'id' => $breakdown->id,
                'no' => $index + 1,
                'cost_center' => $breakdown->cost_center,
                'wbs_element' => $breakdown->cost_center,
                'sap_remaining' => $sapRemaining,
                'wbs_uninput' => $wbsUninput,
                'total_amount' => $totalAmount,
                'estimated_need' => $estimatedNeed,
                'variance' => $variance,
                'allocated_amount' => $totalAmount,
                'used_amount' => $estimatedNeed,
                'total' => $totalAmount,
                'remaining_amount' => $sapRemaining,
                'sisa_anggaran_sap' => $sapRemaining,
                'wbs_belum_input' => $wbsUninput,
                'estimasi_kebutuhan' => $estimatedNeed,
                'selisih_variance' => $variance,
                'created_at' => $breakdown->created_at?->toIso8601String(),
                'updated_at' => $breakdown->updated_at?->toIso8601String(),
            ];
        })->values();

        $namaPA = $profile?->nama_PA ?? 'KURNIAWAN';
        $namaPimpro = $profile?->nama_pimpro ?? $this->leader_name ?? 'SETIAJI';
        $nilaiKontrak = (float) ($profile?->nilai_kontrak ?? $this->net_contract_value ?? 0);
        $projectName = $profile?->project_name ?? $this->project_name;

        return [
            'id' => $this->id,
            'project_id' => $this->project_code,
            'project_code' => $this->project_code,
            'project_name' => $this->project_name,
            'client_name' => $this->client_name,
            'leader_name' => $this->leader_name,
            'net_contract_value' => (float) $this->net_contract_value,
            'spreadsheet_url' => $this->spreadsheet_url,
            'spreadsheet_id' => $this->spreadsheet_id,
            'type' => $this->type,
            'title' => $this->title,
            'is_active' => (bool) $this->is_active,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'profile' => $profile ? [
                'id' => $profile->id,
                'project_id' => $profile->project_id,
                'type' => $profile->type,
                'project_name' => $profile->project_name,
                'nama_PA' => $profile->nama_PA,
                'nama_pimpro' => $profile->nama_pimpro,
                'nilai_kontrak' => (float) $profile->nilai_kontrak,
            ] : null,
            'budget_profile' => $this->budgetProfile ? [
                'id' => $this->budgetProfile->id,
                'type' => $this->budgetProfile->type,
                'project_name' => $this->budgetProfile->project_name,
                'nama_PA' => $this->budgetProfile->nama_PA,
                'nama_pimpro' => $this->budgetProfile->nama_pimpro,
                'nilai_kontrak' => (float) $this->budgetProfile->nilai_kontrak,
            ] : null,
            'procurement_profile' => $this->procurementProfile ? [
                'id' => $this->procurementProfile->id,
                'type' => $this->procurementProfile->type,
                'project_name' => $this->procurementProfile->project_name,
                'nama_PA' => $this->procurementProfile->nama_PA,
                'nama_pimpro' => $this->procurementProfile->nama_pimpro,
                'nilai_kontrak' => (float) $this->procurementProfile->nilai_kontrak,
            ] : null,
            'profiles' => $this->profiles->map(fn ($p) => [
                'id' => $p->id,
                'type' => $p->type,
                'project_name' => $p->project_name,
                'nama_PA' => $p->nama_PA,
                'nama_pimpro' => $p->nama_pimpro,
                'nilai_kontrak' => (float) $p->nilai_kontrak,
            ]),
            'metadata' => [
                'nama_proyek' => $projectName,
                'kode_proyek' => $this->project_code,
                'nama_pimpro' => $namaPimpro,
                'nama_pa' => $namaPA,
                'nama_client' => $this->client_name,
                'net_contract_value' => $nilaiKontrak,
                'spreadsheet_url' => $this->spreadsheet_url,
                'tanggal_update' => $this->sheet_updated_at ?? $this->updated_at?->toDateString() ?? now()->toDateString(),
            ],
            'aggregates' => [
                'total_allocated_budget' => $totalBudget,
                'total_used_budget' => $totalEstimatedNeed,
                'remaining_budget' => $totalSapRemaining,
                'budget_utilization_percentage' => $totalBudget > 0 ? round(($totalEstimatedNeed / $totalBudget) * 100, 2) : 0.0,
                'total_procurement_items' => $procurementItems->count(),
                'procurement_status_summary' => $procurementStatusSummary,
                'total_sap_remaining' => $totalSapRemaining,
                'total_wbs_uninput' => $totalWbsUninput,
                'total_budget' => $totalBudget,
                'total_estimated_need' => $totalEstimatedNeed,
                'total_variance' => $totalVariance,
            ],
            'budget_summary' => $budgetSummary,
            'budget_breakdown' => $budgetBreakdown,
            'procurement_matrix' => $procurementMatrix,
            'procurement_items' => $procurementItems->map(fn ($item) => [
                'id' => $item->id,
                'no' => $item->no,
                'is_sub_item' => (bool) $item->is_sub_item,
                'main_no' => $item->main_no,
                'category' => $item->category,
                'jenis' => $item->category,
                'item_name' => $item->item_name,
                'nama_pengadaan' => $item->item_name,
                'vendor' => $item->vendor,
                'nama_vendor' => $item->vendor,
                'target_date' => $item->target_date,
                'target_tgl_pengadaan' => $item->target_date,
                'status' => $item->status,
                'priority' => $item->priority,
                'start_date' => $item->start_date,
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ]),
            'items' => $procurementItems->map(fn ($item) => [
                'id' => $item->id,
                'no' => $item->no,
                'is_sub_item' => (bool) $item->is_sub_item,
                'main_no' => $item->main_no,
                'category' => $item->category,
                'jenis' => $item->category,
                'item_name' => $item->item_name,
                'nama_pengadaan' => $item->item_name,
                'vendor' => $item->vendor,
                'nama_vendor' => $item->vendor,
                'target_date' => $item->target_date,
                'target_tgl_pengadaan' => $item->target_date,
                'status' => $item->status,
                'priority' => $item->priority,
                'start_date' => $item->start_date,
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ]),
            'budget_breakdowns' => $budgetBreakdown,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
