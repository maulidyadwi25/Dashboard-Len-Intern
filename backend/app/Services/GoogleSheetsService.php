<?php

namespace App\Services;

use App\Models\BudgetBreakdown;
use App\Models\MasterProject;
use App\Models\ProcurementItem;
use App\Models\ProjectSpreadsheet;
use Google\Client;
use Google\Service\Sheets;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected Sheets $service;

    protected string $defaultSpreadsheetId;

    public function __construct()
    {
        $this->defaultSpreadsheetId = (string) (config('services.google_sheets.spreadsheet_id')
            ?? env('GOOGLE_SPREADSHEET_ID', '1NlWhk4kzjKmeuujnMxJsZpL8LWfZudY5fC4nEyUWU70'));

        $credentialsPath = base_path((string) config('services.google_sheets.credentials_path', 'storage/app/google/serviceAccountIntern.json'));
        if (! file_exists($credentialsPath)) {
            $credentialsPath = base_path('storage/app/google/serviceAccountIntern.json');
        }

        $client = new Client;
        $client->setApplicationName('Laravel Project Dashboard');
        $client->setScopes([Sheets::SPREADSHEETS]);
        if (file_exists($credentialsPath)) {
            $client->setAuthConfig($credentialsPath);
        }
        $client->setHttpClient(new GuzzleClient([
            'connect_timeout' => 5,
            'timeout' => 10,
        ]));

        $this->service = new Sheets($client);
    }

    /**
     * Tier 2 Multi-Spreadsheet Sync Router.
     * Iterates over all active spreadsheets configured for the MasterProject and delegates
     * to the appropriate handler using PHP 8 match expression.
     *
     * @return array<string|int, array<string, mixed>>
     */
    public function syncProject(MasterProject $project): array
    {
        $spreadsheets = $project->spreadsheets()
            ->where('is_active', true)
            ->get();

        $results = [];

        foreach ($spreadsheets as $spreadsheet) {
            try {
                // Route sync execution using PHP 8 match expression
                $syncDetails = match ($spreadsheet->type) {
                    'procurement' => $this->handleProcurementSync($project, $spreadsheet),
                    'budget_control', 'budget' => $this->handleBudgetSync($project, $spreadsheet),
                    default => throw new \InvalidArgumentException("Unsupported spreadsheet type: [{$spreadsheet->type}]"),
                };

                // Update last_synced_at timestamp on project_spreadsheets
                $spreadsheet->update([
                    'last_synced_at' => now(),
                ]);

                $results[$spreadsheet->id] = [
                    'spreadsheet_id' => $spreadsheet->spreadsheet_id,
                    'type' => $spreadsheet->type,
                    'status' => 'success',
                    'details' => $syncDetails,
                ];
            } catch (\Throwable $e) {
                Log::error("Failed syncing spreadsheet [{$spreadsheet->id}] for MasterProject [{$project->id}]: ".$e->getMessage(), [
                    'project_id' => $project->id,
                    'spreadsheet_id' => $spreadsheet->spreadsheet_id,
                    'type' => $spreadsheet->type,
                    'trace' => $e->getTraceAsString(),
                ]);

                $results[$spreadsheet->id] = [
                    'spreadsheet_id' => $spreadsheet->spreadsheet_id,
                    'type' => $spreadsheet->type,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Handler for procurement spreadsheet synchronization.
     *
     * @return array<string, mixed>
     */
    public function handleProcurementSync(MasterProject $project, ProjectSpreadsheet $spreadsheet): array
    {
        $rows = $this->fetchSpreadsheetRows($spreadsheet->spreadsheet_id, ['6. Procurement', 'Procurement']);
        $items = $this->parseProcurementRows($rows);

        // Atomic transaction to ensure transactional consistency
        DB::transaction(function () use ($project, $items) {
            // Ambil start_date yang sudah ada agar tidak tertimpa saat sync
            $existingStartDates = $project->procurementItems()
                ->whereNotNull('start_date')
                ->pluck('start_date', 'no')
                ->toArray();

            $project->procurementItems()->delete();

            if (! empty($items)) {
                $payload = array_map(fn (array $item) => [
                    'master_project_id' => $project->id,
                    'no' => $item['no'],
                    'is_sub_item' => $item['is_sub_item'],
                    'main_no' => $item['main_no'],
                    'category' => $item['category'],
                    'item_name' => $item['item_name'],
                    'vendor' => $item['vendor'],
                    'target_date' => $item['target_date'],
                    'status' => $item['status'],
                    'priority' => $item['priority'],
                    'start_date' => $existingStartDates[$item['no']] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $items);

                ProcurementItem::insert($payload);
            }
        });

        return [
            'items_count' => count($items),
        ];
    }

    /**
     * Handler for budget spreadsheet synchronization.
     *
     * @return array<string, mixed>
     */
    public function handleBudgetSync(MasterProject $project, ProjectSpreadsheet $spreadsheet): array
    {
        $rows = $this->fetchSpreadsheetRows($spreadsheet->spreadsheet_id, ['Budget Control', 'Budget', 'Sheet5']);
        $summary = $this->parseBudgetSummary($rows);
        $items = $this->parseBudgetRows($rows);

        // Atomic transaction to ensure transactional consistency
        DB::transaction(function () use ($project, $items) {
            $project->budgetBreakdowns()->delete();

            if (! empty($items)) {
                $payload = array_map(fn (array $item) => [
                    'master_project_id' => $project->id,
                    'cost_center' => $item['cost_center'],
                    'sap_remaining' => $item['sap_remaining'],
                    'wbs_uninput' => $item['wbs_uninput'],
                    'total_amount' => $item['total_amount'],
                    'estimated_need' => $item['estimated_need'],
                    'variance' => $item['variance'],
                    'allocated_amount' => $item['total_amount'],
                    'used_amount' => $item['estimated_need'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $items);

                BudgetBreakdown::insert($payload);
            }
        });

        return [
            'breakdowns_count' => count($items),
            'summary' => $summary,
        ];
    }

    /**
     * Fetch all rows from Google Sheets, attempting to match preferred sheet titles.
     *
     * @param  array<int, string>  $preferredSheetTitles
     * @return array<int, array<int, string>>
     */
    public function fetchSpreadsheetRows(string $spreadsheetId, array $preferredSheetTitles = []): array
    {
        $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
        $sheetsList = $spreadsheet->getSheets();

        $targetTitle = null;

        foreach ($preferredSheetTitles as $preferred) {
            foreach ($sheetsList as $sheet) {
                $title = (string) $sheet->getProperties()->getTitle();
                if (stripos($title, $preferred) !== false) {
                    $targetTitle = $title;
                    break 2;
                }
            }
        }

        if (! $targetTitle && ! empty($sheetsList)) {
            $targetTitle = (string) $sheetsList[0]->getProperties()->getTitle();
        }

        if (! $targetTitle) {
            return [];
        }

        $range = "'{$targetTitle}'!A1:Z200";
        $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);

        return $response->getValues() ?? [];
    }

    /**
     * Parse raw rows from procurement sheet into normalized array.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array{no: ?string, is_sub_item: bool, main_no: ?int, category: string, item_name: string, vendor: ?string, target_date: ?string, status: string, priority: string}>
     */
    public function parseProcurementRows(array $rows): array
    {
        $items = [];
        $headerIndex = null;
        $colMap = [
            'no' => 0,
            'item_name' => 1,
            'vendor' => 2,
            'target_date' => 3,
            'status' => 4,
            'category' => 7,
        ];

        foreach ($rows as $index => $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $firstCell = strtoupper(trim((string) ($row[0] ?? '')));
            if ($firstCell === 'NO' || stripos($firstCell, 'NO') !== false) {
                $headerIndex = $index;
                // Map column headers dynamically if available
                foreach ($row as $colIdx => $colVal) {
                    $val = strtoupper(trim((string) $colVal));
                    if (str_contains($val, 'NAMA PENGADAAN') || str_contains($val, 'ITEM')) {
                        $colMap['item_name'] = $colIdx;
                    } elseif (str_contains($val, 'VENDOR')) {
                        $colMap['vendor'] = $colIdx;
                    } elseif (str_contains($val, 'TARGET') || str_contains($val, 'TGL')) {
                        $colMap['target_date'] = $colIdx;
                    } elseif (str_contains($val, 'JENIS') || str_contains($val, 'CATEGORY')) {
                        $colMap['category'] = $colIdx;
                    } elseif (str_contains($val, 'STATUS')) {
                        $colMap['status'] = $colIdx;
                    }
                }

                continue;
            }

            if ($headerIndex !== null && $index > $headerIndex) {
                $rawNo = trim((string) ($row[$colMap['no']] ?? ''));
                $itemName = trim((string) ($row[$colMap['item_name']] ?? ''));
                if ($itemName === '') {
                    continue;
                }

                $isSubItem = str_contains($rawNo, '.');
                $mainNo = $rawNo !== '' ? ($isSubItem ? (int) explode('.', $rawNo)[0] : (int) $rawNo) : null;
                $vendor = trim((string) ($row[$colMap['vendor']] ?? ''));
                $targetDate = trim((string) ($row[$colMap['target_date']] ?? ''));
                $category = trim((string) ($row[$colMap['category']] ?? ''));
                $status = trim((string) ($row[$colMap['status']] ?? 'Open'));
                $normalizedStatus = $this->normalizeProcurementStatus($status);

                $items[] = [
                    'no' => $rawNo !== '' ? $rawNo : null,
                    'is_sub_item' => $isSubItem,
                    'main_no' => $mainNo,
                    'category' => $category !== '' ? $category : 'General',
                    'item_name' => $itemName,
                    'vendor' => $vendor !== '' ? $vendor : null,
                    'target_date' => $targetDate !== '' ? $targetDate : null,
                    'status' => $normalizedStatus,
                    'priority' => match ($normalizedStatus) {
                        'Done' => 'Low',
                        'In Progress' => 'Medium',
                        'Open' => 'Low',
                        default => 'High',
                    },
                ];
            }
        }

        return $items;
    }

    /**
     * Parse raw rows from budget control sheet into normalized array.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array{cost_center: string, allocated_amount: float, used_amount: float}>
     */
    public function parseBudgetRows(array $rows): array
    {
        $breakdowns = [];

        foreach ($rows as $index => $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $col0 = trim((string) ($row[0] ?? ''));
            $lower = strtolower($col0);

            if ($index === 4) {
                continue;
            }

            // Skip known header / metadata labels
            if (
                $col0 === '' ||
                in_array($lower, ['nama proyek', 'tanggal update', 'budget control', 'wbs'])
            ) {
                continue;
            }

            $sapRemaining = $this->cleanNumber($row[1] ?? 0);
            $wbsUninput = $this->cleanNumber($row[2] ?? 0);
            $totalAmount = $this->cleanNumber($row[3] ?? 0);
            $estimatedNeed = $this->cleanNumber($row[4] ?? 0);
            $variance = $this->cleanNumber($row[5] ?? 0);

            $breakdowns[] = [
                'cost_center' => $col0,
                'sap_remaining' => $sapRemaining,
                'wbs_uninput' => $wbsUninput,
                'total_amount' => $totalAmount,
                'estimated_need' => $estimatedNeed,
                'variance' => $variance,
                'allocated_amount' => $totalAmount,
                'used_amount' => $estimatedNeed,
            ];
        }

        return $breakdowns;
    }

    public function parseBudgetSummary(array $rows): array
    {
        $summaryRow = $rows[4] ?? [];

        return [
            'total_sap_remaining' => $this->cleanNumber($summaryRow[1] ?? 0),
            'total_wbs_uninput' => $this->cleanNumber($summaryRow[2] ?? 0),
            'total_budget' => $this->cleanNumber($summaryRow[3] ?? 0),
            'total_estimated_need' => $this->cleanNumber($summaryRow[4] ?? 0),
            'total_variance' => $this->cleanNumber($summaryRow[5] ?? 0),
        ];
    }

    /**
     * Clean numeric string to float.
     */
    public function cleanNumber(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $value));

        return (float) ($cleaned ?: 0);
    }

    private function normalizeProcurementStatus(string $status): string
    {
        $raw = trim($status);
        if ($raw === '') {
            return 'Open';
        }

        $normalized = strtolower($raw);

        if (in_array($normalized, ['done', 'completed', 'selesai', 'finish', 'finished'])) {
            return 'Done';
        }

        if (in_array($normalized, ['in progress', 'progress', 'inprogress', 'ongoing', 'on going'])) {
            return 'In Progress';
        }

        if (in_array($normalized, ['overdue', 'delay', 'late', 'terlambat'])) {
            return 'Overdue';
        }

        if (in_array($normalized, ['open', 'pending', 'belum dimulai', 'new'])) {
            return 'Open';
        }

        return ucfirst($raw);
    }

    /**
     * Legacy adapter: Get spreadsheet last update date.
     */
    public function getSheetLastUpdate(): string
    {
        try {
            $spreadsheet = $this->service->spreadsheets->get($this->defaultSpreadsheetId);
            $sheetsList = $spreadsheet->getSheets();

            $progressSheetTitle = $sheetsList[0]->getProperties()->getTitle();
            $budgetSheetTitle = $sheetsList[1]->getProperties()->getTitle() ?? $progressSheetTitle;

            foreach ($sheetsList as $s) {
                $title = (string) $s->getProperties()->getTitle();
                if (trim($title) === 'Sheet5' || str_contains(strtolower($title), 'budget')) {
                    $budgetSheetTitle = $title;
                    break;
                }
            }

            $response = $this->service->spreadsheets_values->get(
                $this->defaultSpreadsheetId,
                "'{$budgetSheetTitle}'!A1:B10"
            );

            $rows = $response->getValues() ?? [];
            foreach ($rows as $row) {
                if (strtolower(trim((string) ($row[0] ?? ''))) === 'tanggal update') {
                    return trim((string) ($row[1] ?? ''));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch Google Sheet last update date: '.$e->getMessage());
        }

        return date('Y-m-d H:i:s');
    }
}
