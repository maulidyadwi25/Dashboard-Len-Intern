<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetsService
{
    protected Sheets $service;
    protected string $spreadsheetId;

    public function __construct()
    {
        $this->spreadsheetId = config('services.google_sheets.spreadsheet_id');

        $client = new Client();
        $client->setApplicationName('Laravel Project Dashboard');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig(base_path(config('services.google_sheets.credentials_path')));

        $this->service = new Sheets($client);
    }

    public function getProjectDashboard(): array
    {
        $spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
        $sheetsList = $spreadsheet->getSheets();

        // 1. Deteksi Tab Sheet 1 & Sheet 2 secara dinamis
        $progressSheetTitle = $sheetsList[0]->getProperties()->getTitle();
        $budgetSheetTitle = $sheetsList[1]->getProperties()->getTitle() ?? $progressSheetTitle;

        foreach ($sheetsList as $s) {
            $title = $s->getProperties()->getTitle();
            if (trim($title) === 'Sheet5' || str_contains(strtolower($title), 'budget')) {
                $budgetSheetTitle = $title;
                break;
            }
        }

        // 2. Tarik kedua sheet dalam 1 request batch (Rentang Sheet 1 diperluas ke A1:G30)
        $response = $this->service->spreadsheets_values->batchGet($this->spreadsheetId, [
            'ranges' => [
                "'{$progressSheetTitle}'!A1:G30",
                "'{$budgetSheetTitle}'!A1:F35",
            ],
        ]);

        $valueRanges = $response->getValueRanges();
        $sheet1Rows = $valueRanges[0]->getValues() ?? [];
        $sheet2Rows = $valueRanges[1]->getValues() ?? [];

        $metadata = [
            'nama_proyek'        => 'ABCDE PROJECT NAME',
            'kode_proyek'        => '-',
            'nama_pimpro'        => '-',
            'nama_client'        => '-',
            'net_contract_value' => 0.0,
            'tanggal_update'     => '-',
        ];

        $summary = [
            'sisa_anggaran_sap'  => 0,
            'wbs_belum_input'    => 0,
            'total'              => 0,
            'estimasi_kebutuhan' => 0,
            'selisih_variance'   => 0,
        ];

        $breakdown = [];
        $no = 1;

        // 3. Parse Sheet 1: Kode Proyek, Nama Pimpro, Nama Client, & Net Contract Value
        foreach ($sheet1Rows as $row) {
            if (empty(array_filter($row))) continue;

            $colA = strtolower(trim((string)($row[0] ?? '')));

            // Cari isi sel pertama yang valid setelah Kolom A (melewati sel kosong atau ':')
            $rowValue = null;
            for ($i = 1; $i < count($row); $i++) {
                $val = trim((string)($row[$i] ?? ''));
                if ($val !== '' && $val !== ':') {
                    $rowValue = $val;
                    break;
                }
            }

            if ($rowValue === null) continue;

            // Mapping Kode Proyek
            if (str_contains($colA, 'kode proyek') || str_contains($colA, 'project code')) {
                $metadata['kode_proyek'] = $rowValue;
            }
            // Mapping Nama Pimpro / Project Manager
            elseif (
                str_contains($colA, 'pimpro') ||
                str_contains($colA, 'pimpinan proyek') ||
                str_contains($colA, 'project manager') ||
                str_contains($colA, 'nama pimpro')
            ) {
                $metadata['nama_pimpro'] = $rowValue;
            }
            // Mapping Nama Client / Klien
            elseif (
                str_contains($colA, 'client') ||
                str_contains($colA, 'klien') ||
                str_contains($colA, 'customer') ||
                str_contains($colA, 'pemberi kerja')
            ) {
                $metadata['nama_client'] = $rowValue;
            }
            // Mapping Net Contract Value / Nilai Kontrak
            elseif (
                str_contains($colA, 'contract') ||
                str_contains($colA, 'kontrak') ||
                str_contains($colA, 'nilai kontrak')
            ) {
                $metadata['net_contract_value'] = $this->cleanNumber($rowValue);
            }
        }

        // 4. Parse Sheet 2: Metadata Proyek, Total KPI, dan Rincian WBS
        foreach ($sheet2Rows as $row) {
            if (empty(array_filter($row))) continue;

            $colA = trim((string)($row[0] ?? ''));
            $lowerColA = strtolower($colA);

            if ($lowerColA === 'nama proyek') {
                $metadata['nama_proyek'] = $row[1] ?? $metadata['nama_proyek'];
                continue;
            }

            if ($lowerColA === 'tanggal update') {
                $metadata['tanggal_update'] = $row[1] ?? $metadata['tanggal_update'];
                continue;
            }

            if ($lowerColA === 'total') {
                $summary = [
                    'sisa_anggaran_sap'  => $this->cleanNumber($row[1] ?? 0),
                    'wbs_belum_input'    => $this->cleanNumber($row[2] ?? 0),
                    'total'              => $this->cleanNumber($row[3] ?? 0),
                    'estimasi_kebutuhan' => $this->cleanNumber($row[4] ?? 0),
                    'selisih_variance'   => $this->cleanNumber($row[5] ?? 0),
                ];
                continue;
            }

            // Filter pengecualian Header, Efisiensi, dan Kurs
            if (
                in_array($lowerColA, ['budget control', 'wbs', 'efisiensi', 'kurs']) ||
                str_starts_with($lowerColA, 'efisiensi') ||
                str_starts_with($lowerColA, 'kurs')
            ) {
                continue;
            }

            if ($colA !== '') {
                $breakdown[] = [
                    'no'                 => $no++,
                    'wbs_element'        => $colA,
                    'sisa_anggaran_sap'  => $this->cleanNumber($row[1] ?? 0),
                    'wbs_belum_input'    => $this->cleanNumber($row[2] ?? 0),
                    'total'              => $this->cleanNumber($row[3] ?? 0),
                    'estimasi_kebutuhan' => $this->cleanNumber($row[4] ?? 0),
                    'selisih_variance'   => $this->cleanNumber($row[5] ?? 0),
                ];
            }
        }

        return [
            'metadata'         => $metadata,
            'budget_summary'   => $summary,
            'budget_breakdown' => $breakdown,
        ];
    }

    private function cleanNumber($value): float
    {
        if (is_numeric($value)) return (float)$value;
        $cleaned = preg_replace('/[^0-9\.\-]/', '', str_replace(',', '', (string)$value));
        return (float)($cleaned ?: 0);
    }
}