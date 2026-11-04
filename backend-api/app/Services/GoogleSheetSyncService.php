<?php

namespace App\Services;

use App\Models\GanttActivity;
use App\Models\Procurement;
use App\Models\MasterProject;
use App\Models\ProjectLink;
use App\Models\ProjectProfile;
use App\Models\SystemSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Detail restore (frontend/parser jalan) + kolom ERD dipertahankan.
 */
class GoogleSheetSyncService
{
    protected string $sheetId;
    protected string $apiKey;
    protected string $credentialsPath;

    public function __construct()
    {
        $this->sheetId = config('services.google.sheet_id') ?? env('GOOGLE_SHEET_ID', '');
        $this->apiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY', '');
        $this->credentialsPath = config('services.google.credentials_path') ?? env('GOOGLE_CREDENTIALS_PATH', '');
    }

    /**
     * Get OAuth2 access token from service account.
     */
    protected function getAccessToken(): ?string
    {
        $credPath = base_path($this->credentialsPath);
        if (! file_exists($credPath)) {
            Log::warning('Google credentials file not found: ' . $credPath);
            return null;
        }

        $credentials = json_decode(file_get_contents($credPath), true);
        $clientEmail = $credentials['client_email'] ?? '';
        $privateKey = $credentials['private_key'] ?? '';

        if (! $clientEmail || ! $privateKey) {
            return null;
        }

        $now = time();
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signature = '';
        openssl_sign("{$header}.{$payload}", $signature, $privateKey, 'SHA256');
        $jwt = "{$header}.{$payload}." . base64_encode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('Google OAuth token failed: ' . $response->body());
        return null;
    }

    /**
     * Pastikan header kolom "STATUS TERMIN" (BL..BR) ada di sheet procurement.
     * Baris 5 = judul blok, baris 6 = sub-judul (DP, T1..T6) meniru pola PAYMENT STATUS.
     * Grid sheet mungkin masih 63 kolom (maks BK) -> perluas dulu bila perlu.
     */
    protected function ensureProcurementTerminHeader(array $rows, string $sheetName, string $spreadsheetId): void
    {
        $headerRow = $rows[4] ?? [];
        $current = trim((string) ($headerRow[63] ?? ''));
        if ($current !== '') {
            return; // sudah ada
        }

        $this->ensureSheetColumns($sheetName, $spreadsheetId, 70);

        $subLabels = ['DP', 'T1', 'T2', 'T3', 'T4', 'T5', 'T6'];
        $colLetters = ['BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR'];

        $this->writeBackToSheet($sheetName, 'BL5', 'STATUS TERMIN', $spreadsheetId);
        foreach ($subLabels as $i => $label) {
            $this->writeBackToSheet($sheetName, "{$colLetters[$i]}6", $label, $spreadsheetId);
        }
    }

    /**
     * Dipanggil dari endpoint termin sebelum write-back ke kolom baru (BL..BR).
     */
    public function ensureProcurementColumnsPublic(string $sheetName, string $spreadsheetId): bool
    {
        return $this->ensureSheetColumns($sheetName, $spreadsheetId, 70);
    }

    /**
     * Perluas jumlah kolom grid sheet bila saat ini < $minColumns (appendDimension).
     */
    protected function ensureSheetColumns(string $sheetName, string $spreadsheetId, int $minColumns): bool
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return false;
        }

        $metaUrl = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}?fields=sheets.properties";
        $meta = Http::withToken($token)->get($metaUrl);
        if (! $meta->successful()) {
            Log::warning('Sheet metadata fetch failed: ' . $meta->body());
            return false;
        }

        $target = null;
        foreach ($meta->json('sheets') ?? [] as $sh) {
            if (($sh['properties']['title'] ?? '') === $sheetName) {
                $target = $sh['properties'];
                break;
            }
        }
        if (! $target) {
            return false;
        }

        $gridCols = (int) ($target['gridProperties']['columnCount'] ?? 0);
        $sheetId = $target['sheetId'];
        $delta = $minColumns - $gridCols;
        if ($delta <= 0) {
            return true;
        }

        $res = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}:batchUpdate", [
                'requests' => [
                    [
                        'appendDimension' => [
                            'sheetId' => $sheetId,
                            'dimension' => 'COLUMNS',
                            'length' => $delta,
                        ],
                    ],
                ],
            ]);

        if (! $res->successful()) {
            Log::error('Sheet appendDimension failed: ' . $res->body());
            return false;
        }

        Log::info("Sheet '{$sheetName}' columns extended by {$delta} to {$minColumns}.");
        return true;
    }

    /**
     * Write a value to a specific cell in Google Sheets.
     * e.g. writeBackToSheet('6. Procurement', 'F15', 'KONTRAK')
     */
    public function writeBackToSheet(string $sheetName, string $range, string $value, ?string $spreadsheetId = null): bool
    {
        $token = $this->getAccessToken();
        if (! $token) {
            Log::warning('Cannot write to sheet: no access token');
            return false;
        }

        $sid = $spreadsheetId ?? $this->sheetId;
        $fullRange = "'{$sheetName}'!{$range}";
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sid}/values/{$fullRange}?valueInputOption=USER_ENTERED";

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->put($url, [
                'range' => $fullRange,
                'majorDimension' => 'ROWS',
                'values' => [[$value]],
            ]);

        if ($response->successful()) {
            return true;
        }

        Log::error('Sheet write failed: ' . $response->body());
        return false;
    }

    /**
     * Find the row number in sheet data where column A matches the procurement `no`.
     * Returns 1-based row number (sheet rows start at 1).
     */
    public function findProcurementRow(?int $projectId, string $no): ?int
    {
        $record = ProjectLink::where('master_project_id', $projectId)
            ->where('sheet_key', 'procurement')
            ->first();

        if (! $record || empty($record->data)) {
            return null;
        }

        $rows = $record->data;
        foreach ($rows as $index => $row) {
            $cellNo = trim((string) ($row[0] ?? ''));
            if ($cellNo === $no) {
                return $index + 1; // 1-based
            }
        }

        return null;
    }

    public function syncAll(?int $projectId = null): array
    {
        $resProgress = $this->syncProgressAndRevenue($projectId);
        $resProcurement = $this->syncProcurement($projectId);

        return [
            'status' => ($resProgress['success'] && $resProcurement['success']) ? 'success' : 'partial',
            'master_project_id' => $projectId,
            'project_id' => $projectId,
            'progress_and_revenue' => $resProgress,
            'procurement' => $resProcurement,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    protected function resolveSheetId(?int $projectId): string
    {
        if ($projectId) {
            $mp = MasterProject::find($projectId);
            if ($mp?->spreadsheet_id) {
                return $mp->spreadsheet_id;
            }
            $link = ProjectLink::where('master_project_id', $projectId)
                ->whereNotNull('sheet_url')->first();
            $sid = $link?->spreadsheet_id;
            if ($sid) {
                return $sid;
            }
        }

        return $this->sheetId;
    }

    protected function sheetUrl(string $spreadsheetId): string
    {
        return 'https://docs.google.com/spreadsheets/d/' . $spreadsheetId;
    }

    public function syncProgressAndRevenue(?int $projectId = null): array
    {
        $targetProject = $projectId ? MasterProject::find($projectId) : MasterProject::first();
        $sheetIdToUse = $this->resolveSheetId($targetProject?->id ?? $projectId);

        if (! $sheetIdToUse || ! $this->apiKey) {
            return ['success' => false, 'message' => 'Credentials Google Sheet belum lengkap di .env atau database'];
        }

        $sheetName = '2. Progress & Revenue';
        $range = "'{$sheetName}'!A1:BD250";
        $apiUrl = 'https://sheets.googleapis.com/v4/spreadsheets/' . $sheetIdToUse . '/values/' . urlencode($range);

        try {
            $response = Http::get($apiUrl, [
                'key' => $this->apiKey,
                'valueRenderOption' => 'FORMATTED_VALUE',
                'dateTimeRenderOption' => 'FORMATTED_STRING',
                't' => time(),
            ]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Gagal mengambil data dari Google Sheets API', 'details' => $response->json()];
            }

            $rows = $response->json()['values'] ?? [];

            DB::transaction(function () use ($rows, $sheetName, $projectId, $sheetIdToUse) {
                $project = $this->populateProjectSummary($rows, $projectId, $sheetIdToUse);

                ProjectLink::updateOrCreate(
                    [
                        'master_project_id' => $project->id,
                        'sheet_name' => $sheetName,
                    ],
                    [
                        'sheet_key' => 'progress_revenue',
                        'sheet_url' => $this->sheetUrl($sheetIdToUse),
                        'data' => $rows,
                        'row_count' => count($rows),
                        'last_synced_at' => now(),
                    ]
                );

                $this->populateGanttActivities($rows, $project->id);

                $this->populateProjectProfile($rows, $project->id, 'progress_revenue');

                SystemSync::updateOrCreate(
                    ['key' => 'progress_revenue'],
                    ['last_synced_at' => now()]
                );
            }, 5);

            return [
                'success' => true,
                'rows' => count($rows),
                'message' => 'Sheet 2. Progress & Revenue berhasil disinkronkan.',
            ];
        } catch (\Throwable $e) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Sync Progress & Revenue Error: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function syncProcurement(?int $projectId = null): array
    {
        $targetProject = $projectId ? MasterProject::find($projectId) : MasterProject::first();
        $sheetIdToUse = $this->resolveSheetId($targetProject?->id ?? $projectId);

        if (! $sheetIdToUse || ! $this->apiKey) {
            return ['success' => false, 'message' => 'Credentials Google Sheet belum lengkap di .env atau database'];
        }

        $sheetName = '6. Procurement';
        $range = "'{$sheetName}'!A1:BR500";
        $apiUrl = 'https://sheets.googleapis.com/v4/spreadsheets/' . $sheetIdToUse . '/values/' . urlencode($range);

        try {
            $response = Http::get($apiUrl, [
                'key' => $this->apiKey,
                'valueRenderOption' => 'UNFORMATTED_VALUE',
                'dateTimeRenderOption' => 'FORMATTED_STRING',
                't' => time(),
            ]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Gagal mengambil data dari Google Sheets API', 'details' => $response->json()];
            }

            $rows = $response->json()['values'] ?? [];

            if (! $targetProject) {
                $targetProject = MasterProject::firstOrCreate(
                    ['project_code' => 'B-1234567'],
                    ['project_name' => 'REFURBISHMENT KRI SIGMA', 'spreadsheet_id' => $sheetIdToUse]
                );
            }

            DB::transaction(function () use ($rows, $sheetName, $targetProject, $sheetIdToUse) {
                ProjectLink::updateOrCreate(
                    [
                        'master_project_id' => $targetProject->id,
                        'sheet_name' => $sheetName,
                    ],
                    [
                        'sheet_key' => 'procurement',
                        'sheet_url' => $this->sheetUrl($sheetIdToUse),
                        'data' => $rows,
                        'row_count' => count($rows),
                        'last_synced_at' => now(),
                    ]
                );

                $this->populateProcurements($rows, $targetProject->id);

                // Header sheet procurement: "Nama Proyek" + "Nilai Kontrak" (nilai di kolom C)
                $procName = trim((string) ($rows[1][2] ?? ''));
                $procContractRaw = trim((string) ($rows[2][2] ?? ''));
                $procContract = is_numeric($procContractRaw)
                    ? number_format((float) $procContractRaw, 0, '', ',')
                    : ($procContractRaw !== '' ? $procContractRaw : null);
                $sibling = ProjectProfile::where('master_project_id', $targetProject->id)
                    ->where('type', 'progress_revenue')
                    ->first();
                $this->populateProjectProfile($rows, $targetProject->id, 'procurement', [
                    'project_name' => $procName !== '' ? $procName : null,
                    'contract_value' => $procContract !== '' ? $procContract : null,
                    'pimpro_name' => $sibling?->pimpro_name,
                    'report_pic' => $sibling?->report_pic,
                ]);

                SystemSync::updateOrCreate(
                    ['key' => 'procurement'],
                    ['last_synced_at' => now()]
                );
            }, 5);

            $this->ensureProcurementTerminHeader($rows, $sheetName, $sheetIdToUse);

            return [
                'success' => true,
                'rows' => count($rows),
                'message' => 'Sheet 6. Procurement berhasil disinkronkan.',
            ];
        } catch (\Throwable $e) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Sync Procurement Error: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function populateProjectSummary(array $rows, ?int $projectId = null, ?string $spreadsheetId = null): MasterProject
    {
        $projectName = 'REFURBISHMENT KRI SIGMA';
        $projectCode = 'B-1234567';
        $contractValue = 'Rp 0';
        $progressTrend = '0%';

        foreach ($rows as $row) {
            $key = strtolower(trim($row[0] ?? ''));
            $val = trim($row[2] ?? ($row[3] ?? ($row[1] ?? '')));
            if (! $key) {
                continue;
            }
            if (str_contains($key, 'nama proyek') && $val) {
                $projectName = $val;
            } elseif (str_contains($key, 'kode proyek') && $val) {
                $projectCode = $val;
            } elseif (str_contains($key, 'nilai kontrak') && $val) {
                $cleanNum = preg_replace('/[^0-9]/', '', $val);
                $contractValue = is_numeric($cleanNum) ? 'Rp ' . number_format((float) $cleanNum, 0, ',', '.') : $val;
            } elseif (str_contains($key, 'trend') && $val) {
                $progressTrend = str_contains($val, '%') ? $val : "{$val}%";
            }
        }

        $totalActual = 0;
        foreach ($rows as $row) {
            $typeAP = strtoupper(trim($row[7] ?? ''));
            if ($typeAP === 'A') {
                for ($i = 8; $i < count($row); $i++) {
                    $cell = trim($row[$i] ?? '');
                    if ($cell && $cell !== '0' && $cell !== '0%' && $cell !== '-') {
                        $num = (float) str_replace('%', '', $cell);
                        $totalActual += $num;
                    }
                }
            }
        }

        $attrs = [
            'project_name' => $projectName,
            'spreadsheet_id' => $spreadsheetId ?? $this->sheetId,
        ];

        if ($projectId) {
            $project = MasterProject::find($projectId);
            if ($project) {
                $project->update($attrs);

                return $project;
            }
        }

        return MasterProject::updateOrCreate(['project_code' => $projectCode], $attrs);
    }

    protected function populateProjectProfile(array $rows, int $projectId, string $type, array $overrides = []): void
    {
        // Baris header sheet "2. Progress & Revenue": label di kolom A, nilai di kolom C
        $get = function (string $label) use ($rows): ?string {
            foreach ($rows as $row) {
                $key = strtolower(trim($row[0] ?? ''));
                if ($key === strtolower($label)) {
                    $val = trim((string) ($row[2] ?? ''));
                    return $val !== '' ? $val : null;
                }
            }
            return null;
        };

        ProjectProfile::updateOrCreate(
            ['master_project_id' => $projectId, 'type' => $type],
            array_merge(
                [
                    'project_name' => $get('nama proyek'),
                    'pimpro_name' => $get('nama pimpro'),
                    'report_pic' => $get('penanggungjawab report'),
                    'contract_value' => $get('nilai kontrak'),
                ],
                $overrides
            )
        );
    }

    protected function populateGanttActivities(array $rows, int $projectId): void
    {
        GanttActivity::where('master_project_id', $projectId)->delete();

        $months = ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
        $weeks = ['W1', 'W2', 'W3', 'W4'];
        $flatWeeks = [];
        foreach ($months as $m) {
            foreach ($weeks as $w) {
                $flatWeeks[] = "{$m}_{$w}";
            }
        }

        $toInsert = [];
        foreach ($rows as $row) {
            $colA = trim($row[0] ?? '');
            $colB = trim($row[1] ?? '');
            $colC = trim($row[2] ?? '');
            $wbsName = $colC ?: ($colB ?: $colA);
            $typeAP = strtoupper(trim($row[7] ?? ''));
            if (! $wbsName || ! in_array($typeAP, ['P', 'A'])) {
                continue;
            }
            $fullText = strtolower("{$colA} {$colB} {$colC}");
            if (
                str_contains($fullText, 'kode proyek') ||
                str_contains($fullText, 'nama proyek') ||
                str_contains($fullText, 'nilai kontrak') ||
                str_contains($fullText, 'target progress') ||
                str_contains($fullText, 'realisasi') ||
                str_contains($fullText, 'deviasi')
            ) {
                continue;
            }

            $startWeek = null;
            $endWeek = null;
            for ($i = 0; $i < 48; $i++) {
                $cellVal = trim($row[8 + $i] ?? '');
                if ($cellVal && $cellVal !== '0' && $cellVal !== '0%' && $cellVal !== '-') {
                    $weekKey = $flatWeeks[$i] ?? "W_{$i}";
                    if (! $startWeek) {
                        $startWeek = $weekKey;
                    }
                    $endWeek = $weekKey;
                }
            }

            $weight = trim($row[4] ?? ($row[5] ?? ''));
            $progressPct = (int) preg_replace('/[^0-9]/', '', (string) $weight);

            $toInsert[] = [
                'master_project_id' => $projectId,
                'activity_name' => $wbsName,
                'start_date' => $this->weekToDate($startWeek, true),
                'end_date' => $this->weekToDate($endWeek, false),
                'progress_percentage' => $progressPct,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($toInsert)) {
            GanttActivity::insert($toInsert);
        }
    }

    /**
     * Konversi AGUSTUS_W4 -> tanggal acuan 2026.
     * W1=1-7, W2=8-14, W3=15-21, W4=22-akhir bulan.
     */
    protected function weekToDate(?string $weekKey, bool $isStart = true): ?string
    {
        if (! $weekKey || ! str_contains($weekKey, '_')) {
            return null;
        }
        [$mon, $w] = explode('_', $weekKey, 2);
        $map = [
            'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4,
            'MEI' => 5, 'JUNI' => 6, 'JULI' => 7, 'AGUSTUS' => 8,
            'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
        ];
        $mm = $map[strtoupper($mon)] ?? null;
        if (! $mm) {
            return null;
        }
        $year = 2026;
        $last = cal_days_in_month(CAL_GREGORIAN, $mm, $year);
        $day = match ($w) {
            'W1' => $isStart ? 1 : 7,
            'W2' => $isStart ? 8 : 14,
            'W3' => $isStart ? 15 : 21,
            default => $isStart ? 22 : $last,
        };
        $day = min($day, $last);

        return sprintf('%04d-%02d-%02d', $year, $mm, $day);
    }

    protected function populateProcurements(array $rows, int $projectId): void
    {
        // Simpan edit user sebelum delete (progress, priority, status + kontrak/payment/delivery)
        $userEdits = Procurement::where('master_project_id', $projectId)
            ->whereIn('progress', ['SPH', 'HPS', 'RKS', 'PR', 'RFQ', 'NEGOSIASI', 'PO', 'KONTRAK', 'PRODUCTION', 'DELIVERED'])
            ->orWhereIn('priority', ['High', 'Medium', 'Low'])
            ->get()
            ->keyBy('no')
            ->map(fn ($p) => [
                'progress' => $p->progress,
                'priority' => $p->priority,
                'status' => $p->status,
                'kontrak_status' => $p->kontrak_status,
                'payment_priority' => $p->payment_priority,
                'delivery_status' => $p->delivery_status,
            ])
            ->toArray();

        Procurement::where('master_project_id', $projectId)->delete();

        $parseNumber = function ($val) {
            if (! $val && $val !== 0) {
                return 0;
            }
            if (is_numeric($val)) {
                return (float) $val;
            }
            $clean = preg_replace('/[^0-9,-]/', '', (string) $val);
            $clean = str_replace(',', '.', $clean);

            return is_numeric($clean) ? (float) $clean : 0;
        };

        $toInsert = [];
        foreach ($rows as $row) {
            $no = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            if (! $no || strtoupper($no) === 'NO' || strtoupper($name) === 'NAMA PENGADAAN') {
                continue;
            }
            if (! $name) {
                continue;
            }

            $priorityRaw = strtolower(trim((string) ($row[6] ?? '')));
            $sheetPriority = match ($priorityRaw) {
                'high' => 'High',
                'low' => 'Low',
                default => 'Medium',
            };

            // Pakai edit user kalau ada, kalau gak pakai dari sheet
            $userEdit = $userEdits[$no] ?? null;

            $toInsert[] = [
                'master_project_id' => $projectId,
                'no' => $no,
                'name' => $name,
                'vendor' => trim((string) ($row[2] ?? '-')),
                'target_tgl' => trim((string) ($row[3] ?? '-')),
                'jenis' => trim((string) ($row[7] ?? '-')),
                'payment_type' => trim((string) ($row[8] ?? '-')),
                'proc_policy' => trim((string) ($row[9] ?? '-')),
                'no_hps' => trim((string) ($row[10] ?? '-')),
                'tanggal_hps' => trim((string) ($row[11] ?? '-')),
                'rks' => trim((string) ($row[12] ?? '-')),
                'tanggal_rks' => trim((string) ($row[13] ?? '-')),
                'pic_ome' => trim((string) ($row[14] ?? '-')),
                'no_pr' => trim((string) ($row[15] ?? '-')),
                'proc_ops' => trim((string) ($row[16] ?? '-')),
                'no_po' => trim((string) ($row[17] ?? '-')),
                'kontrak_status' => $userEdit['kontrak_status'] ?? trim((string) ($row[18] ?? '-')),
                'nilai_rab' => $parseNumber($row[19] ?? 0),
                'realisasi' => $parseNumber($row[20] ?? 0),
                'mata_uang' => trim((string) ($row[21] ?? 'IDR')),
                'due_date' => trim((string) ($row[23] ?? '-')),
                'status' => $userEdit['status'] ?? trim((string) ($row[4] ?? 'In Progress')),
                'progress' => $userEdit['progress'] ?? trim((string) ($row[5] ?? '-')),
                'priority' => $userEdit['priority'] ?? $sheetPriority,
                'payment_priority' => $userEdit['payment_priority'] ?? trim((string) ($row[53] ?? '-')),
                'delivery_status' => $userEdit['delivery_status'] ?? trim((string) ($row[55] ?? '-')),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($toInsert)) {
            Procurement::insert($toInsert);
        }
    }
}
