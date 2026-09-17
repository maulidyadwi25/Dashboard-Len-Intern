<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Matikan FK agar rebuild aman
        DB::statement('PRAGMA foreign_keys=OFF');

        // 1. project_links: id, master_project_id, project_id, ...
        DB::statement('CREATE TABLE project_links_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            master_project_id INTEGER,
            project_id INTEGER,
            sheet_key VARCHAR NOT NULL,
            sheet_name VARCHAR NOT NULL,
            data TEXT NOT NULL,
            row_count INTEGER NOT NULL DEFAULT 0,
            last_synced_at DATETIME,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (master_project_id) REFERENCES master_projects(id) ON DELETE SET NULL,
            FOREIGN KEY (project_id) REFERENCES master_projects(id) ON DELETE CASCADE
        )');
        DB::statement('INSERT INTO project_links_new (id, master_project_id, project_id, sheet_key, sheet_name, data, row_count, last_synced_at, created_at, updated_at)
            SELECT id, master_project_id, project_id, sheet_key, sheet_name, data, row_count, last_synced_at, created_at, updated_at FROM project_links');
        DB::statement('DROP TABLE project_links');
        DB::statement('ALTER TABLE project_links_new RENAME TO project_links');
        DB::statement('CREATE UNIQUE INDEX project_links_project_id_sheet_key_unique ON project_links (project_id, sheet_key)');

        // 2. procurements: id, master_project_id, project_id, ...
        DB::statement('CREATE TABLE procurements_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            master_project_id INTEGER,
            project_id INTEGER,
            no VARCHAR NOT NULL,
            parent_no VARCHAR,
            name VARCHAR NOT NULL,
            vendor VARCHAR,
            target_toa VARCHAR,
            status VARCHAR,
            progress VARCHAR,
            priority VARCHAR,
            jenis VARCHAR,
            payment_type VARCHAR,
            proc_policy VARCHAR,
            nilai_rab NUMERIC NOT NULL DEFAULT 0,
            realisasi NUMERIC NOT NULL DEFAULT 0,
            mata_uang VARCHAR NOT NULL DEFAULT \'IDR\',
            efisiensi VARCHAR,
            due_date VARCHAR,
            payment_details TEXT,
            payment_priority VARCHAR,
            estimated_delivery VARCHAR,
            delivery_status VARCHAR,
            payment_status TEXT,
            last_synced_at DATETIME,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (master_project_id) REFERENCES master_projects(id) ON DELETE SET NULL,
            FOREIGN KEY (project_id) REFERENCES master_projects(id) ON DELETE CASCADE
        )');
        DB::statement('INSERT INTO procurements_new (id, master_project_id, project_id, no, parent_no, name, vendor, target_toa, status, progress, priority, jenis, payment_type, proc_policy, nilai_rab, realisasi, mata_uang, efisiensi, due_date, payment_details, payment_priority, estimated_delivery, delivery_status, payment_status, last_synced_at, created_at, updated_at)
            SELECT id, master_project_id, project_id, no, parent_no, name, vendor, target_toa, status, progress, priority, jenis, payment_type, proc_policy, nilai_rab, realisasi, mata_uang, efisiensi, due_date, payment_details, payment_priority, estimated_delivery, delivery_status, payment_status, last_synced_at, created_at, updated_at FROM procurements');
        DB::statement('DROP TABLE procurements');
        DB::statement('ALTER TABLE procurements_new RENAME TO procurements');

        // 3. gantt_activities: id, master_project_id, project_id, ...
        DB::statement('CREATE TABLE gantt_activities_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            master_project_id INTEGER,
            project_id INTEGER,
            wbs_no VARCHAR,
            activity_name VARCHAR NOT NULL,
            type VARCHAR NOT NULL DEFAULT \'P\',
            weight VARCHAR,
            weekly_progress TEXT,
            start_week VARCHAR,
            end_week VARCHAR,
            last_synced_at DATETIME,
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (master_project_id) REFERENCES master_projects(id) ON DELETE SET NULL,
            FOREIGN KEY (project_id) REFERENCES master_projects(id) ON DELETE CASCADE
        )');
        DB::statement('INSERT INTO gantt_activities_new (id, master_project_id, project_id, wbs_no, activity_name, type, weight, weekly_progress, start_week, end_week, last_synced_at, created_at, updated_at)
            SELECT id, master_project_id, project_id, wbs_no, activity_name, type, weight, weekly_progress, start_week, end_week, last_synced_at, created_at, updated_at FROM gantt_activities');
        DB::statement('DROP TABLE gantt_activities');
        DB::statement('ALTER TABLE gantt_activities_new RENAME TO gantt_activities');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        // Tidak dikembalikan (urutan kolom tidak krusial untuk rollback)
    }
};
