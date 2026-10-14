# Documentation Overview

Dokumentasi proyek ini dibagi menjadi 3 level agar lebih jelas dan konsisten dengan implementasi yang benar-benar ada di repo.

## 1. Project Overview

- File utama: [AI_CONTEXT.md](../AI_CONTEXT.md)
- Fokus: tujuan proyek, teknologi, arsitektur umum, dan fitur utama

## 2. Page Documentation

- Folder: [docs/pages](pages)
- Fokus: halaman frontend, data source, komponen utama, dan fungsi bisnis per layar

Dokumen halaman yang tersedia:

- [docs/pages/budget-monitoring.md](pages/budget-monitoring.md)
- [docs/pages/procurement.md](pages/procurement.md)
- [docs/pages/progress-revenue.md](pages/progress-revenue.md) — Progress & Revenue, WBS/Gantt, dan ringkasan AI

## 3. API Documentation

- Folder: [docs/api](api)
- Fokus: endpoint Laravel, method, request, response, dan penggunaan pada frontend

Dokumen API yang tersedia:

- [docs/api/README.md](api/README.md)

## 4. Why This Structure

- AI_CONTEXT.md berfungsi sebagai ringkasan proyek untuk onboarding dan konteks umum
- Page docs membantu frontend developer memahami apa yang ditampilkan di layar dan data apa yang dipakai
- API docs membantu backend developer dan integrator memahami contract endpoint dan response shape

## 5. Source of Truth

Semua dokumentasi ini dibuat berdasarkan kode riil di repo, terutama:

- [backend/routes/api.php](../backend/routes/api.php)
- [frontend/src/services/budgetServices.js](../frontend/src/services/budgetServices.js)
- [frontend/src/pages](../frontend/src/pages)
- [backend/app/Http/Controllers](../backend/app/Http/Controllers)
