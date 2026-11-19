# API Documentation

Dokumentasi ini mencatat endpoint aktif yang benar-benar ada di backend dan dipakai oleh frontend.

## 1. Autentikasi dan Akses

- Semua endpoint `/api/*` selain `GET /api/cron/sync-sheets` memerlukan sesi Sanctum (`auth:sanctum`). Request tanpa sesi mendapat `401`.
- SPA mengambil cookie CSRF dari `GET /sanctum/csrf-cookie`, lalu login melalui `POST /login` dengan body `email` dan `password`. Kedua endpoint auth ini berada di root, bukan di bawah `/api`.
- `POST /api/logout` mengakhiri sesi login. `GET /api/user` mengembalikan user aktif dalam `data`, termasuk `id`, `name`, `email`, dan `role`.
- Pendaftaran publik dinonaktifkan. Akun internal dibuat melalui `php artisan user:create "Nama" email@contoh.com PA` atau role `VP`; command meminta password minimal 12 karakter.
- Role yang digunakan: `PA` (Project Analyst) dan `VP` (Vice President). Endpoint riwayat perubahan hanya boleh diakses VP; selain VP mendapat `403`.

**Contoh response `GET /api/user`**:

```json
{
  "data": {
    "id": 1,
    "name": "Nama Pengguna",
    "email": "email@contoh.com",
    "role": "VP"
  }
}
```

---

## 2. Budget Monitoring

### GET /api/projects/1/dashboard

**Controller**: `ProjectDashboardController@show`

**Tujuan**: mengambil data agregat project untuk dashboard utama.

**Response utama**:

- `status`
- `data` (ProjectDashboardResource)

**Catatan**: frontend memanggil endpoint ini lewat `budgetService.getDashboardData()`.

### POST /api/budget-dashboard

**Controller**: `InternSheetController@update`

**Tujuan**: menerima payload update budget dari frontend dan menyimpan ke DB.

**Request umum**:

- `project_code`
- `budget_summary`
- `budget_breakdown`

**Response**:

- `status`
- `message`
- `data`

---

## 3. Procurement

### GET /api/procurement

**Controller**: `ProcurementController@index`

**Tujuan**: mendapatkan list procurement project aktif.

**Response utama**:

- `project`
- `items` / `procurement_items`
- `status_counts`
- `total`
- `main_items_total`

### POST /api/procurement/sync

**Controller**: `ProcurementController@sync`

**Tujuan**: memicu sinkronisasi data procurement dari Google Sheets ke DB.

### PATCH /api/procurement/{id}/start-date

**Controller**: `ProcurementController@updateStartDate`

**Tujuan**: update tanggal mulai item procurement.

**Request**:

- `start_date` (format `YYYY-MM-DD` di browser)

**Response**:

- `status`
- `message`
- `data.id`
- `data.start_date`

---

## 4. Progress & Revenue

### GET /api/projects/{id}/progress-revenue

**Controller**: `ProgressRevenueController@show`

**Tujuan**: mengambil indikator progress dan revenue untuk project tertentu.

**Query param**:

- `month=all|1..12`

**Response utama**:

- `status`
- `data.project`
- `data.baseline`
- `data.periods`
- `data.indicators`

---

## 5. WBS & Gantt

### GET /api/projects/{id}/wbs

**Controller**: `WbsController@show`

**Tujuan**: mengambil tree WBS project dengan summary.

**Response utama**:

- `project`
- `year`
- `summary`
- `items`

### GET /api/projects/{id}/wbs/weekly

**Controller**: `WbsController@weekly`

**Tujuan**: mengambil nilai WBS per minggu untuk grid gantt.

**Query param**:

- `month=all|1..12`

**Response utama**:

- `year`
- `periods`
- `values`

### PUT /api/projects/{id}/wbs/{wbs_no}/progress

**Controller**: `WbsController@updateProgress`

**Akses**: hanya role `PA` dan `VP`, serta item harus merupakan leaf WBS.

**Request JSON**:

```json
{
  "percent": 55,
  "comment": "Progress pekerjaan di lapangan bertambah."
}
```

- `percent`: angka 0–100.
- `comment`: opsional untuk PA dan VP.
- PA dapat menyimpan kenaikan secara langsung. Untuk pengurangan, PA mengirim pengajuan dengan alasan melalui `POST /api/projects/{id}/wbs/{wbs_no}/progress-change-requests`; progres tidak berubah sampai disetujui VP. VP dapat menaikkan, menurunkan, atau menetapkan progres menjadi `0`.
- Kenaikan PA dan perubahan langsung VP diterapkan pada entry pekan berjalan (zona `Asia/Jakarta`) dan dicatat ke audit trail. Pengurangan yang diajukan PA tetap pending sampai VP menyetujui; perubahan hasil persetujuan dicatat ke audit trail.
- Error umum: `401` belum login, `403` role tidak berhak, `404` project/WBS tidak ditemukan, `422` nilai tidak valid, item bukan leaf, atau progres PA tidak meningkat.

Pengajuan pengurangan PA disimpan sebagai pending di `wbs_progress_change_requests`. VP dapat melihat antrean melalui `GET /api/projects/{id}/wbs/progress-change-requests`, lalu meninjau dengan `PATCH /api/projects/{id}/wbs/progress-change-requests/{requestId}` dengan body `{"decision":"approved"}` atau `{"decision":"rejected"}`. Persetujuan menerapkan nilai dan mencatat perubahan pada audit; penolakan tidak mengubah progres. Pengajuan ditolak saat progres telah berubah sejak diajukan.

PA dapat melihat status pengajuannya sendiri melalui `GET /api/projects/{id}/wbs/my-progress-change-requests` (query `page` dan `status` opsional; status default `all`). Endpoint hanya mengembalikan pengajuan user PA yang sedang login dan menyertakan jumlah `pending`, `approved`, serta `rejected`. VP menggunakan endpoint antrean untuk melihat pengajuan pending yang perlu ditindaklanjuti.

### GET /api/projects/{id}/wbs/progress-changes

**Controller**: `WbsProgressChangeLogController@index`

**Akses**: role `VP` saja.

**Tujuan**: menampilkan riwayat perubahan progres proyek, yang juga ditampilkan di panel **Riwayat perubahan progres** pada halaman Progress & Revenue untuk VP.

**Query param**:

- `page` (opsional, default `1`); hasil dipaginasi 20 entri per halaman, urutan terbaru dahulu.

**Response utama**:

- `data[]`: `id`, `actor_name`, `actor_role`, `wbs_no`, `activity_name`, `year`, `month`, `week`, `previous_percent`, `new_percent`, `comment`, `created_at`.
- `meta`: `current_page`, `last_page`, `per_page`, `total`.
- `previous_percent` dapat berupa `null` bila belum ada progres sebelumnya.

### POST /api/projects/{id}/wbs/sync

**Controller**: `WbsController@sync`

**Tujuan**: memicu sinkronisasi WBS dari spreadsheet ke DB.

---

## 6. Cron Sync

### GET /api/cron/sync-sheets

**Controller**: closure route in [backend/routes/api.php](../../backend/routes/api.php)

**Tujuan**: menjalankan sync semua project aktif dari Google Sheets.

**Autentikasi**:

- Wajib mengatur `CRON_SECRET` di environment backend dan mengirim nilainya sebagai query `secret`.
- Jika secret tidak dikonfigurasi, endpoint mengembalikan `503`. Secret yang tidak cocok mengembalikan `401`.

**Response**:

- `status`
- `message`
- `synced_projects`
- `timestamp`

---

## 7. Currency

### GET /api/currency/rates

**Controller**: `CurrencyController@getRates`

**Tujuan**: mengambil rate mata uang yang dipakai untuk format currency di frontend.

---

## 8. Notes

- Semua endpoint di file ini sesuai dengan implementasi yang saat ini aktif di repo.
- Fitur page-doc dan API-doc dipisahkan untuk memudahkan onboarding dan maintenance.
- Jika ada perubahan response atau route, dokumentasi ini harus diperbarui bersama dengan controller dan service terkait.
