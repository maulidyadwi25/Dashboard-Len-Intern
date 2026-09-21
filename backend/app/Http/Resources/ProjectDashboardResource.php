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
        $budgetBreakdowns = $this->budgetBreakdowns;

        $totalSapRemaining = (float) $budgetBreakdowns->sum('sap_remaining');
        $totalWbsUninput = (float) $budgetBreakdowns->sum('wbs_uninput');
        $totalBudget = (float) $budgetBreakdowns->sum('total_amount');
        $totalEstimatedNeed = (float) $budgetBreakdowns->sum('estimated_need');
        $totalVariance = (float) $budgetBreakdowns->sum('variance');

        $procurementStatusSummary = $this->procurementItems
            ->groupBy('status')
            ->map(fn ($items) => $items->count());

        $budgetSummary = [
            'sisa_anggaran_sap' => $totalSapRemaining,
            'wbs_belum_input' => $totalWbsUninput,
            'total' => $totalBudget,
            'estimasi_kebutuhan' => $totalEstimatedNeed,
            'selisih_variance' => $totalVariance,
        ];

        $procurementMatrix = $this->procurementItems
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

        return [
            'id' => $this->id,
            'project_code' => $this->project_code,
            'project_name' => $this->project_name,
            'client_name' => $this->client_name,
            'leader_name' => $this->leader_name,
            'net_contract_value' => (float) $this->net_contract_value,
            'metadata' => [
                'nama_proyek' => $this->project_name,
                'kode_proyek' => $this->project_code,
                'nama_pimpro' => $this->leader_name,
                'nama_client' => $this->client_name,
                'net_contract_value' => (float) $this->net_contract_value,
                'tanggal_update' => $this->updated_at?->toDateString() ?? now()->toDateString(),
            ],
            'aggregates' => [
                'total_allocated_budget' => $totalBudget,
                'total_used_budget' => $totalEstimatedNeed,
                'remaining_budget' => $totalSapRemaining,
                'budget_utilization_percentage' => $totalBudget > 0 ? round(($totalEstimatedNeed / $totalBudget) * 100, 2) : 0.0,
                'total_procurement_items' => $this->procurementItems->count(),
                'procurement_status_summary' => $procurementStatusSummary,
                'active_spreadsheets_count' => $this->spreadsheets->where('is_active', true)->count(),
                'total_sap_remaining' => $totalSapRemaining,
                'total_wbs_uninput' => $totalWbsUninput,
                'total_budget' => $totalBudget,
                'total_estimated_need' => $totalEstimatedNeed,
                'total_variance' => $totalVariance,
            ],
            'budget_summary' => $budgetSummary,
            'budget_breakdown' => $budgetBreakdown,
            'procurement_matrix' => $procurementMatrix,
            'spreadsheets' => $this->spreadsheets->map(fn ($sheet) => [
                'id' => $sheet->id,
                'spreadsheet_id' => $sheet->spreadsheet_id,
                'type' => $sheet->type,
                'title' => $sheet->title,
                'is_active' => (bool) $sheet->is_active,
                'last_synced_at' => $sheet->last_synced_at?->toIso8601String(),
            ]),
            'procurement_items' => $this->procurementItems->map(fn ($item) => [
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
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ]),
            'items' => $this->procurementItems->map(fn ($item) => [
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
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ]),
            'budget_breakdowns' => $budgetBreakdown,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
