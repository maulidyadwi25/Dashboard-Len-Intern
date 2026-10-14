# Progress & Revenue

## Tujuan

Halaman ini menyajikan indikator progress dan revenue per periode, baseline, struktur WBS, Gantt mingguan, input progress, serta ringkasan pekan ini dan ringkasan AI.

## Progress & Revenue

### Lokasi dan alur

- Halaman: [ProgressRevenue.vue](../../frontend/src/pages/ProgressRevenue.vue), pada route `/progress-revenue`.
- Komponen utama: `ProgressRevenueTable.vue`, `ProjectProfileCard.vue`, dan selector project.
- Service: [budgetServices.js](../../frontend/src/services/budgetServices.js), memanggil `GET /api/projects/{id}/progress-revenue` dengan query `month=all`.
- Backend: `ProgressRevenueController@show` mengembalikan resource berisi `project`, `baseline`, `periods`, dan `indicators`.
- Data project dipilih melalui `useProject()`; halaman mengirim `activeProjectId` (tidak mengunci ID 1).

## WBS & Gantt

### Lokasi UI dan source

- WBS/Gantt ditampilkan di halaman `/progress-revenue`; router `frontend/src/routes/index.ts` tidak mendaftarkan `/wbs` ataupun redirect `/wbs`.
- Komponen: `WbsSummaryCards.vue`, `WbsTreeTable.vue`, `WbsGanttChart.vue`, `WbsToolbar.vue`, `WbsWeekSummary.vue`, `WbsDeviationPanel.vue`, dan `WbsAiSummary.vue` di `frontend/src/components/progressrevenue/`.
- Data UI dikelola oleh `frontend/src/composables/useWbs.ts`, `frontend/src/services/wbsService.js`, dan `frontend/src/utils/wbs.ts`.
- Spreadsheet berasal dari project (`spreadsheet_id` / ID yang diambil dari URL, dengan konfigurasi Google Sheets sebagai fallback). Tab yang dicari adalah `2. Progress & Revenue`.
- Implementasi saat ini meminta range `A19:BD47` dengan `UNFORMATTED_VALUE`. Kolom A-H berisi metadata dan tipe P/A; I-BD dipetakan menjadi 48 pekan (Jan W1 sampai Dec W4). **Range tidak dinamis sampai baris terakhir.**
- Baris `P`/`PLAN` dan `A`/`ACTUAL` dengan WBS number sama digabung menjadi satu item. Baris tanpa nomor dapat memakai nomor WBS terakhir jika kolom A/P mengenali tipe P/A. Nomor/header invalid lainnya dilewati.

### Hierarki, bobot, progres, dan pekan

- Level dihitung dari jumlah segmen nomor: `1` = level 1, `1.1` = level 2, `1.1.1` = level 3. Parent dibentuk dengan membuang segmen terakhir. Parser menerima jumlah segmen lebih dari tiga juga.
- Untuk leaf, `bobot_plan` adalah jumlah nilai mingguan PLAN leaf yang dinormalisasi terhadap total seluruh leaf dalam project lalu dikali 100. Kolom F (`NILAI KEGIATAN`) dan G (`% BOBOT KEGIATAN`) tidak dipakai parser; `nilai_kegiatan` tetap nilai default 0 pada item hasil parser.
- Nilai mingguan PLAN dan ACTUAL disimpan terpisah. `bobot_real` yang disajikan API dihitung dari `bobot_plan × latest_progress_percent / 100`; nilai ACTUAL dari spreadsheet tidak dipakai sebagai persentase bobot real. Parent mendapat bobot dengan penjumlahan child.
- Parent menerima agregasi nilai mingguan dari child per pekan, lalu dapat digulung lagi ke ancestor. Schedule parent memakai earliest start dan latest end dari descendant.
- Input web hanya tersedia untuk leaf yang belum terkunci. Nilai harus numerik 0-100 dan dicatat untuk pekan berjalan di zona `Asia/Jakarta`; entry pada pekan yang sama diperbarui, bukan ditambahkan. Setelah ada entry 100%, input WBS tersebut ditolak. Leaf level 1 atau 2 juga dapat menerima input API bila tidak mempunyai child.
- Progress parent (`progress_sum`) adalah penjumlahan progress terbaru child, bukan rata-rata berbobot.
- Pembagian tanggal: W1 tanggal 1-10, W2 11-17, W3 18-24, W4 25-31, dengan zona `Asia/Jakarta` pada helper tanggal.
- Ordinal di sync sheet memakai `year * 48 + (month - 1) * 4 + (week - 1)`; `WbsDateHelper` memakai `year * 48 + (month - 1) * 4 + week`, sedangkan frontend memakai ordinal relatif 1-48. Tidak ada basis `year * 100`, tetapi rumus backend berbeda satu angka antara sync sheet dan helper tanggal. **Konsistensi lintas jalur BELUM TERVERIFIKASI sepenuhnya.**

### Actual dan status leaf

- Actual awal dapat datang dari jadwal ACTUAL spreadsheet. Entry progres web melanjutkan sumber actual; `actual_source` dihitung sebagai `sheet`, `web`, atau `mixed`.
- Bila belum ada entry web, start/end actual menggunakan jadwal sheet. Bila entry web ada, start memakai periode paling awal antara sheet dan web; finish actual ditentukan entry progres web yang mencapai 100%.
- Status yang dihitung untuk response WBS: `on_track` jika belum lewat jadwal atau selesai sesuai/mendahului plan; `overdue` jika terlambat satu pekan; `critical` jika terlambat lebih dari satu pekan. `not_started` dipakai jika belum ada actual start dan pekerjaan belum melewati akhir plan (atau tidak ada akhir plan). Jika belum mulai dan sudah lewat akhir plan, status menjadi overdue/critical sesuai jaraknya.
- Status ringkasan AI memiliki fungsi status tersendiri; pada item lewat jadwal, service AI memakai ambang progress 90% untuk membedakan `critical` dan `overdue`.

### Sinkronisasi dan skema

- `GoogleSheetsService::syncWbsData(MasterProject $project)` menghapus item WBS project lalu memasukkan hasil parse di dalam transaction. Weekly values terhapus melalui cascade ketika item dihapus. Jika terjadi exception saat penyimpanan, transaction dibatalkan; kegagalan ditangkap dan dikembalikan sebagai `status: failed`.
- Entry progress web berada di tabel terpisah dan tidak dihapus oleh sync WBS. WBS yang dihapus dari spreadsheet dapat meninggalkan entry progress tanpa item pasangannya.
- `syncProject()` menjalankan sync WBS ketika `type` project lowercase adalah `progress_revenue`, `all`, atau kosong. Endpoint sync WBS individual tidak memeriksa type.
- Siklus terjadwal di `backend/routes/console.php`: `app:sync-google-sheet` setiap lima menit. Endpoint `GET /api/cron/sync-sheets` di `backend/routes/api.php` juga menjalankan sync untuk project aktif; jika `CRON_SECRET` terisi, query `secret` harus cocok.
- Tabel terkait: `progress_wbs_items` (item, hierarki, bobot dan schedule), `progress_wbs_weekly_values` (type, tahun/bulan/pekan, label, nilai; unique per item/type/periode), `progress_wbs_progress_entries` (progress web per project/WBS/periode), dan `wbs_ai_summaries` (JSON ringkasan per project/tanggal, source `ai`/`manual`).

### API WBS

Semua route berikut berada di `backend/routes/api.php` dan memakai `{id}` sebagai ID MasterProject.

| Method dan URI | Parameter | Response utama |
| --- | --- | --- |
| `GET /api/projects/{id}/wbs` | Path `id` | `data.project`, `year`, `summary`, `items` (WbsItemResource) |
| `GET /api/projects/{id}/wbs/weekly` | Query `month=all` atau `1..12` | `data.year`, `periods`, `values` berisi WBS/type/periode/value; progres web digabung sebagai type `progress` |
| `GET /api/projects/{id}/wbs/week-summary` | Path `id` | Pekan aktif, label pekan, dan daftar leaf dengan realisasi, plan, deviasi, status |
| `GET /api/projects/{id}/wbs/ai-summary` | Path `id` | `data` ringkasan terakhir atau `null` |
| `POST /api/projects/{id}/wbs/ai-summary` | Tanpa body khusus | Generate ulang; sync sheet dicoba dahulu, lalu hasil `data` |
| `PUT /api/projects/{id}/wbs/ai-summary` | JSON `content` | Simpan struktur ringkasan manual; response `data` |
| `PUT /api/projects/{id}/wbs/{wbs_no}/progress` | JSON `percent` 0-100 | Project/WBS/periode, `percent`, latest progress, `bobot_real`, status |
| `POST /api/projects/{id}/wbs/sync` | Path `id` | `data` hasil sync (`status`, judul sheet, jumlah WBS atau error) |

- Error yang ditangani: 404 project/WBS tidak ditemukan; 422 input percent invalid, item bukan leaf, progres sudah 100%, atau struktur AI tidak valid; 409 lock generate AI sedang dipakai; 429 batas request AI; 502 kegagalan generate AI. Route API yang dibaca tidak memasang middleware autentikasi; middleware global yang berlaku: BELUM TERVERIFIKASI.
- Mode edit UI diaktifkan melalui tombol WBS. Draft dapat dibatalkan; perubahan disimpan per leaf melalui PUT progress dan progres 100% terkunci. UI menahan navigasi saat ada perubahan draft belum tersimpan.
- `wbsService.js` membungkus request dan menormalisasi error 404/409/422/429/502; `useWbs.ts` memuat tree/weekly values, mengelola filter bulan, sync, dan menyimpan perubahan progress.

### Test terkait

- Parser: `cd backend && php artisan test --compact tests/Unit/WbsParserTest.php`.
- Endpoint/progress/AI: `php artisan test --compact tests/Feature/WbsApiTest.php tests/Feature/WbsProgressApiTest.php tests/Feature/WbsAiSummaryApiTest.php`.
- Sync persistensi/idempotensi: `php artisan test --compact tests/Feature/WbsSyncTest.php`.
- File sync test saat ini mencakup hierarchy/rollup, kosong versus nol, dan sync berulang. Test rollback untuk `syncWbsData`: **BELUM TERVERIFIKASI (tidak ditemukan pada test yang dibaca)**. Status test lama yang pernah gagal: **BELUM TERVERIFIKASI**; tes tidak dijalankan dalam pembaruan dokumentasi ini.

## Ringkasan WBS Pekan Ini & AI

- `GET /api/projects/{id}/wbs/week-summary` menghitung pekan berjalan dari waktu `Asia/Jakarta`; daftar berisi leaf aktif dan diurutkan menurut deviasi absolut.
- Provider AI menggunakan API kompatibel OpenAI: request POST ke `{AI_BASE_URL}/chat/completions`, model dari `AI_MODEL`, dan Bearer key opsional dari `AI_API_KEY`. Nama konfigurasi lain: `AI_TIMEOUT`. Nilai rahasia tidak dicantumkan di dokumentasi.
- Response model harus JSON berisi `executive_summary` (string), `recommendations` (array object `priority`, `title`, `detail` string), dan `risks` (array string). Struktur divalidasi sebelum disimpan.
- Job `wbs:generate-ai-summary` dijadwalkan setiap hari pukul 06:00 dengan timezone `Asia/Jakarta` dan `withoutOverlapping()`. Scheduler perlu dijalankan oleh `schedule:run`/scheduler host agar job berlangsung. Route bertoken `/api/cron/sync-sheets` hanya menjalankan sync Sheets, bukan job ringkasan AI.
- Generate ulang manual lewat POST AI summary memanggil sync WBS dahulu. Controller memakai WBS tersimpan dan menyertakan `sync_warning` bila sync melempar exception; `syncWbsData()` sendiri umumnya menangkap exception dan mengembalikan `status: failed`, sehingga warning pada keadaan itu tidak dijamin. Ada lock per project (409) dan RateLimiter berkonfigurasi lima percobaan per 60 detik (429); controller juga membersihkan limiter setelah generate selesai, sehingga throttle persisten setelah request sukses: **BELUM TERVERIFIKASI**. Endpoint manual memakai force dan dapat mengganti ringkasan hari itu.
- Job harian melewati ringkasan `source=manual` untuk tanggal hari ini kecuali opsi `--force`; ringkasan manual tidak ditimpa oleh job harian normal.

## Batasan dan utang teknis

- Project ID 1 hardcode di `budgetServices.js`: tidak ditemukan; ID aktif diteruskan dari selector project.
- Proteksi autentikasi route API: route WBS tidak mendeklarasikan middleware auth dan `bootstrap/app.php` tidak menambah middleware API khusus. Perlindungan eksternal di luar repository: **BELUM TERVERIFIKASI**.
- Jadwal ringkasan AI hanya berjalan jika Laravel scheduler berjalan. Route cron bertoken yang tersedia menjalankan sync Sheets saja.
- Sync WBS menulis ulang semua item setiap sync; progress web berada di tabel terpisah sehingga entry dapat terlepas dari item jika WBS hilang dari sheet.
- Pembagian empat pekan per bulan tidak merata secara kalender (W1 10 hari, W2/W3 7 hari, W4 sisa bulan).
- Test lama yang gagal: **BELUM TERVERIFIKASI** karena tidak dijalankan dan tidak ada hasil kegagalan yang dapat diperiksa.
- Test rollback syncWbsData belum ada pada file test yang diperiksa.
- Pelajaran operasional: jangan menjalankan script penghapus data pada DB lokal; buat backup DB sebelum sesi otomatis.
