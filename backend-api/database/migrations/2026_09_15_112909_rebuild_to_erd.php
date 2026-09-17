<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rombak total ikut ERD:
     * MASTER_PROJECT(id PK, project_code UK, project_name, description, created_at)
     * PROJECT_LINKS(id PK, master_project_id FK, sheet_name, sheet_url, created_at)
     * PROCUREMENTS(id PK, master_project_id FK, no, name, vendor, nilai_rab, realisasi, mata_uang, status, progress, priority)
     * GANTT_ACTIVITIES(id PK, master_project_id FK, activity_name, start_date, end_date, progress_percentage)
     * + updated_at (wajib Laravel, deviasi minimal dari ERD).
     */
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        // 2. project_links baru DULU (butuh spreadsheet_id lama untuk sheet_url)
        DB::statement('CREATE TABLE project_links_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            master_project_id INTEGER NOT NULL REFERENCES master_projects(id) ON DELETE CASCADE,
            sheet_name VARCHAR NOT NULL,
            sheet_url VARCHAR,
            created_at DATETIME,
            updated_at DATETIME
        )');
        DB::statement("INSERT INTO project_links_new (id, master_project_id, sheet_name, sheet_url, created_at, updated_at)
            SELECT pl.id, pl.master_project_id, pl.sheet_name,
                CASE WHEN m.spreadsheet_id IS NOT NULL AND m.spreadsheet_id != ''
                    THEN 'https://docs.google.com/spreadsheets/d/' || m.spreadsheet_id ELSE NULL END,
                pl.created_at, pl.updated_at
            FROM project_links pl LEFT JOIN master_projects m ON m.id = pl.master_project_id");
        DB::statement('DROP TABLE project_links');
        DB::statement('ALTER TABLE project_links_new RENAME TO project_links');

        // 1. master_projects baru
        DB::statement('CREATE TABLE master_projects_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            project_code VARCHAR NOT NULL,
            project_name VARCHAR NOT NULL,
            description TEXT,
            created_at DATETIME,
            updated_at DATETIME,
            UNIQUE (project_code)
        )');
        DB::statement("INSERT INTO master_projects_new (id, project_code, project_name, description, created_at, updated_at)
            SELECT id, project_code, project_name, NULL, created_at, updated_at FROM master_projects");
        DB::statement('DROP TABLE master_projects');
        DB::statement('ALTER TABLE master_projects_new RENAME TO master_projects');

        // 3. procurements baru (hanya kolom ERD)
        DB::statement('CREATE TABLE procurements_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            master_project_id INTEGER NOT NULL REFERENCES master_projects(id) ON DELETE CASCADE,
            no VARCHAR NOT NULL,
            name VARCHAR NOT NULL,
            vendor VARCHAR,
            nilai_rab NUMERIC NOT NULL DEFAULT 0,
            realisasi NUMERIC NOT NULL DEFAULT 0,
            mata_uang VARCHAR NOT NULL DEFAULT \'IDR\',
            status VARCHAR,
            progress VARCHAR,
            priority VARCHAR,
            created_at DATETIME,
            updated_at DATETIME
        )');
        DB::statement("INSERT INTO procurements_new (id, master_project_id, no, name, vendor, nilai_rab, realisasi, mata_uang, status, progress, priority, created_at, updated_at)
            SELECT id, master_project_id, no, name, vendor, nilai_rab, realisasi, mata_uang, status, progress, priority, created_at, updated_at FROM procurements");
        DB::statement('DROP TABLE procurements');
        DB::statement('ALTER TABLE procurements_new RENAME TO procurements');

        // 4. gantt_activities baru (start/end date NULL dulu, progress 0 — konversi minggu->tanggal butuh mapping manual)
        DB::statement('CREATE TABLE gantt_activities_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            master_project_id INTEGER NOT NULL REFERENCES master_projects(id) ON DELETE CASCADE,
            activity_name VARCHAR NOT NULL,
            start_date DATE,
            end_date DATE,
            progress_percentage INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME,
            updated_at DATETIME
        )');
        DB::statement("INSERT INTO gantt_activities_new (id, master_project_id, activity_name, start_date, end_date, progress_percentage, created_at, updated_at)
            SELECT id, master_project_id, activity_name, NULL, NULL, 0, created_at, updated_at FROM gantt_activities");
        DB::statement('DROP TABLE gantt_activities');
        DB::statement('ALTER TABLE gantt_activities_new RENAME TO gantt_activities');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
    }
};
