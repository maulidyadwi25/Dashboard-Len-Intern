<?php

namespace App\Services;

use App\Models\MasterProject;
use App\Models\WbsAiSummary;
use App\Models\WbsItem;
use App\Models\WbsWeeklyValue;
use App\Support\WbsDateHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WbsAiSummaryService
{
    public function buildWeekSummaryPayload(MasterProject $project, ?Carbon $date = null): array
    {
        $referenceDate = $date ?? Carbon::now('Asia/Jakarta');
        $year = $project->progress_revenue_year ?? (int) $referenceDate->year;
        $weekInfo = WbsDateHelper::resolveWeekInfo($referenceDate, 'Asia/Jakarta');
        $currentOrdinal = WbsDateHelper::periodOrdinal($year, (int) $weekInfo['month'], (int) $weekInfo['week']) - ($year * 48);

        $items = $project->wbsItems()->orderBy('order_index')->get();
        $parentWbsNumbers = $items->pluck('parent_wbs_no')->filter()->flip();
        $leaves = $items->reject(fn (WbsItem $item) => $parentWbsNumbers->has($item->wbs_no));

        $entriesByWbsNo = $project->wbsProgressEntries()
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('week')
            ->get()
            ->groupBy('wbs_no');

        $rows = [];
        foreach ($leaves as $item) {
            $entries = $entriesByWbsNo->get($item->wbs_no, collect());
            $latest = $entries->last();
            $progress = $latest ? (float) $latest->percent : 0.0;
            $plannedPercent = $this->plannedPercentForItem($item, $year, $currentOrdinal);
            $deviation = $progress - $plannedPercent;

            $rows[] = [
                'wbs_no' => $item->wbs_no,
                'activity_name' => $item->activity_name ?? $item->wbs_no,
                'status' => $this->statusForItem($item, $entries, $year, $currentOrdinal),
                'latest_progress_percent' => round($progress, 2),
                'planned_percent' => round($plannedPercent, 2),
                'deviation_percent' => round($deviation, 2),
                'bobot_plan' => (float) $item->bobot_plan,
            ];
        }

        usort($rows, fn (array $a, array $b) => abs((float) $b['deviation_percent']) <=> abs((float) $a['deviation_percent']));

        return [
            'project_id' => $project->id,
            'project_name' => $project->project_name,
            'week_key' => $year.'-'.(string) $weekInfo['month'].'-'.(string) $weekInfo['week'],
            'week_label' => WbsDateHelper::periodLabel((int) $weekInfo['month'], (int) $weekInfo['week']),
            'generated_at' => $referenceDate->toIso8601String(),
            'items' => array_slice($rows, 0, 30),
        ];
    }

    public function generateForProject(MasterProject $project, bool $force = false): array
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $existing = WbsAiSummary::query()
            ->where('master_project_id', $project->id)
            ->whereDate('summary_date', $today)
            ->first();

        if ($existing && $existing->source === 'manual' && ! $force) {
            return [
                'content' => $existing->content,
                'source' => 'manual',
                'summary_date' => $existing->summary_date->toDateString(),
                'generated_at' => $existing->generated_at?->toIso8601String(),
                'skipped' => true,
            ];
        }

        $baseUrl = trim((string) config('services.ai.base_url'));
        if ($baseUrl === '') {
            throw new \RuntimeException('AI service is not configured.');
        }

        $compact = $this->buildWeekSummaryPayload($project);

        $response = Http::timeout((int) config('services.ai.timeout', 30))
            ->withHeaders($this->headers())
            ->post(rtrim($baseUrl, '/').'/chat/completions', $this->buildRequestPayload($compact));

        if (! $response->successful()) {
            throw new \RuntimeException('AI summary generation failed.');
        }

        $decoded = $this->extractStructuredJson($response->json());
        $validated = $this->validateResponse($decoded);

        $saved = WbsAiSummary::query()
            ->where('master_project_id', $project->id)
            ->whereDate('summary_date', $today)
            ->first();

        if ($saved) {
            $saved->fill([
                'content' => $validated,
                'source' => 'ai',
                'generated_at' => now('Asia/Jakarta'),
            ]);
            $saved->save();
        } else {
            $saved = WbsAiSummary::create([
                'master_project_id' => $project->id,
                'summary_date' => $today,
                'content' => $validated,
                'source' => 'ai',
                'generated_at' => now('Asia/Jakarta'),
            ]);
        }

        return [
            'content' => $saved->content,
            'source' => 'ai',
            'summary_date' => $saved->summary_date->toDateString(),
            'generated_at' => $saved->generated_at?->toIso8601String(),
        ];
    }

    public function validateSummaryStructure(mixed $content): array
    {
        $data = is_array($content) ? $content : [];

        if (! isset($data['executive_summary']) || ! is_string($data['executive_summary'])) {
            throw new \InvalidArgumentException('Summary content must include executive_summary as string.');
        }

        if (! isset($data['recommendations']) || ! is_array($data['recommendations'])) {
            throw new \InvalidArgumentException('Summary content must include recommendations array.');
        }

        if (! isset($data['risks']) || ! is_array($data['risks'])) {
            throw new \InvalidArgumentException('Summary content must include risks array.');
        }

        $recommendations = [];
        foreach ($data['recommendations'] as $recommendation) {
            if (! is_array($recommendation)) {
                throw new \InvalidArgumentException('Each recommendation must be an object.');
            }

            $priority = $recommendation['priority'] ?? null;
            $title = $recommendation['title'] ?? null;
            $detail = $recommendation['detail'] ?? null;

            if (! is_string($priority) || ! is_string($title) || ! is_string($detail)) {
                throw new \InvalidArgumentException('Each recommendation requires priority, title, and detail as strings.');
            }

            $recommendations[] = [
                'priority' => $priority,
                'title' => $title,
                'detail' => $detail,
            ];
        }

        $risks = [];
        foreach ($data['risks'] as $risk) {
            if (! is_string($risk)) {
                throw new \InvalidArgumentException('Each risk must be a string.');
            }

            $risks[] = $risk;
        }

        return [
            'executive_summary' => trim($data['executive_summary']),
            'recommendations' => $recommendations,
            'risks' => $risks,
        ];
    }

    protected function headers(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        $apiKey = trim((string) config('services.ai.api_key'));
        if ($apiKey !== '') {
            $headers['Authorization'] = 'Bearer '.$apiKey;
        }

        return $headers;
    }

    protected function buildRequestPayload(array $compact): array
    {
        return [
            'model' => config('services.ai.model', 'gpt-4o-mini'),
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah manajer proyek yang menulis ringkasan eksekutif untuk WBS. Jawab HANYA JSON valid tanpa markdown. Struktur: {"executive_summary":"...","recommendations":[{"priority":"high|medium|low","title":"...","detail":"..."}],"risks":["..."]}. Jangan mengarang angka selain yang ada di data. Fokus pada realisasi, deviasi, dan tindakan prioritas.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($compact, JSON_THROW_ON_ERROR),
                ],
            ],
        ];
    }

    protected function extractStructuredJson(mixed $response): array
    {
        $payload = $response;
        $text = is_array($payload) && isset($payload['choices'][0]['message']['content']) ? $payload['choices'][0]['message']['content'] : '';

        if (! is_string($text) || trim($text) === '') {
            throw new \RuntimeException('AI response is empty.');
        }

        $candidate = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $candidate = preg_replace('/\s*```\s*$/i', '', (string) $candidate);
        $candidate = trim((string) $candidate);

        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($candidate, '{');
        $end = strrpos($candidate, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $inner = substr($candidate, $start, $end - $start + 1);
            $decoded = json_decode($inner, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('AI response did not contain valid JSON.');
    }

    protected function validateResponse(array $decoded): array
    {
        return $this->validateSummaryStructure($decoded);
    }

    protected function statusForItem(WbsItem $item, $entries, int $year, int $currentWeekOrdinal): string
    {
        $latest = $entries->last();
        $progress = $latest ? (float) $latest->percent : 0.0;
        $planEndOrdinal = $this->parsePlanEndOrdinal((string) $item->plan_end_period, $year);

        if ($progress >= 100.0) {
            return 'completed';
        }

        if ($planEndOrdinal !== null && $currentWeekOrdinal > $planEndOrdinal && $progress < 100.0) {
            return $progress >= 90 ? 'critical' : 'overdue';
        }

        return $progress > 0 ? 'on_track' : 'not_started';
    }

    protected function plannedPercentForItem(WbsItem $item, int $year, int $currentWeekOrdinal): float
    {
        $values = WbsWeeklyValue::query()
            ->where('progress_wbs_item_id', $item->id)
            ->where('type', 'plan')
            ->get();

        $total = $values->sum('value');
        if ($total <= 0) {
            return 0.0;
        }

        $progressed = $values->filter(function ($value) use ($year, $currentWeekOrdinal) {
            $ordinal = WbsDateHelper::periodOrdinal((int) $value->year, (int) $value->month, (int) $value->week) - ($year * 48);

            return $ordinal <= $currentWeekOrdinal;
        })->sum('value');

        return ($progressed / $total) * 100;
    }

    protected function parsePlanEndOrdinal(?string $value, int $year): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $ordinal = WbsDateHelper::periodOrdinalFromLabel((string) $value, $year);

        return $ordinal === null ? null : ($ordinal - ($year * 48));
    }
}
