<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disable foreign key checks for rebuild
        DB::statement('PRAGMA foreign_keys = OFF');

        // ============================================================
        // 1. MASTER_PROJECTS — sesuai ERD + spreadsheet_id (wajib FE)
        // ============================================================
        DB::statement('ALTER TABLE master_projects RENAME TO master_projects_old');
        DB::statement('
            CREATE TABLE master_projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_code VARCHAR NOT NULL,
                project_name VARCHAR NOT NULL,
                spreadsheet_id VARCHAR NULL,
                description TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            )
        ');
        DB::statement('
            INSERT INTO master_projects (id, project_code, project_name, spreadsheet_id, description, created_at, updated_at)
            SELECT id, project_code, project_name, spreadsheet_id, description, created_at, updated_at
            FROM master_projects_old
        ');
        DB::statement('DROP TABLE master_projects_old');

        // ============================================================
        // 2. PROJECT_LINKS — sesuai ERD + sheet_key/data/row_count (wajib sync & FE)
        // ============================================================
        DB::statement('ALTER TABLE project_links RENAME TO project_links_old');
        DB::statement('
            CREATE TABLE project_links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                master_project_id INTEGER NOT NULL,
                sheet_name VARCHAR NOT NULL,
                sheet_url VARCHAR NULL,
                sheet_key VARCHAR NULL,
                data TEXT NULL,
                row_count INTEGER NULL,
                last_synced_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY (master_project_id) REFERENCES master_projects(id)
            )
        ');
        DB::statement('
            INSERT INTO project_links (id, master_project_id, sheet_name, sheet_url, sheet_key, data, row_count, last_synced_at, created_at, updated_at)
            SELECT id, master_project_id, sheet_name, sheet_url, sheet_key, data, row_count, last_synced_at, created_at, updated_at
            FROM project_links_old
        ');
        DB::statement('DROP TABLE project_links_old');

        // ============================================================
        // 3. PROCUREMENTS — sesuai ERD (tabel mati, FE gak baca dari sini)
        // ============================================================
        DB::statement('DROP TABLE IF EXISTS procurements_old');
        DB::statement('ALTER TABLE procurements RENAME TO procurements_old');
        DB::statement('
            CREATE TABLE procurements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                master_project_id INTEGER NOT NULL,
                no VARCHAR NOT NULL,
                name VARCHAR NOT NULL,
                vendor VARCHAR NULL,
                nilai_rab DECIMAL(15,2) NULL,
                realisasi DECIMAL(15,2) NULL,
                mata_uang VARCHAR NULL DEFAULT \'IDR\',
                status VARCHAR NULL DEFAULT \'In Progress\',
                progress VARCHAR NULL,
                priority VARCHAR NULL DEFAULT \'Medium\',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY (master_project_id) REFERENCES master_projects(id)
            )
        ');
        DB::statement('DROP TABLE procurements_old');

        // ============================================================
        // 4. GANTT_ACTIVITIES — sesuai ERD (tabel mati, FE gak baca dari sini)
        // ============================================================
        DB::statement('DROP TABLE IF EXISTS gantt_activities_old');
        DB::statement('ALTER TABLE gantt_activities RENAME TO gantt_activities_old');
        DB::statement('
            CREATE TABLE gantt_activities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                master_project_id INTEGER NOT NULL,
                activity_name VARCHAR NOT NULL,
                start_date DATE NULL,
                end_date DATE NULL,
                progress_percentage INTEGER NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY (master_project_id) REFERENCES master_projects(id)
            )
        ');
        DB::statement('DROP TABLE gantt_activities_old');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Not reversible — ERD rebuild is one-way
    }
};
