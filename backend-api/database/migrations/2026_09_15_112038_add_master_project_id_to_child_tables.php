<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom master_project_id (BIGINT FK -> master_projects.id)
     * ke project_links, procurements, gantt_activities.
     * Kolom lama project_id tetap ada, isi disinkronkan.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['project_links', 'procurements', 'gantt_activities'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (! Schema::hasColumn($table, 'master_project_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('master_project_id')
                        ->nullable()
                        ->after('project_id')
                        ->constrained('master_projects')
                        ->nullOnDelete();
                });
            }
        }

        Schema::enableForeignKeyConstraints();

        // Backfill: isi dari project_id yang sudah ada, fallback ke id 1
        $firstId = DB::table('master_projects')->value('id') ?? 1;
        foreach (['project_links', 'procurements', 'gantt_activities'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'master_project_id')) {
                continue;
            }
            DB::table($table)->whereNull('master_project_id')->whereNotNull('project_id')
                ->update(['master_project_id' => DB::raw('project_id')]);
            DB::table($table)->whereNull('master_project_id')
                ->update(['master_project_id' => $firstId]);
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['gantt_activities', 'procurements', 'project_links'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'master_project_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropConstrainedForeignId('master_project_id');
                });
            }
        }
        Schema::enableForeignKeyConstraints();
    }
};
