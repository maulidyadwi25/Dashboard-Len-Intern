# Progress & Revenue

## Tujuan

Halaman ini menggabungkan monitoring progress & revenue berdasarkan indikator periodik dan baseline dengan struktur WBS serta progress mingguan proyek.

## File Implementasi

- Frontend: [frontend/src/pages/ProgressRevenue.vue](../../frontend/src/pages/ProgressRevenue.vue)
- Service: [frontend/src/services/budgetServices.js](../../frontend/src/services/budgetServices.js)
- WBS composable: [frontend/src/composables/useWbs.ts](../../frontend/src/composables/useWbs.ts)
- WBS service: [frontend/src/services/wbsService.js](../../frontend/src/services/wbsService.js)
- Backend controller: [backend/app/Http/Controllers/ProgressRevenueController.php](../../backend/app/Http/Controllers/ProgressRevenueController.php)
- WBS controller: [backend/app/Http/Controllers/WbsController.php](../../backend/app/Http/Controllers/WbsController.php)

## Data Source

Halaman ini memanggil endpoint:

- GET /api/projects/{id}/progress-revenue
- GET /api/projects/{id}/wbs
- GET /api/projects/{id}/wbs/weekly
- POST /api/projects/{id}/wbs/sync

Di frontend, service call adalah:

- `budgetService.getProgressRevenueData("all")`

## Flow Data

1. `fetchProgressRevenue()` dipanggil pada saat komponen siap
2. Response dibaca dari `res.data?.data ?? res.data`
3. Payload di-normalize dengan:
   - `project`
   - `baseline`
   - `periods`
   - `indicators`
4. `ProgressRevenueTable` menerima data tersebut untuk ditampilkan
5. `useWbs()` memuat struktur WBS dan data mingguan untuk tabel Gantt
6. Sync dan update progress WBS tetap menggunakan endpoint WBS masing-masing

## Field Utama

Payload utama biasanya berisi:

- `project.project_name`
- `project.project_code`
- `project.contract_value`
- `project.project_lead_name`
- `project.report_owner_name`
- `periods[]`
- `indicators[]`
- `baseline`

## Kondisi UI

Halaman memiliki:

- state loading
- state error
- state data berhasil dimuat

## Fitur Penting

- project profile card
- progress revenue table
- WBS summary cards dan tabel WBS/Gantt
- sinkronisasi WBS dan input progress untuk item leaf
- filter bulan dengan `selectedMonth`
- menampilkan metadata projek dan nilai kontrak

## Catatan Penting

- `ProgressRevenueController::show()` menerima `id` sebagai parameter path dan mengembalikan `ProgressRevenueResource`
- `month` filter dapat dikirim via query param seperti `?month=all` atau `?month=1`
- API masih menggunakan project default/aktif sesuai kebutuhan frontend; di layanan saat ini dipanggil dengan ID 1
- route lama `/wbs` diarahkan ke `/progress-revenue`
