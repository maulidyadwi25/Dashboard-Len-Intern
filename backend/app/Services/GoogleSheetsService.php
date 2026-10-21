<?php

namespace App\Services;

use App\Models\BudgetBreakdown;
use App\Models\MasterProject;
use App\Models\ProcurementItem;
use App\Models\ProgressRevenueValue;
use App\Models\WbsItem;
use App\Models\WbsWeeklyValue;
use Google\Client;
use Google\Service\Sheets;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    public const PROGRESS_MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

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
     * Synchronize MasterProject data from its configured Google Spreadsheet.
     *
     * @return array<string, mixed>
     */
    public function syncProject(MasterProject $project): array
    {
        $spreadsheetId = $project->effective_spreadsheet_id ?: $this->defaultSpreadsheetId;
        $type = strtolower(trim((string) ($project->type ?? 'all')));
        $results = [];

        try {
            if (in_array($type, ['procurement', 'all', ''])) {
                $procurementDetails = $this->handleProcurementSync($project, $spreadsheetId);
                $results['procurement'] = [
                    'status' => 'success',
                    'details' => $procurementDetails,
                ];
            }

            if (in_array($type, ['budget_control', 'budget', 'all', ''])) {
                $budgetDetails = $this->handleBudgetSync($project, $spreadsheetId);
                $results['budget'] = [
                    'status' => 'success',
                    'details' => $budgetDetails,
                ];
            }

            if (in_array($type, ['progress_revenue', 'all', ''])) {
                $progressRevenueDetails = $this->syncProgressRevenue($project);
                $results['progress_revenue'] = $progressRevenueDetails;
                $results['wbs'] = $this->syncWbsData($project);
            }

            // Update last_synced_at timestamp on master_projects
            $project->update([
                'last_synced_at' => now(),
            ]);

            $results['status'] = 'success';
            $results['spreadsheet_id'] = $spreadsheetId;
        } catch (\Throwable $e) {
            Log::error("Failed syncing MasterProject [{$project->id}]: ".$e->getMessage(), [
                'project_id' => $project->id,
                'spreadsheet_id' => $spreadsheetId,
                'type' => $project->type,
                'trace' => $e->getTraceAsString(),
            ]);

            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Handler for procurement spreadsheet synchronization.
     *
     * @return array<string, mixed>
     */
    public function handleProcurementSync(MasterProject $project, ?string $spreadsheetId = null): array
    {
        $targetSpreadsheetId = $spreadsheetId ?: ($project->effective_spreadsheet_id ?: $this->defaultSpreadsheetId);
        $rows = $this->fetchSpreadsheetRows($targetSpreadsheetId, ['6. Procurement', 'Procurement']);
        $metadata = $this->parseProcurementMetadata($rows);
        $items = $this->parseProcurementRows($rows);

        // Update or create procurement-specific profile
        $project->profiles()->updateOrCreate(
            ['type' => 'procurement'],
            [
                'project_name' => ! empty($metadata['nama_proyek']) ? $metadata['nama_proyek'] : ($project->project_name ?? 'PROCUREMENT'),
                'nilai_kontrak' => $metadata['nilai_kontrak'] > 0 ? $metadata['nilai_kontrak'] : ($project->net_contract_value ?? 0),
                'nama_PA' => 'KURNIAWAN',
                'nama_pimpro' => $project->leader_name ?? 'SETIAJI',
            ]
        );

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
            'metadata' => $metadata,
        ];
    }

    /**
     * Handler for budget spreadsheet synchronization.
     *
     * @return array<string, mixed>
     */
    public function handleBudgetSync(MasterProject $project, ?string $spreadsheetId = null): array
    {
        $targetSpreadsheetId = $spreadsheetId ?: ($project->effective_spreadsheet_id ?: $this->defaultSpreadsheetId);
        $rows = $this->fetchSpreadsheetRows($targetSpreadsheetId, ['Budget Control', 'Budget', 'Sheet5']);
        $summary = $this->parseBudgetSummary($rows);
        $items = $this->parseBudgetRows($rows);
        $metadata = $this->parseBudgetMetadata($rows);

        // Update project name and tanggal update on master_projects
        $updatePayload = [];
        if (! empty($metadata['nama_proyek'])) {
            $updatePayload['project_name'] = $metadata['nama_proyek'];
        }
        if (! empty($metadata['tanggal_update'])) {
            $updatePayload['sheet_updated_at'] = $metadata['tanggal_update'];
        }

        if (! empty($updatePayload)) {
            $project->update($updatePayload);
        }

        // Update or create budget_control-specific profile
        $project->profiles()->updateOrCreate(
            ['type' => 'budget_control'],
            [
                'project_name' => ! empty($metadata['nama_proyek']) ? $metadata['nama_proyek'] : ($project->project_name ?? 'BUDGET CONTROL'),
                'nilai_kontrak' => $project->net_contract_value ?? 0,
                'nama_PA' => 'KURNIAWAN',
                'nama_pimpro' => $project->leader_name ?? 'SETIAJI',
            ]
        );

        Log::info('[Sync] Updated budget metadata & profile from sheet.', array_merge(
            ['master_project_id' => $project->id],
            $updatePayload
        ));

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
            'metadata' => $metadata,
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
     * Parse metadata (Nama Proyek, Tanggal Update) from Budget Control sheet rows.
     * Looks for rows where column A contains "Nama Proyek" or "Tanggal Update" (case-insensitive).
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{nama_proyek: string, tanggal_update: string}
     */
    public function parseBudgetMetadata(array $rows): array
    {
        $namaProyek = '';
        $tanggalUpdate = '';

        foreach ($rows as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $col0 = strtolower(trim((string) ($row[0] ?? '')));

            if ($col0 === 'nama proyek' || str_contains($col0, 'nama proyek')) {
                // Value can be in column B (index 1) or column C (index 2)
                $val = trim((string) ($row[1] ?? ''));
                if ($val === '') {
                    $val = trim((string) ($row[2] ?? ''));
                }
                $namaProyek = $val;
            }

            if ($col0 === 'tanggal update' || str_contains($col0, 'tanggal update')) {
                $val = trim((string) ($row[1] ?? ''));
                if ($val === '') {
                    $val = trim((string) ($row[2] ?? ''));
                }
                $tanggalUpdate = $val;
            }
        }

        return [
            'nama_proyek' => $namaProyek,
            'tanggal_update' => $tanggalUpdate,
        ];
    }

    /**
     * Parse metadata (Nama Proyek, Nilai Kontrak) from Procurement sheet rows.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{nama_proyek: string, nilai_kontrak: float}
     */
    public function parseProcurementMetadata(array $rows): array
    {
        $namaProyek = '';
        $nilaiKontrak = 0.0;

        foreach ($rows as $index => $row) {
            if ($index > 5 || empty(array_filter($row))) {
                continue;
            }

            $col0 = strtolower(trim((string) ($row[0] ?? '')));

            if ($col0 === 'nama proyek' || str_contains($col0, 'nama proyek')) {
                // Check cell in column 1, 2, or 3
                for ($c = 1; $c <= 3; $c++) {
                    $val = trim((string) ($row[$c] ?? ''));
                    if ($val !== '') {
                        $namaProyek = $val;
                        break;
                    }
                }
            }

            if ($col0 === 'nilai kontrak' || str_contains($col0, 'nilai kontrak')) {
                for ($c = 1; $c <= 3; $c++) {
                    $val = trim((string) ($row[$c] ?? ''));
                    if ($val !== '') {
                        $nilaiKontrak = $this->cleanNumber($val);
                        break;
                    }
                }
            }
        }

        return [
            'nama_proyek' => $namaProyek,
            'nilai_kontrak' => $nilaiKontrak,
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

    /**
     * Synchronize the "2. Progress & Revenue" tab for a MasterProject.
     *
     * Scope for this phase: the project profile block and the weekly summary
     * block only. The WBS block (row 22 and later) is intentionally ignored.
     *
     * @return array<string, mixed>
     */
    public function syncProgressRevenue(MasterProject $project): array
    {
        $targetSpreadsheetId = $project->effective_spreadsheet_id ?: $this->defaultSpreadsheetId;
        $targetTitle = $this->resolveSheetTitle($targetSpreadsheetId, ['2. Progress & Revenue', 'Progress & Revenue']);

        $logContext = [
            'master_project_id' => $project->id,
            'spreadsheet_id' => $targetSpreadsheetId,
            'sheet_title' => $targetTitle,
        ];

        $rows = $targetTitle !== null
            ? $this->fetchSpreadsheetRange($targetSpreadsheetId, $targetTitle, 'UNFORMATTED_VALUE')
            : [];

        if ($rows === []) {
            Log::warning('[ProgressRevenue] Sheet not found or empty', $logContext);

            return ['status' => 'no_data', 'sheet_title' => $targetTitle];
        }

        try {
            $headerInfo = $this->findProgressWeekHeaderRow($rows);
            $headerRowIndex = $headerInfo['row'] ?? null;
            $labelColumn = $headerInfo['column'] ?? 0;
            $periods = $this->parseProgressRevenueWeekHeaders($rows, $this->progressRevenueYear($project));
            $indicators = $headerRowIndex === null
                ? []
                : $this->parseProgressRevenueIndicators($rows, $periods, $headerRowIndex, $labelColumn);
            $profile = $this->parseProgressRevenueProfile($rows, $logContext);

            DB::transaction(function () use ($project, $profile, $periods, $indicators) {
                $project->profiles()->updateOrCreate(
                    ['type' => 'progress_revenue'],
                    [
                        'project_name' => $profile['project_name'],
                        'project_code' => $profile['project_code'] ?? null,
                        'client_name' => $profile['client_name'] ?? null,
                        'nilai_kontrak' => $profile['contract_value'],
                        'nama_pimpro' => $profile['project_lead_name'],
                        'nama_PA' => $profile['report_owner_name'],
                    ]
                );

                $projectCode = trim((string) ($profile['project_code'] ?? ''));
                $contractValue = (float) ($profile['contract_value'] ?? 0);

                foreach (['procurement', 'budget_control'] as $profileType) {
                    $targetProfile = $project->profiles()->where('type', $profileType)->first();
                    if (! $targetProfile) {
                        continue;
                    }

                    $fallbackValues = [];
                    if ($projectCode !== '' && trim((string) $targetProfile->project_code) === '') {
                        $fallbackValues['project_code'] = $projectCode;
                    }
                    if ($contractValue > 0 && (float) $targetProfile->nilai_kontrak <= 0) {
                        $fallbackValues['nilai_kontrak'] = $contractValue;
                    }

                    if ($fallbackValues !== []) {
                        $targetProfile->update($fallbackValues);
                    }
                }

                $masterUpdates = [];
                if (! empty($profile['project_code']) && trim((string) $profile['project_code']) !== '') {
                    $masterUpdates['project_code'] = trim((string) $profile['project_code']);
                }
                if (! empty($profile['client_name']) && trim((string) $profile['client_name']) !== '') {
                    $masterUpdates['client_name'] = trim((string) $profile['client_name']);
                }
                if (! empty($masterUpdates)) {
                    $project->update($masterUpdates);
                }

                foreach ($indicators as $indicatorKey => $indicator) {
                    foreach ($periods as $period) {
                        ProgressRevenueValue::updateOrCreate(
                            [
                                'master_project_id' => $project->id,
                                'year' => $period['year'],
                                'month' => $period['month'],
                                'week' => $period['week'],
                                'is_baseline' => $period['is_baseline'],
                                'indicator_key' => $indicatorKey,
                            ],
                            ['value' => $indicator['values'][$period['label']] ?? null]
                        );
                    }
                }
            });

            $result = [
                'status' => 'success',
                'sheet_title' => $targetTitle,
                'header_row' => $headerRowIndex !== null ? $headerRowIndex + 1 : null,
                'periods_count' => count($periods),
                'indicators_found' => array_keys($indicators),
            ];

            Log::info('[ProgressRevenue] Sync completed', array_merge($logContext, $result));

            return $result;
        } catch (\Throwable $e) {
            Log::error("Failed syncing ProgressRevenue [{$project->id}]: ".$e->getMessage(), array_merge($logContext, [
                'trace' => $e->getTraceAsString(),
            ]));

            return [
                'status' => 'failed',
                'sheet_title' => $targetTitle,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reporting year for the weekly grid. Defaults to 2026 and can be pinned
     * per spreadsheet through the master_projects.progress_revenue_year column.
     */
    protected function progressRevenueYear(MasterProject $project): int
    {
        $configured = $project->progress_revenue_year;

        return $configured !== null ? (int) $configured : 2026;
    }

    /**
     * Resolve the target tab title: prefer the configured GID, then fall back to
     * a case-insensitive title match. Never hardcodes a spreadsheet id or GID.
     */
    protected function resolveSheetTitle(string $spreadsheetId, array $preferredSheetTitles): ?string
    {
        try {
            $sheetsList = $this->service->spreadsheets->get($spreadsheetId)->getSheets() ?? [];
        } catch (\Throwable $e) {
            Log::error('Failed to list spreadsheet tabs: '.$e->getMessage(), [
                'spreadsheet_id' => $spreadsheetId,
            ]);

            return null;
        }

        foreach ($preferredSheetTitles as $preferred) {
            foreach ($sheetsList as $sheet) {
                if (stripos((string) $sheet->getProperties()->getTitle(), $preferred) !== false) {
                    return (string) $sheet->getProperties()->getTitle();
                }
            }
        }

        return null;
    }

    /**
     * Fetch a tab range with the given value render option.
     *
     * @return array<int, array<int, mixed>>
     */
    public function fetchSpreadsheetRange(string $spreadsheetId, string $title, string $valueRenderOption, string $range = 'A1:BZ200'): array
    {
        $response = $this->service->spreadsheets_values->get(
            $spreadsheetId,
            "'{$title}'!{$range}",
            ['valueRenderOption' => $valueRenderOption]
        );

        return $response->getValues() ?? [];
    }

    /**
     * Parse the project profile block: Nama Client, Kode Proyek, Nama Proyek,
     * Nilai Kontrak, % LKP Des 2025, Progress Tercapai, Anual Deviasi Target,
     * Target Progress 2026, Target % LKP Des 2026, Nama Pimpro,
     * Penanggungjawab Report.
     *
     * Labels are matched by normalized value (trim + lowercase + collapsed
     * whitespace) instead of a fixed cell address. Only the four mapped
     * columns are persisted; the rest are read for completeness and logged
     * on failure.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $logContext
     * @return array<string, mixed>
     */
    public function parseProgressRevenueProfile(array $rows, array $logContext = []): array
    {
        $mapping = [
            'nama client' => 'client_name',
            'kode proyek' => 'project_code',
            'nama proyek' => 'project_name',
            'nilai kontrak' => 'contract_value',
            '% lkp des 2025' => 'lkp_prev_year',
            'progress tercapai' => 'progress_achieved',
            'anual deviasi target' => 'annual_deviation_target',
            'target progress 2026' => 'target_progress',
            'target % lkp des 2026' => 'target_lkp',
            'nama pimpro' => 'project_lead_name',
            'penanggungjawab report' => 'report_owner_name',
        ];

        $profile = array_fill_keys(array_values($mapping), null);
        $found = [];

        foreach ($rows as $index => $row) {
            if (empty($row)) {
                continue;
            }

            // WBS block starts at row 22 (1-indexed) and is out of scope for this phase.
            if ($index + 1 >= 22) {
                break;
            }

            $label = $this->normalizeProgressLabel((string) ($row[0] ?? ''));

            if ($label === '' || ! isset($mapping[$label])) {
                continue;
            }

            $key = $mapping[$label];

            // Value lives in column C (index 2). D8 is intentionally not read.
            $rawValue = $row[2] ?? null;

            if ($rawValue === null || trim((string) $rawValue) === '') {
                continue;
            }

            if ($key === 'contract_value') {
                $profile[$key] = $this->cleanNumber((string) $rawValue);
            } elseif (in_array($key, ['lkp_prev_year', 'progress_achieved', 'annual_deviation_target', 'target_progress', 'target_lkp'], true)) {
                $profile[$key] = $this->cleanProgressValue($rawValue);
            } else {
                $profile[$key] = trim((string) $rawValue);
            }

            $found[$key] = true;
        }

        $missing = array_diff(array_values($mapping), array_keys($found));

        if ($missing !== []) {
            Log::warning('[ProgressRevenue] Missing profile labels, continuing with the rest', array_merge($logContext, [
                'missing_fields' => $missing,
            ]));
        }

        return $profile;
    }

    /**
     * Trim, lowercase, collapse whitespace for label matching.
     */
    protected function normalizeProgressLabel(string $value): string
    {
        return (string) preg_replace('/\s+/u', ' ', mb_strtolower(trim($value), 'UTF-8'));
    }

    /**
     * Locate the weekly header row by finding the cell whose value is "Week",
     * then parse every column to the right of it by cell value.
     *
     * "Des 2025" is the baseline (previous year); "Jan W1".."Des W4" are the
     * reporting periods of the configured year.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array{column: int, year: int, month: int, week: ?int, label: string, is_baseline: bool}>
     */
    public function parseProgressRevenueWeekHeaders(array $rows, int $year = 2026): array
    {
        $headerInfo = $this->findProgressWeekHeaderRow($rows);

        if ($headerInfo === null) {
            return [];
        }

        $headerRow = $headerInfo['row'];

        $periods = [];
        $baselineYear = null;

        // First pass: resolve the baseline column so the baseline year is known.
        foreach ($rows[$headerRow] as $column => $cell) {
            $label = (string) preg_replace('/\s+/u', ' ', trim((string) $cell));

            if (preg_match('/^([A-Za-z]{3})\s+(\d{4})$/', $label, $matches)) {
                $baselineYear = (int) $matches[2];
                break;
            }
        }

        foreach ($rows[$headerRow] as $column => $cell) {
            if ($column === 0) {
                continue;
            }

            $label = (string) preg_replace('/\s+/u', ' ', trim((string) $cell));

            if ($label === '') {
                continue;
            }

            if (preg_match('/^([A-Za-z]{3})\s+(\d{4})$/', $label, $matches)) {
                $periods[] = [
                    'column' => $column,
                    'year' => (int) $matches[2],
                    'month' => $this->monthNumber($matches[1]) ?? 12,
                    'week' => null,
                    'label' => ucfirst(strtolower($matches[1])).' '.(int) $matches[2],
                    'is_baseline' => true,
                ];

                continue;
            }

            if (preg_match('/^([A-Za-z]{3})\s+W(\d+)$/', $label, $matches)) {
                $month = $this->monthNumber($matches[1]);

                if ($month === null) {
                    continue;
                }

                $week = (int) $matches[2];

                if ($week < 1 || $week > 4) {
                    continue;
                }

                $periods[] = [
                    'column' => $column,
                    'year' => $year,
                    'month' => $month,
                    'week' => $week,
                    'label' => ucfirst(strtolower($matches[1])).' W'.$week,
                    'is_baseline' => false,
                ];
            }
        }

        $periods = array_merge($periods, $this->missingProgressPeriods($periods, $year));

        if ($periods !== [] && $baselineYear !== null) {
            Log::info('[ProgressRevenue] Parsed weekly header', [
                'header_row' => $headerRow + 1,
                'baseline_year' => $baselineYear,
                'period_count' => count($periods),
            ]);
        }

        return $periods;
    }

    /**
     * The sheet only carries the months that have been filled in, so a planning
     * grid that stops at, say, Nov W4 would otherwise leave December - and any
     * other not-yet-created column - missing from the table entirely. Pad the
     * full 12x4 grid so every week of the year exists, with a null column so
     * the value resolves to null instead of reading the wrong cell.
     *
     * ponytail: synthesises periods only, values stay null until the sheet gains
     * the column. Drop this once every sheet is guaranteed to ship 48 columns.
     *
     * @param  array<int, array{column: ?int, year: int, month: int, week: ?int, label: string, is_baseline: bool}>  $periods
     * @return array<int, array{column: ?int, year: int, month: int, week: int, label: string, is_baseline: false}>
     */
    private function missingProgressPeriods(array $periods, int $year): array
    {
        $present = [];

        foreach ($periods as $period) {
            if ($period['is_baseline']) {
                continue;
            }

            $present[$period['month'].'-'.$period['week']] = true;
        }

        $missing = [];

        for ($month = 1; $month <= 12; $month++) {
            for ($week = 1; $week <= 4; $week++) {
                if (isset($present[$month.'-'.$week])) {
                    continue;
                }

                $missing[] = [
                    'column' => null,
                    'year' => $year,
                    'month' => $month,
                    'week' => $week,
                    'label' => self::PROGRESS_MONTH_LABELS[$month - 1].' W'.$week,
                    'is_baseline' => false,
                ];
            }
        }

        return $missing;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{row: int, column: int}|null
     */
    private function findProgressWeekHeaderRow(array $rows): ?array
    {
        foreach ($rows as $index => $row) {
            foreach ($row as $colIndex => $cell) {
                if ($this->normalizeProgressLabel((string) $cell) === 'week') {
                    return ['row' => $index, 'column' => $colIndex];
                }
            }
        }

        return null;
    }

    /**
     * Map a 3-letter month label to its number. The sheet is written in
     * Indonesian, so both "Dec" and "Des" (and "Agu"/"Okt"/"Mei") are accepted.
     */
    private function monthNumber(string $shortLabel): ?int
    {
        $months = [
            'jan' => 1, 'januari' => 1,
            'feb' => 2, 'februari' => 2,
            'mar' => 3, 'maret' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5, 'mei' => 5,
            'jun' => 6, 'juni' => 6,
            'jul' => 7, 'juli' => 7,
            'aug' => 8, 'agu' => 8, 'agustus' => 8,
            'sep' => 9, 'september' => 9,
            'oct' => 10, 'okt' => 10, 'oktober' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'des' => 12, 'desember' => 12,
        ];

        return $months[mb_strtolower($shortLabel, 'UTF-8')] ?? null;
    }

    /**
     * Indicator labels, in display order. Rows are matched by normalized label,
     * never by row number.
     *
     * @return array<string, string>
     */
    public function progressRevenueIndicatorMap(): array
    {
        return [
            'rencana mingguan rkap' => 'rkap_weekly_plan',
            'akumulasi mingguan rkap' => 'rkap_weekly_cumulative',
            'rencana mingguan proyek' => 'project_weekly_plan',
            'akumulasi mingguan proyek' => 'project_weekly_cumulative',
            'realisasi mingguan proyek' => 'project_weekly_actual',
            'akumulasi realisasi proyek' => 'project_actual_cumulative',
            'deviasi proyek vs rkap' => 'deviation_vs_rkap',
        ];
    }

    /**
     * Parse indicator rows, matching by label.
     *
     * Only the weekly summary block is in scope for this phase. The WBS block
     * that follows it is skipped, so a WBS label can never be mistaken for an
     * indicator even if it sits on the same row range.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, array{column: int, year: int, month: int, week: ?int, label: string, is_baseline: bool}>  $periods
     * @param  int  $headerRow  The 0-indexed row where "Week" was found.
     * @param  int  $labelColumn  The 0-indexed column where "Week" was found (indicator labels live here).
     * @return array<string, array{label: string, values: array<string, float|null>}>
     */
    public function parseProgressRevenueIndicators(array $rows, array $periods, int $headerRow, int $labelColumn = 0): array
    {
        if ($periods === []) {
            return [];
        }

        $indicatorMap = $this->progressRevenueIndicatorMap();
        $indicators = [];
        $matched = [];

        foreach ($rows as $index => $row) {
            if ($index <= $headerRow) {
                continue;
            }

            $label = $this->normalizeProgressLabel((string) ($row[$labelColumn] ?? ''));

            if ($label === '') {
                continue;
            }

            // The WBS block starts the out-of-scope section: stop here.
            if ($this->isProgressWbsLabel($label)) {
                break;
            }

            if (! isset($indicatorMap[$label])) {
                continue;
            }

            $key = $indicatorMap[$label];
            $values = [];

            foreach ($periods as $period) {
                $values[$period['label']] = $this->cleanProgressValue($row[$period['column']] ?? null);
            }

            $indicators[$key] = [
                'label' => trim((string) $row[$labelColumn]),
                'values' => $values,
            ];

            $matched[$key] = true;
        }

        $missing = array_diff(array_values($indicatorMap), array_keys($matched));

        if ($missing !== []) {
            Log::warning('[ProgressRevenue] Missing indicator labels, continuing with the rest', [
                'missing_indicator_keys' => $missing,
            ]);
        }

        return $indicators;
    }

    /**
     * Labels that open the out-of-scope WBS block.
     */
    private function isProgressWbsLabel(string $normalizedLabel): bool
    {
        return in_array($normalizedLabel, ['wbs', 'wbs element', 'no', 'no. wbs', 'detail wbs'], true);
    }

    /**
     * Percentages are stored as fractions. UNFORMATTED_VALUE already returns
     * 5% as 0.05, so no extra scaling is applied. Blank cells stay null.
     */
    public function cleanProgressValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '' || $trimmed === '-') {
            return null;
        }

        if (! is_numeric($trimmed)) {
            return null;
        }

        return (float) $trimmed;
    }

    /**
     * Synchronize the WBS block (A19:BD) of the "2. Progress & Revenue" tab.
     *
     * @return array<string, mixed>
     */
    public function syncWbsData(MasterProject $project): array
    {
        $targetSpreadsheetId = $project->effective_spreadsheet_id ?: $this->defaultSpreadsheetId;
        $targetTitle = $this->resolveSheetTitle($targetSpreadsheetId, ['2. Progress & Revenue', 'Progress & Revenue']);

        $logContext = [
            'master_project_id' => $project->id,
            'spreadsheet_id' => $targetSpreadsheetId,
            'sheet_title' => $targetTitle,
        ];

        $rows = $targetTitle !== null
            ? $this->fetchSpreadsheetRange($targetSpreadsheetId, $targetTitle, 'UNFORMATTED_VALUE', 'A19:BD700')
            : [];

        if ($rows === []) {
            Log::warning('[WBS] Sheet not found or empty', $logContext);

            return ['status' => 'no_data', 'sheet_title' => $targetTitle];
        }

        try {
            $items = $this->parseWbsRows($rows, $this->progressRevenueYear($project));

            DB::transaction(function () use ($project, $items) {
                $project->wbsItems()->delete();

                $now = now();

                foreach ($items as $item) {
                    $weeklyValues = $item['weekly_values'];
                    unset($item['weekly_values']);

                    $model = WbsItem::query()->create([
                        ...$item,
                        'master_project_id' => $project->id,
                    ]);

                    $payload = [];

                    foreach ($weeklyValues as $weekly) {
                        $payload[] = [
                            'progress_wbs_item_id' => $model->id,
                            'type' => $weekly['type'],
                            'year' => $weekly['year'],
                            'month' => $weekly['month'],
                            'week' => $weekly['week'],
                            'period_label' => $weekly['period_label'],
                            'value' => $weekly['value'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    foreach (array_chunk($payload, 500) as $chunk) {
                        WbsWeeklyValue::query()->insert($chunk);
                    }
                }
            });

            $result = [
                'status' => 'success',
                'sheet_title' => $targetTitle,
                'wbs_count' => count($items),
            ];

            Log::info('[WBS] Sync completed', array_merge($logContext, $result));

            return $result;
        } catch (\Throwable $e) {
            Log::error("Failed syncing WBS [{$project->id}]: ".$e->getMessage(), array_merge($logContext, [
                'trace' => $e->getTraceAsString(),
            ]));

            return [
                'status' => 'failed',
                'sheet_title' => $targetTitle,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse WBS rows from A19:BD. Plan (P) and Actual (A) rows with the same
     * wbs_no become a single item. Parent bobot, schedule, and weekly values
     * are rolled up from descendants after every leaf is parsed.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function parseWbsRows(array $rows, int $year = 2026): array
    {
        $items = [];
        $orderIndex = 0;
        $lastWbsNo = null;
        $periods = $this->wbsWeekPeriods($year);

        foreach ($rows as $row) {
            if (! is_array($row) || $row === []) {
                continue;
            }

            $parsedNumber = $this->parseWbsNumber((string) ($row[0] ?? ''));
            $rowType = $this->parseWbsRowType((string) ($row[7] ?? ''));

            if ($parsedNumber === null && $rowType !== null && $lastWbsNo !== null) {
                $parsedNumber = $this->parseWbsNumber($lastWbsNo);
            }

            if ($parsedNumber === null) {
                continue;
            }

            $wbsNo = $parsedNumber['wbs_no'];
            $lastWbsNo = $wbsNo;

            if (! isset($items[$wbsNo])) {
                $items[$wbsNo] = [
                    'wbs_no' => $wbsNo,
                    'parent_wbs_no' => $parsedNumber['parent_wbs_no'],
                    'level' => $parsedNumber['level'],
                    'milestone' => null,
                    'activity_name' => null,
                    'pic' => null,
                    'divisi' => null,
                    'nilai_kegiatan' => 0.0,
                    'bobot_plan' => 0.0,
                    'bobot_real' => 0.0,
                    'plan_start_period' => null,
                    'plan_end_period' => null,
                    'actual_start_period' => null,
                    'actual_end_period' => null,
                    'durasi' => null,
                    'status' => 'on_track',
                    'order_index' => $orderIndex,
                    'weekly_values' => [],
                    '_weeks' => ['plan' => [], 'actual' => []],
                    '_is_leaf' => true,
                ];
                $orderIndex++;
            }

            $this->mergeWbsMetadata($items[$wbsNo], $row, $rowType);

            if ($rowType === null) {
                continue;
            }

            foreach ($periods as $period) {
                $value = $this->cleanProgressValue($row[$period['column']] ?? null);

                if ($value === null) {
                    continue;
                }

                $items[$wbsNo]['_weeks'][$rowType][$period['key']] = [
                    'type' => $rowType,
                    'year' => $period['year'],
                    'month' => $period['month'],
                    'week' => $period['week'],
                    'period_label' => $period['label'],
                    'value' => $value,
                    'ordinal' => $period['ordinal'],
                ];
            }
        }

        $this->normalizeWbsLeafBobot($items);
        $this->applyWbsLeafSchedules($items);
        $this->rollupWbsParents($items, $year);
        $this->finalizeWbsItems($items, $year);

        return array_values($items);
    }

    /**
     * 48 reporting weeks mapped to columns I..BD (indexes 8..55).
     *
     * @return array<int, array{column: int, year: int, month: int, week: int, label: string, key: string, ordinal: int}>
     */
    public function wbsWeekPeriods(int $year = 2026): array
    {
        $periods = [];

        for ($offset = 0; $offset < 48; $offset++) {
            $month = intdiv($offset, 4) + 1;
            $week = ($offset % 4) + 1;
            $label = $this->wbsPeriodLabel($month, $week);

            $periods[] = [
                'column' => 8 + $offset,
                'year' => $year,
                'month' => $month,
                'week' => $week,
                'label' => $label,
                'key' => $year.'-'.$month.'-'.$week,
                'ordinal' => $this->wbsPeriodOrdinal($year, $month, $week),
            ];
        }

        return $periods;
    }

    /**
     * @return array{wbs_no: string, parent_wbs_no: ?string, level: int}|null
     */
    public function parseWbsNumber(string $raw): ?array
    {
        $normalized = trim($raw);

        if ($normalized === '' || ! preg_match('/^\d+(?:\.\d+)*$/', $normalized)) {
            return null;
        }

        $segments = explode('.', $normalized);
        $level = count($segments);

        return [
            'wbs_no' => $normalized,
            'parent_wbs_no' => $level === 1 ? null : implode('.', array_slice($segments, 0, -1)),
            'level' => $level,
        ];
    }

    protected function parseWbsRowType(string $raw): ?string
    {
        $normalized = strtoupper(trim($raw));

        if (in_array($normalized, ['P', 'PLAN'], true)) {
            return 'plan';
        }

        if (in_array($normalized, ['A', 'ACTUAL', 'REAL'], true)) {
            return 'actual';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, mixed>  $row
     */
    protected function mergeWbsMetadata(array &$item, array $row, ?string $rowType): void
    {
        $milestone = $this->nullableTrim($row[1] ?? null);
        $activityName = $this->nullableTrim($row[2] ?? null);
        $pic = $this->nullableTrim($row[3] ?? null);
        $divisi = $this->nullableTrim($row[4] ?? null);
        $preferPlan = $rowType === 'plan' || $rowType === null;

        if ($milestone !== null && ($item['milestone'] === null || $preferPlan)) {
            $item['milestone'] = $milestone;
        }

        if ($activityName !== null && ($item['activity_name'] === null || $preferPlan)) {
            $item['activity_name'] = $activityName;
        }

        if ($pic !== null && ($item['pic'] === null || $preferPlan)) {
            $item['pic'] = $pic;
        }

        if ($divisi !== null && ($item['divisi'] === null || $preferPlan)) {
            $item['divisi'] = $divisi;
        }

    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    protected function normalizeWbsLeafBobot(array &$items): void
    {
        $rawByLeaf = [];
        $totalRaw = 0.0;
        $parentNumbers = [];

        foreach ($items as $item) {
            if ($item['parent_wbs_no'] !== null) {
                $parentNumbers[$item['parent_wbs_no']] = true;
            }
        }

        foreach ($items as $wbsNo => $item) {
            if (isset($parentNumbers[$wbsNo])) {
                continue;
            }

            $raw = array_sum(array_map(
                fn (array $week) => (float) $week['value'],
                array_values($item['_weeks']['plan'])
            ));

            $rawByLeaf[$wbsNo] = $raw;
            $totalRaw += $raw;
        }

        foreach ($rawByLeaf as $wbsNo => $raw) {
            $items[$wbsNo]['bobot_plan'] = $totalRaw > 0
                ? round(($raw / $totalRaw) * 100, 4)
                : 0.0;
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    protected function applyWbsLeafSchedules(array &$items): void
    {
        foreach ($items as &$item) {
            $planWeeks = array_values($item['_weeks']['plan']);
            $actualWeeks = array_values($item['_weeks']['actual']);

            $item['plan_start_period'] = $this->earliestWbsPeriodLabel($planWeeks);
            $item['plan_end_period'] = $this->latestWbsPeriodLabel($planWeeks);
            $item['actual_start_period'] = $this->earliestWbsPeriodLabel($actualWeeks);
            $item['actual_end_period'] = $this->latestWbsPeriodLabel($actualWeeks);
        }
        unset($item);
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    protected function rollupWbsParents(array &$items, int $year): void
    {
        $childrenByParent = [];

        foreach ($items as $wbsNo => $item) {
            $parentNo = $item['parent_wbs_no'];

            if ($parentNo === null) {
                continue;
            }

            $childrenByParent[$parentNo][] = $wbsNo;

            if (isset($items[$parentNo])) {
                $items[$parentNo]['_is_leaf'] = false;
            }
        }

        $levels = [];

        foreach ($items as $item) {
            $levels[$item['level']] = true;
        }

        krsort($levels);

        foreach (array_keys($levels) as $level) {
            foreach ($items as $wbsNo => &$item) {
                if ($item['level'] !== $level || ($item['_is_leaf'] ?? true) === true) {
                    continue;
                }

                $childNos = $childrenByParent[$wbsNo] ?? [];

                if ($childNos === []) {
                    continue;
                }

                $bobotPlan = 0.0;
                $bobotReal = 0.0;
                $planWeeks = [];
                $actualWeeks = [];
                $rolledWeeks = ['plan' => [], 'actual' => []];

                foreach ($childNos as $childNo) {
                    $child = $items[$childNo];
                    $bobotPlan += (float) $child['bobot_plan'];
                    $bobotReal += (float) $child['bobot_real'];

                    foreach (['plan', 'actual'] as $type) {
                        foreach ($child['_weeks'][$type] as $key => $week) {
                            if (! isset($rolledWeeks[$type][$key])) {
                                $rolledWeeks[$type][$key] = [
                                    ...$week,
                                    'value' => 0.0,
                                ];
                            }

                            $rolledWeeks[$type][$key]['value'] += (float) $week['value'];
                        }
                    }

                    foreach (['plan_start_period' => 'plan', 'plan_end_period' => 'plan', 'actual_start_period' => 'actual', 'actual_end_period' => 'actual'] as $field => $bucket) {
                        $label = $child[$field];

                        if ($label === null) {
                            continue;
                        }

                        $entry = [
                            'period_label' => $label,
                            'ordinal' => $this->wbsPeriodOrdinalFromLabel($label, $year),
                        ];

                        if ($bucket === 'plan') {
                            $planWeeks[] = $entry;
                        } else {
                            $actualWeeks[] = $entry;
                        }
                    }
                }

                $item['bobot_plan'] = round($bobotPlan, 4);
                $item['bobot_real'] = round($bobotReal, 4);
                $item['_weeks'] = $rolledWeeks;
                $item['plan_start_period'] = $this->earliestWbsPeriodLabel($planWeeks);
                $item['plan_end_period'] = $this->latestWbsPeriodLabel($planWeeks);
                $item['actual_start_period'] = $this->earliestWbsPeriodLabel($actualWeeks);
                $item['actual_end_period'] = $this->latestWbsPeriodLabel($actualWeeks);
            }
            unset($item);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    protected function finalizeWbsItems(array &$items, int $year): void
    {
        foreach ($items as &$item) {
            $weekly = [];

            foreach (['plan', 'actual'] as $type) {
                foreach ($item['_weeks'][$type] as $week) {
                    $weekly[] = [
                        'type' => $week['type'],
                        'year' => $week['year'],
                        'month' => $week['month'],
                        'week' => $week['week'],
                        'period_label' => $week['period_label'],
                        'value' => $week['value'],
                    ];
                }
            }

            $item['weekly_values'] = $weekly;
            $item['durasi'] = $this->wbsDurationLabel($item['plan_start_period'], $item['plan_end_period'], $year);
            $item['status'] = $this->resolveWbsStatus(
                $item['plan_start_period'],
                $item['plan_end_period'],
                $item['actual_start_period'],
                $item['actual_end_period'],
                $year
            );

            unset($item['_weeks'], $item['_is_leaf']);
        }
        unset($item);
    }

    /**
     * @param  array<int, array{period_label: string, ordinal: ?int}>  $weeks
     */
    protected function earliestWbsPeriodLabel(array $weeks): ?string
    {
        $filtered = array_values(array_filter($weeks, fn (array $week) => ($week['ordinal'] ?? null) !== null));

        if ($filtered === []) {
            return null;
        }

        usort($filtered, fn (array $a, array $b) => $a['ordinal'] <=> $b['ordinal']);

        return $filtered[0]['period_label'];
    }

    /**
     * @param  array<int, array{period_label: string, ordinal: ?int}>  $weeks
     */
    protected function latestWbsPeriodLabel(array $weeks): ?string
    {
        $filtered = array_values(array_filter($weeks, fn (array $week) => ($week['ordinal'] ?? null) !== null));

        if ($filtered === []) {
            return null;
        }

        usort($filtered, fn (array $a, array $b) => $a['ordinal'] <=> $b['ordinal']);

        return $filtered[array_key_last($filtered)]['period_label'];
    }

    public function resolveWbsStatus(
        ?string $planStart,
        ?string $planEnd,
        ?string $actualStart,
        ?string $actualEnd,
        int $year = 2026
    ): string {
        $planOrdinal = $this->wbsPeriodOrdinalFromLabel($planEnd ?? '', $year)
            ?? $this->wbsPeriodOrdinalFromLabel($planStart ?? '', $year);
        $actualOrdinal = $this->wbsPeriodOrdinalFromLabel($actualEnd ?? '', $year)
            ?? $this->wbsPeriodOrdinalFromLabel($actualStart ?? '', $year);

        if ($planOrdinal === null || $actualOrdinal === null) {
            return 'on_track';
        }

        $delay = $actualOrdinal - $planOrdinal;

        if ($delay <= 0) {
            return 'on_track';
        }

        if ($delay <= 1) {
            return 'overdue';
        }

        return 'critical';
    }

    protected function wbsDurationLabel(?string $start, ?string $end, int $year): ?string
    {
        $startOrdinal = $this->wbsPeriodOrdinalFromLabel($start ?? '', $year);
        $endOrdinal = $this->wbsPeriodOrdinalFromLabel($end ?? '', $year);

        if ($startOrdinal === null || $endOrdinal === null) {
            return null;
        }

        return ($endOrdinal - $startOrdinal + 1).' minggu';
    }

    protected function wbsPeriodLabel(int $month, int $week): string
    {
        return self::PROGRESS_MONTH_LABELS[$month - 1].' W'.$week;
    }

    protected function wbsPeriodOrdinal(int $year, int $month, int $week): int
    {
        return ($year * 48) + (($month - 1) * 4) + ($week - 1);
    }

    protected function wbsPeriodOrdinalFromLabel(string $label, int $year): ?int
    {
        if (! preg_match('/^([A-Za-z]{3})\s+W(\d+)$/', trim($label), $matches)) {
            return null;
        }

        $month = $this->monthNumber($matches[1]);
        $week = (int) $matches[2];

        if ($month === null || $week < 1 || $week > 4) {
            return null;
        }

        return $this->wbsPeriodOrdinal($year, $month, $week);
    }

    protected function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
