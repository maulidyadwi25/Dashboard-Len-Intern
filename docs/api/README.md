# API Documentation

Dokumentasi ini mencatat endpoint aktif yang benar-benar ada di backend dan dipakai oleh frontend.

## 1. Budget Monitoring

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

## 2. Procurement

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

## 3. Progress & Revenue

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

## 4. WBS & Gantt

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

### POST /api/projects/{id}/wbs/sync

**Controller**: `WbsController@sync`

**Tujuan**: memicu sinkronisasi WBS dari spreadsheet ke DB.

---

## 5. Cron Sync

### GET /api/cron/sync-sheets

**Controller**: closure route in [backend/routes/api.php](../../backend/routes/api.php)

**Tujuan**: menjalankan sync semua project aktif dari Google Sheets.

**Query param**:

- `secret` jika `CRON_SECRET` diaktifkan

**Response**:

- `status`
- `message`
- `synced_projects`
- `timestamp`

---

## 6. Currency

### GET /api/currency/rates

**Controller**: `CurrencyController@getRates`

**Tujuan**: mengambil rate mata uang yang dipakai untuk format currency di frontend.

---

## 7. Notes

- Semua endpoint di file ini sesuai dengan implementasi yang saat ini aktif di repo.
- Fitur page-doc dan API-doc dipisahkan untuk memudahkan onboarding dan maintenance.
- Jika ada perubahan response atau route, dokumentasi ini harus diperbarui bersama dengan controller dan service terkait.
