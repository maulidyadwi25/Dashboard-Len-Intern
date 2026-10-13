# Procurement

## Tujuan

Halaman ini digunakan untuk memantau status pengadaan proyek, termasuk list item pengadaan, prioritas, timeline, dan visualisasi WBS/Gantt terkait procurement.

## File Implementasi

- Frontend: [frontend/src/pages/Procurement.vue](../../frontend/src/pages/Procurement.vue)
- Service: [frontend/src/services/budgetServices.js](../../frontend/src/services/budgetServices.js)
- Backend controller: [backend/app/Http/Controllers/ProcurementController.php](../../backend/app/Http/Controllers/ProcurementController.php)

## Data Source

Halaman ini mengambil data berikut:

- GET /api/procurement
- PATCH /api/procurement/{id}/start-date
- POST /api/procurement/sync

## Flow Data

1. `onMounted()` memanggil `budgetService.getProcurementData()`
2. Data dari API dibaca dari `res.data?.data ?? res.data`
3. `procurementItems` diisi dari `payload.procurement_items` atau `payload.items`
4. `ProjectProfileCard` menampilkan project metadata
5. Data difilter berdasarkan `activeFilter` (misalnya "All Packages")
6. WBS/Gantt panel menampilkan item yang sedang difilter

## Field Utama

Beberapa field yang dipakai di UI:

- `id`
- `no`
- `item_name` / `nama_pengadaan`
- `category` / `jenis`
- `vendor` / `nama_vendor`
- `status`
- `priority`
- `start_date`
- `target_date`

## Fitur Utama

- project selector bar
- project profile card
- status monitoring procurement
- metric cards dengan filter package
- export PDF
- update tanggal mulai item tertentu

## Update Start Date

`updateStartDate` mengubah date dari format browser `YYYY-MM-DD` menjadi format DB `DD/MM/YYYY` sebelum dikirim ke backend. Proses update dilakukan via `PATCH /api/procurement/{id}/start-date`.

## Catatan Penting

- Data procurement berasal dari `MasterProject::procurementItems()`
- `ProcurementController` akan melakukan backfill priority jika data belum terisi dengan memanggil `syncProject()`
- API response mengembalikan `project`, `items`, `procurement_items`, `status_counts`, dan `total`
