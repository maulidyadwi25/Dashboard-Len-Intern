<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProgressRevenueResource extends JsonResource
{
    /**
     * Ordered indicator keys, matching the sheet layout.
     *
     * @var array<int, string>
     */
    public const INDICATOR_KEYS = [
        'rkap_weekly_plan',
        'rkap_weekly_cumulative',
        'project_weekly_plan',
        'project_weekly_cumulative',
        'project_weekly_actual',
        'project_actual_cumulative',
        'deviation_vs_rkap',
    ];

    /**
     * @var array<int, string>
     */
    public const INDICATOR_LABELS = [
        'rkap_weekly_plan' => 'Rencana Mingguan RKAP',
        'rkap_weekly_cumulative' => 'Akumulasi Mingguan RKAP',
        'project_weekly_plan' => 'Rencana Mingguan Proyek',
        'project_weekly_cumulative' => 'Akumulasi Mingguan Proyek',
        'project_weekly_actual' => 'Realisasi Mingguan Proyek',
        'project_actual_cumulative' => 'Akumulasi Realisasi Proyek',
        'deviation_vs_rkap' => 'Deviasi Proyek vs RKAP',
    ];

    /**
     * @var array<int, string>
     */
    public const MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $values = $this->resource->progressRevenueValues ?? collect();
        $profile = $this->resource->progressRevenueProfile;

        $baselineRow = $values->firstWhere('is_baseline', true);
        $baselineYear = $baselineRow?->year !== null ? (int) $baselineRow->year : null;
        $periodYear = $this->resolvePeriodYear($values, $baselineYear);

        $periods = $this->buildPeriods();
        $requestedMonth = $this->resolveMonthFilter($request);

        $filteredPeriods = $requestedMonth === null
            ? $periods
            : array_values(array_filter($periods, fn (array $p) => $p['month'] === $requestedMonth));

        $baselineValues = $this->buildBaselineValues($values, $baselineYear);
        $indicators = $this->buildIndicators($values, $periodYear, $filteredPeriods);

        return [
            'project' => $this->buildProject($profile),
            'baseline' => [
                'label' => $this->buildBaselineLabel($baselineYear),
                'values' => $baselineValues,
            ],
            'periods' => $filteredPeriods,
            'indicators' => $indicators,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildProject($profile): ?array
    {
        if (! $profile) {
            return [
                'id' => $this->resource->id,
                'client_name' => $this->resource->client_name,
                'project_code' => $this->resource->project_code ?? $this->resource->project_id,
                'project_name' => $this->resource->project_name,
                'project_lead_name' => $this->resource->leader_name,
                'contract_value' => (float) ($this->resource->net_contract_value ?? 0),
                'spreadsheet_url' => $this->resource->spreadsheet_url,
                'synced_at' => null,
            ];
        }

        return [
            'id' => $this->resource->id,
            'client_name' => null,
            'project_code' => $this->resource->project_code ?? $this->resource->project_id,
            'project_name' => $profile->project_name,
            'project_lead_name' => $profile->nama_pimpro,
            'report_owner_name' => $profile->nama_PA,
            'contract_value' => $profile->nilai_kontrak !== null ? (float) $profile->nilai_kontrak : null,
            'spreadsheet_url' => $this->resource->spreadsheet_url,
            'synced_at' => $profile->updated_at?->toIso8601String(),
        ];
    }

    private function castFraction(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function resolvePeriodYear(Collection $values, ?int $baselineYear): int
    {
        $year = $values->where('is_baseline', false)->max('year');

        return $year !== null ? (int) $year : ($baselineYear !== null ? $baselineYear + 1 : (int) now()->year);
    }

    /**
     * Build the fixed Jan W1 - Des W4 period grid for the target year.
     *
     * @return array<int, array{month: int, week: int, label: string}>
     */
    private function buildPeriods(): array
    {
        $periods = [];
        $monthLabels = self::MONTH_LABELS;

        for ($month = 1; $month <= 12; $month++) {
            for ($week = 1; $week <= 4; $week++) {
                $periods[] = [
                    'month' => $month,
                    'week' => $week,
                    'label' => $monthLabels[$month - 1].' W'.$week,
                ];
            }
        }

        return $periods;
    }

    /**
     * @return array<string, float|null>
     */
    private function buildBaselineValues(Collection $values, ?int $baselineYear): array
    {
        $baseline = [];

        foreach (self::INDICATOR_KEYS as $key) {
            $baseline[$key] = null;
        }

        if ($baselineYear === null) {
            return $baseline;
        }

        $rows = $values->where('is_baseline', true)->where('year', $baselineYear)->keyBy('indicator_key');

        foreach (self::INDICATOR_KEYS as $key) {
            $value = $rows->get($key)?->value;
            $baseline[$key] = $value === null ? null : (float) $value;
        }

        return $baseline;
    }

    /**
     * @param  array<int, array{month: int, week: int, label: string}>  $periods
     * @return array<int, array{key: string, label: string, values: array<int, float|null>}>
     */
    private function buildIndicators(Collection $values, int $year, array $periods): array
    {
        $rows = $values->where('is_baseline', false)->where('year', $year);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->indicator_key][$row->month.'-'.$row->week] = $row->value === null ? null : (float) $row->value;
        }

        $indicators = [];

        foreach (self::INDICATOR_KEYS as $key) {
            $keyMap = $map[$key] ?? [];
            $series = [];

            foreach ($periods as $period) {
                $lookup = $period['month'].'-'.$period['week'];
                $series[] = $keyMap[$lookup] ?? null;
            }

            $indicators[] = [
                'key' => $key,
                'label' => self::INDICATOR_LABELS[$key],
                'values' => $series,
            ];
        }

        return $indicators;
    }

    private function buildBaselineLabel(?int $baselineYear): ?string
    {
        if ($baselineYear === null) {
            return null;
        }

        return self::MONTH_LABELS[11].' '.$baselineYear;
    }

    /**
     * month=all returns null (no filtering); 1..12 filters backend-side.
     */
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
