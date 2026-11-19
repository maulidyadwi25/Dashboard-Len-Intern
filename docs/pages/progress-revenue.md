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
- Tombol **Antrean Persetujuan Progres** (dengan jumlah pengajuan pending) berada di samping Download Gantt dan hanya tampil untuk VP. Tombol membuka route `/progress-approval` pada tab **Menunggu Persetujuan**. VP juga dapat membuka halaman dari navigasi utama.
- Tombol notifikasi pada header menyesuaikan role. VP mendapat daftar pengajuan pending untuk ditindaklanjuti dan tautan ke antrean persetujuan. PA mendapat status pengajuan miliknya (menunggu, disetujui, atau ditolak); daftar notifikasi diperbarui otomatis berkala dan saat proyek aktif berubah.
- Halaman [ProgressApproval.vue](../../frontend/src/pages/ProgressApproval.vue) dibatasi untuk VP. Halaman menggunakan proyek aktif dan menyediakan tab **Menunggu Persetujuan**, **Riwayat Keputusan**, dan **Perubahan Langsung VP**.
- Tab **Menunggu Persetujuan** menampilkan tabel kode/status WBS dan aktivitas, pengaju, perbandingan progres (termasuk delta dan baseline), justifikasi, serta tindakan Setujui/Tolak. Tindakan meminta konfirmasi; form penolakan meminta alasan. Setelah keputusan, antrean dan jumlah status diperbarui.
- Tab **Riwayat Keputusan** menampilkan pengajuan yang telah disetujui atau ditolak, termasuk perubahan nilai, status keputusan, pengaju, pemutus, dan waktu putusan, dengan filter Semua/Disetujui/Ditolak. Tab **Perubahan Langsung VP** menampilkan log audit perubahan progres yang dilakukan langsung VP.
- Data UI dikelola oleh `frontend/src/composables/useWbs.ts`, `frontend/src/services/wbsService.js`, dan `frontend/src/utils/wbs.ts`.
- Spreadsheet berasal dari project (`spreadsheet_id` / ID yang diambil dari URL, dengan konfigurasi Google Sheets sebagai fallback). Tab yang dicari adalah `2. Progress & Revenue`.
- Implementasi saat ini meminta range `A19:BD47` dengan `UNFORMATTED_VALUE`. Kolom A-H berisi metadata dan tipe P/A; I-BD dipetakan menjadi 48 pekan (Jan W1 sampai Dec W4). **Range tidak dinamis sampai baris terakhir.**
- Baris `P`/`PLAN` dan `A`/`ACTUAL` dengan WBS number sama digabung menjadi satu item. Baris tanpa nomor dapat memakai nomor WBS terakhir jika kolom A/P mengenali tipe P/A. Nomor/header invalid lainnya dilewati.

### Hierarki, bobot, progres, dan pekan

- Level dihitung dari jumlah segmen nomor: `1` = level 1, `1.1` = level 2, `1.1.1` = level 3. Parent dibentuk dengan membuang segmen terakhir. Parser menerima jumlah segmen lebih dari tiga juga.
- Untuk leaf, `bobot_plan` adalah jumlah nilai mingguan PLAN leaf yang dinormalisasi terhadap total seluruh leaf dalam project lalu dikali 100. Kolom F (`NILAI KEGIATAN`) dan G (`% BOBOT KEGIATAN`) tidak dipakai parser; `nilai_kegiatan` tetap nilai default 0 pada item hasil parser.
- Nilai mingguan PLAN dan ACTUAL disimpan terpisah. `bobot_real` yang disajikan API dihitung dari `bobot_plan × latest_progress_percent / 100`; nilai ACTUAL dari spreadsheet tidak dipakai sebagai persentase bobot real. Parent mendapat bobot dengan penjumlahan child.
- Parent menerima agregasi nilai mingguan dari child per pekan, lalu dapat digulung lagi ke ancestor. Schedule parent memakai earliest start dan latest end dari descendant.
- Input web hanya tersedia untuk leaf dan role PA/VP. Nilai harus numerik 0-100 dan dicatat untuk pekan berjalan di zona `Asia/Jakarta`; entry pada pekan yang sama diperbarui, bukan ditambahkan. Kenaikan PA disimpan langsung tanpa komentar wajib. PA yang menurunkan progres harus mengisi alasan dan mengirim pengajuan untuk persetujuan VP; progres tidak berubah saat pengajuan dibuat. VP dapat menaikkan, menurunkan, atau menetapkan nilai 0 secara langsung. Leaf level 1 atau 2 juga dapat menerima input API bila tidak mempunyai child.
- Kenaikan PA dan perubahan langsung VP direkam di `wbs_progress_change_logs`, termasuk pelaku/role, WBS, periode, nilai sebelumnya dan baru, komentar bila ada, dan waktu. Pengajuan PA disimpan terpisah di `wbs_progress_change_requests`; perubahan baru dicatat ke log setelah pengajuan disetujui.
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
- Siklus terjadwal di `backend/routes/console.php`: `app:sync-google-sheet` setiap lima menit. Endpoint `GET /api/cron/sync-sheets` di `backend/routes/api.php` juga menjalankan sync untuk project aktif. Endpoint tersebut wajib memiliki `CRON_SECRET` di environment backend dan menerima query `secret` yang cocok; tanpa konfigurasi secret, endpoint mengembalikan `503`.
- Tabel terkait: `progress_wbs_items` (item, hierarki, bobot dan schedule), `progress_wbs_weekly_values` (type, tahun/bulan/pekan, label, nilai; unique per item/type/periode), `progress_wbs_progress_entries` (progress web per project/WBS/periode), `wbs_progress_change_requests` (pengajuan PA beserta status, pengaju, peninjau, dan waktu putusan), `wbs_progress_change_logs` (audit perubahan progres yang diterapkan), dan `wbs_ai_summaries` (JSON ringkasan per project/tanggal, source `ai`/`manual`).

### API WBS

Semua route berikut berada di `backend/routes/api.php` dan memakai `{id}` sebagai ID MasterProject.

| Method dan URI                                                      | Parameter                                     | Response utama                                                                                                    |
| ------------------------------------------------------------------- | --------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| `GET /api/projects/{id}/wbs`                                        | Path `id`                                     | `data.project`, `year`, `summary`, `items` (WbsItemResource)                                                      |
| `GET /api/projects/{id}/wbs/weekly`                                 | Query `month=all` atau `1..12`                | `data.year`, `periods`, `values` berisi WBS/type/periode/value; progres web digabung sebagai type `progress`      |
| `GET /api/projects/{id}/wbs/week-summary`                           | Path `id`                                     | Pekan aktif, label pekan, dan daftar leaf dengan realisasi, plan, deviasi, status                                 |
| `GET /api/projects/{id}/wbs/ai-summary`                             | Path `id`                                     | `data` ringkasan terakhir atau `null`                                                                             |
| `POST /api/projects/{id}/wbs/ai-summary`                            | Tanpa body khusus                             | Generate ulang; sync sheet dicoba dahulu, lalu hasil `data`                                                       |
| `PUT /api/projects/{id}/wbs/ai-summary`                             | JSON `content`                                | Simpan struktur ringkasan manual; response `data`                                                                 |
| `PUT /api/projects/{id}/wbs/{wbs_no}/progress`                      | JSON `percent` 0-100; `comment` opsional      | Project/WBS/periode, `percent`, latest progress, `bobot_real`, status; setiap perubahan berhasil dicatat ke audit |
| `POST /api/projects/{id}/wbs/{wbs_no}/progress-change-requests`     | Role PA; JSON `percent` dan alasan `comment`  | Membuat pengajuan penurunan berstatus pending; progres tidak berubah sebelum disetujui VP                         |
| `GET /api/projects/{id}/wbs/progress-change-requests`               | Role VP; `page` dan `status` opsional         | Daftar pengajuan paginasi 20 entri; status `pending` (default), `reviewed`, `approved`, atau `rejected`; counts    |
| `GET /api/projects/{id}/wbs/my-progress-change-requests`             | Role PA; `page` dan `status` opsional         | Pengajuan milik PA yang login saja, beserta counts status (`pending`, `approved`, `rejected`)                      |
| `PATCH /api/projects/{id}/wbs/progress-change-requests/{requestId}` | Role VP; keputusan `approved` atau `rejected` | Meninjau pengajuan; approval menerapkan nilai dan mencatat audit, rejection tidak mengubah progres                |
| `GET /api/projects/{id}/wbs/progress-changes`                       | Role VP; query `page` opsional                | Riwayat perubahan paginasi 20 entri, termasuk task, pelaku, nilai lama/baru, alasan, dan waktu                    |
| `POST /api/projects/{id}/wbs/sync`                                  | Path `id`                                     | `data` hasil sync (`status`, judul sheet, jumlah WBS atau error)                                                  |

- Semua endpoint API pada route group dilindungi `auth:sanctum`; hanya endpoint cron sync yang berada di luar group. Login SPA menggunakan `GET /sanctum/csrf-cookie` dan `POST /login`; akun dibuat internal dengan `php artisan user:create`.
- Error terkait akses/perubahan progres: 401 belum login, 403 role tidak berhak, 404 project/WBS/pengajuan tidak ditemukan, 409 pengajuan duplikat, pengajuan sudah ditinjau, atau progres berubah sejak pengajuan, dan 422 input invalid atau item bukan leaf. AI juga dapat mengembalikan 409, 429, atau 502 sesuai kondisi.
- Mode edit UI diaktifkan melalui tombol WBS. Draft dapat dibatalkan; kenaikan PA langsung disimpan. Pengurangan PA meminta alasan dan tombol centang membuka konfirmasi pengajuan; setelah dikirim, nilai hanya berubah ketika VP menyetujui. VP dapat mengubah progres langsung; jika mengurangi progres, UI meminta konfirmasi dengan alasan opsional. UI menahan navigasi saat ada perubahan draft belum tersimpan.
- Halaman `/progress-approval` hanya untuk VP dan selalu terbuka pada tab **Menunggu Persetujuan**, termasuk saat dibuka dari tombol pada Progress & Revenue. Tab keputusan menyediakan filter Semua/Disetujui/Ditolak; tabel menampilkan status WBS, komparasi progres, delta, baseline, nilai sebelumnya, pengaju, pemutus, dan waktu putusan. UI meminta alasan saat menolak, tetapi API saat ini tidak menyimpan catatan penolakan.
- Tombol notifikasi di header memakai endpoint berdasarkan role dan proyek aktif: VP menerima antrean pending, sedangkan PA hanya menerima pengajuan miliknya. Endpoint PA tidak membuka pengajuan PA lain.
- Data halaman persetujuan dan riwayat mengikuti proyek aktif. Riwayat keputusan dan log perubahan langsung dipaginasi, terbaru dahulu, 20 entri per halaman; VP dapat melihat task/WBS, perubahan nilai, nama pelaku/pemutus, waktu, status, dan alasan.
- `wbsService.js` membungkus request WBS, perubahan progres, pengajuan, keputusan, dan riwayat; `useWbs.ts` memuat tree/weekly values, mengelola filter bulan, sync, dan penyimpanan progres.

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
- Akun PA dan VP diprovisikan secara internal; endpoint registrasi publik tidak diaktifkan.
- Jadwal ringkasan AI hanya berjalan jika Laravel scheduler berjalan. Route cron bertoken yang tersedia menjalankan sync Sheets saja.
- Sync WBS menulis ulang semua item setiap sync; progress web berada di tabel terpisah sehingga entry dapat terlepas dari item jika WBS hilang dari sheet.
- Pembagian empat pekan per bulan tidak merata secara kalender (W1 10 hari, W2/W3 7 hari, W4 sisa bulan).
- Test lama yang gagal: **BELUM TERVERIFIKASI** karena tidak dijalankan dan tidak ada hasil kegagalan yang dapat diperiksa.
- Test rollback syncWbsData belum ada pada file test yang diperiksa.
- Pelajaran operasional: jangan menjalankan script penghapus data pada DB lokal; buat backup DB sebelum sesi otomatis.
