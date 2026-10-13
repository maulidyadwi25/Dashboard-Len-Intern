# Budget Monitoring

## Tujuan

Halaman ini menampilkan ringkasan anggaran proyek, detail breakdown per WBS, dan status sisa anggaran. Halaman ini merupakan dashboard utama untuk melihat apakah anggaran masih sehat atau ada varians yang perlu diperhatikan.

## File Implementasi

- Frontend: [frontend/src/pages/BudgetMonitoring.vue](../../frontend/src/pages/BudgetMonitoring.vue)
- Service: [frontend/src/services/budgetServices.js](../../frontend/src/services/budgetServices.js)
- Backend controller: [backend/app/Http/Controllers/InternSheetController.php](../../backend/app/Http/Controllers/InternSheetController.php)

## Data Source

Halaman ini mengambil data dari endpoint backend berikut:

- GET /api/projects/1/dashboard
- POST /api/budget-dashboard

Di sisi frontend, data diakses melalui `budgetService.getDashboardData()`.

## Flow Data

1. Vue page dipanggil pada mount
2. `fetchDashboardData()` memanggil service
3. Response di-normalize ke struktur:
   - `metadata`
   - `budget_summary`
   - `budget_breakdown`
4. Data ditampilkan ke komponen:
   - `ProjectHeader`
   - `BudgetSummaryCards`
   - `BudgetBreakdownTable`

## Respons Payload Utama

Payload yang dipakai di frontend mengandung:

- `sheetData.metadata`
- `sheetData.budget_summary`
- `sheetData.budget_breakdown`
- `exchangeRates`

Contoh field yang dipakai:

- `metadata.nama_proyek`
- `metadata.kode_proyek`
- `metadata.tanggal_update`
- `budget_summary.sisa_anggaran_sap`
- `budget_summary.wbs_belum_input`
- `budget_summary.total`
- `budget_summary.estimasi_kebutuhan`
- `budget_summary.selisih_variance`

## Kondisi UI

Halaman memiliki 3 state utama:

- loading: spinner saat fetch data
- error: tampilan error dengan tombol retry
- success: menampilkan header proyek, summary cards, serta tabel breakdown

## Fitur Penting

- menampilkan metadata proyek
- menampilkan summary anggaran
- menampilkan breakdown per cost center/WBS
- export PDF via `generateBudgetPdf()`
- support currency formatting via composable `useCurrency()`

## Catatan Penting

- Halaman saat ini menggunakan `project ID 1` sebagai default di service frontend.
- Data source sebenarnya merupakan project aktif yang dipilih di backend, bukan sekadar data hardcoded.
- API response untuk page ini berbentuk `success + data + sheetData` sesuai controller.
