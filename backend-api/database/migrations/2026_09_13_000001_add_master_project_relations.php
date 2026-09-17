<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom spreadsheet_id ke tabel projects jika belum ada
        if (!Schema::hasColumn('projects', 'spreadsheet_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('spreadsheet_id')->nullable()->after('project_code');
            });
        }

        // Isi spreadsheet_id default dari env/config untuk project pertama
        $defaultSpreadsheetId = config('services.google.sheet_id') ?? env('GOOGLE_SHEET_ID', '');
        DB::table('projects')->whereNull('spreadsheet_id')->update([
            'spreadsheet_id' => $defaultSpreadsheetId,
        ]);

        // 2. Tambah kolom project_id ke tabel gantt_activities
        if (!Schema::hasColumn('gantt_activities', 'project_id')) {
            Schema::table('gantt_activities', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->cascadeOnDelete();
            });

            // Tautkan data gantt lama ke project ID 1
            $firstProjectId = DB::table('projects')->value('id') ?? 1;
            DB::table('gantt_activities')->whereNull('project_id')->update([
                'project_id' => $firstProjectId,
            ]);
        }

        // 3. Tambah kolom project_id ke tabel procurements
        if (!Schema::hasColumn('procurements', 'project_id')) {
            Schema::table('procurements', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->cascadeOnDelete();
            });

            // Tautkan data procurement lama ke project ID 1
            $firstProjectId = DB::table('projects')->value('id') ?? 1;
            DB::table('procurements')->whereNull('project_id')->update([
                'project_id' => $firstProjectId,
            ]);
        }

        // 4. Tambah kolom project_id ke tabel sheet_data
        if (!Schema::hasColumn('sheet_data', 'project_id')) {
            Schema::table('sheet_data', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->cascadeOnDelete();
            });

            // Tautkan snapshot sheet_data lama ke project ID 1
            $firstProjectId = DB::table('projects')->value('id') ?? 1;
            DB::table('sheet_data')->whereNull('project_id')->update([
                'project_id' => $firstProjectId,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sheet_data', 'project_id')) {
            Schema::table('sheet_data', function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_id');
            });
        }

        if (Schema::hasColumn('procurements', 'project_id')) {
            Schema::table('procurements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_id');
            });
        }

        if (Schema::hasColumn('gantt_activities', 'project_id')) {
            Schema::table('gantt_activities', function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_id');
            });
        }

        if (Schema::hasColumn('projects', 'spreadsheet_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('spreadsheet_id');
            });
        }
    }
};
