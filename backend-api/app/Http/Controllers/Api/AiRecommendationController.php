<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiRecommendationController extends Controller
{
    /**
     * Generate summary & recommendation by AI (READ-ONLY).
     * Endpoint ini HANYA membaca snapshot procurement dari database.
     * Tidak ada operasi tulis ke database maupun ke Google Sheet.
     */
    public function recommendation(Request $request)
    {
        $request->validate([
            'no' => 'required|string|max:20',
        ]);

        $groqKey = config('services.groq.key');
        if (!$groqKey) {
            return response()->json([
                'status'  => 'error',
                'message' => 'GROQ_API_KEY belum diisi di file .env backend',
            ], 503);
        }

        // ===== READ-ONLY: hanya membaca snapshot project_links =====
        $projectId = $request->input('id');
        $query = ProjectLink::where('sheet_key', 'procurement');
        if ($projectId && $projectId !== 'all') {
            $query->where('master_project_id', is_numeric($projectId) ? (int) $projectId : $projectId);
        }
        $record = $query->first();

        if (!$record || empty($record->data)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data procurement tidak ditemukan. Jalankan sinkronisasi sheet terlebih dahulu.',
            ], 404);
        }

        $rows  = is_string($record->data) ? json_decode($record->data, true) : $record->data;
        $no    = trim($request->input('no'));
        $item  = $this->findItem($rows, $no);

        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => "Pengadaan nomor {$no} tidak ditemukan",
            ], 404);
        }

        $context = $this->buildContext($item, $this->collectSiblings($rows, $no));

        // ===== Panggil Groq (read-only analysis) =====
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $groqKey,
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post(config('services.groq.url'), [
            'model'           => config('services.groq.model'),
            'temperature'     => 0.4,
            'response_format' => ['type' => 'json_object'],
            'messages'        => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $context],
            ],
        ]);

        if ($response->failed()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Layanan AI tidak merespons: ' . substr($response->body(), 0, 300),
            ], 502);
        }

        $content = $response->json('choices.0.message.content') ?? '';
        $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $content));
        $parsed  = json_decode($content, true);

        if (!is_array($parsed)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Respons AI tidak dapat dibaca',
            ], 502);
        }

        return response()->json([
            'status'         => 'success',
            'source'         => 'groq',
            'generated_at'   => now()->toIso8601String(),
            'summary'        => (string) ($parsed['summary'] ?? ''),
            'recommendations'=> $this->normalizeRecommendations($parsed['recommendations'] ?? []),
        ]);
    }

    private function findItem(array $rows, string $no): ?array
    {
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            if (trim(strval($row[0] ?? '')) === $no && trim(strval($row[1] ?? '')) !== '') {
                return $row;
            }
        }
        return null;
    }

    private function collectSiblings(array $rows, string $no): array
    {
        $siblings = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $rNo   = trim(strval($row[0] ?? ''));
            $name  = trim(strval($row[1] ?? ''));
            if ($rNo === '' || $name === '' || $rNo === $no || strtoupper($rNo) === 'NO') continue;
            $siblings[] = [
                'no'       => $rNo,
                'name'     => $name,
                'status'   => trim(strval($row[4] ?? '')),
                'progress' => trim(strval($row[5] ?? '')),
                'nilai'    => trim(strval($row[19] ?? '')),
                'delivery' => trim(strval($row[55] ?? '')),
            ];
            if (count($siblings) >= 15) break;
        }
        return $siblings;
    }

    private function buildContext(array $item, array $siblings): string
    {
        $g = function ($idx) use ($item) {
            return trim(strval($item[$idx] ?? ''));
        };

        $termin = [];
        foreach (['T1' => 29, 'T2' => 33, 'T3' => 37, 'T4' => 41, 'T5' => 45, 'T6' => 49] as $label => $i) {
            $termin[] = [
                'termin'        => $label,
                'tanggal_submit'=> $g($i),
                'nilai'         => $g($i + 1),
                'gr_ses'        => $g($i + 2),
                'no_spp'        => $g($i + 3),
            ];
        }

        $data = [
            'pengadaan' => [
                'no'              => $g(0),
                'nama'            => $g(1),
                'vendor'          => $g(2),
                'target_toa'      => $g(3),
                'status'          => $g(4),
                'tahapan'         => $g(5),
                'priority'        => $g(6),
                'jenis'           => $g(7),
                'payment_type'    => $g(8),
                'nilai_rab_rap'   => $g(19),
                'realisasi'       => $g(20),
                'mata_uang'       => $g(21),
                'efisiensi'       => $g(22),
                'due_date'        => $g(23),
                'payment_priority'=> $g(53),
                'est_delivery'    => $g(54),
                'delivery_status' => $g(55),
            ],
            'dp' => [
                'tanggal_submit' => $g(25),
                'nilai'          => $g(26),
                'gr_ses'         => $g(27),
                'no_spp'         => $g(28),
            ],
            'termin' => $termin,
            'payment_status' => [
                'dp' => $g(56), 't1' => $g(57), 't2' => $g(58),
                't3' => $g(59), 't4' => $g(60), 't5' => $g(61), 't6' => $g(62),
            ],
            'status_termin' => [
                'dp' => $g(63), 't1' => $g(64), 't2' => $g(65),
                't3' => $g(66), 't4' => $g(67), 't5' => $g(68), 't6' => $g(69),
            ],
            'konteks_pengadaan_lain' => $siblings,
        ];

        return "Data pengadaan (read-only) dalam format JSON:\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function systemPrompt(): string
    {
        return 'Anda adalah analis procurement (pengadaan) untuk dashboard PMO kapal.
Anda HANYA menerima data read-only dari database; anda TIDAK boleh mengubah, menulis, atau meminta data lain di luar konteks yang diberikan.
Tugas: analisis karakteristik dan pola dari data pengadaan yang diberikan, lalu hasilkan summary dan recommendation yang paling relevan.
Pertimbangkan pola seperti: efisiensi anggaran, jadwal termin & GR/SES, payment status, delivery status, prioritas, keterlambatan, dan perbandingan dengan pengadaan lain pada konteks.
Balas HANYA dengan JSON valid tanpa teks lain, dengan struktur:
{
  "summary": "ringkasan analisis 2-4 kalimat dalam Bahasa Indonesia",
  "recommendations": [
    {
      "category": "LABEL KATEGORI HURUF KAPITAL",
      "tone": "error" atau "primary" atau "slate",
      "metricLabel": "nama metrik singkat",
      "metricValue": "nilai metrik",
      "title": "judul rekomendasi singkat",
      "body": "penjelasan rekomendasi 1-2 kalimat berbasis data"
    }
  ]
}
Hasilkan tepat 3 rekomendasi, semua dalam Bahasa Indonesia, spesifik dan berbasis angka dari data.';
    }

    private function normalizeRecommendations($recs): array
    {
        if (!is_array($recs)) return [];
        $allowedTones = ['error', 'primary', 'slate'];
        $out = [];
        foreach (array_slice(array_values($recs), 0, 4) as $r) {
            if (!is_array($r)) continue;
            $tone = strval($r['tone'] ?? 'primary');
            $out[] = [
                'category'     => strval($r['category'] ?? 'REKOMENDASI'),
                'tone'         => in_array($tone, $allowedTones, true) ? $tone : 'primary',
                'metricLabel'  => strval($r['metricLabel'] ?? ''),
                'metricValue'  => strval($r['metricValue'] ?? ''),
                'title'        => strval($r['title'] ?? ''),
                'body'         => strval($r['body'] ?? ''),
            ];
        }
        return $out;
    }
}
