<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * projects -> master_projects, sheet_data -> project_links
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('projects') && ! Schema::hasTable('master_projects')) {
            Schema::rename('projects', 'master_projects');
        }

        if (Schema::hasTable('sheet_data') && ! Schema::hasTable('project_links')) {
            Schema::rename('sheet_data', 'project_links');
        }

        // Perbaiki unique: dulu sheet_key unique sendirian, sekarang composite (project_id, sheet_key)
        // agar multi-project bisa punya sheet_key yang sama.
        if (Schema::hasTable('project_links')) {
            try {
                Schema::table('project_links', function (Blueprint $table) {
                    $table->dropUnique(['sheet_key']);
                });
            } catch (\Throwable $e) {
                // index mungkin sudah tidak ada, abaikan
            }
            // Cek apakah composite unique sudah ada via sqlite_master / doctrine
            $hasComposite = false;
            try {
                $indexes = Schema::getIndexes('project_links');
                foreach ($indexes as $idx) {
                    $cols = $idx['columns'] ?? [];
                    if ($cols === ['project_id', 'sheet_key'] || $cols === ['sheet_key', 'project_id']) {
                        $hasComposite = true;
                        break;
                    }
                }
            } catch (\Throwable $e) {
            }
            if (! $hasComposite) {
                try {
                    Schema::table('project_links', function (Blueprint $table) {
                        $table->unique(['project_id', 'sheet_key']);
                    });
                } catch (\Throwable $e) {
                    // abaikan jika gagal (misal data duplikat lama)
                }
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('project_links') && ! Schema::hasTable('sheet_data')) {
            Schema::rename('project_links', 'sheet_data');
        }

        if (Schema::hasTable('master_projects') && ! Schema::hasTable('projects')) {
            Schema::rename('master_projects', 'projects');
        }

        Schema::enableForeignKeyConstraints();
    }
};
