<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Balikin kolom detail (biar sync+frontend jalan lagi),
     * pertahankan tambahan ERD: description, sheet_url, start/end_date, progress_percentage, unique project_code.
     */
    public function up(): void
    {
        // master_projects: tambah kolom sync lama
        Schema::table('master_projects', function (Blueprint $t) {
            if (! Schema::hasColumn('master_projects', 'spreadsheet_id')) {
                $t->string('spreadsheet_id')->nullable()->after('project_code');
            }
            if (! Schema::hasColumn('master_projects', 'contract_value')) {
                $t->string('contract_value')->nullable()->after('description');
            }
            if (! Schema::hasColumn('master_projects', 'overall_progress')) {
                $t->string('overall_progress')->nullable()->after('contract_value');
            }
            if (! Schema::hasColumn('master_projects', 'progress_trend')) {
                $t->string('progress_trend')->nullable()->after('overall_progress');
            }
            if (! Schema::hasColumn('master_projects', 'spi_value')) {
                $t->string('spi_value')->nullable()->after('progress_trend');
            }
            if (! Schema::hasColumn('master_projects', 'spi_status')) {
                $t->string('spi_status')->nullable()->after('spi_value');
            }
            if (! Schema::hasColumn('master_projects', 'last_synced_at')) {
                $t->timestamp('last_synced_at')->nullable()->after('spi_status');
            }
        });

        // Isi spreadsheet_id dari sheet_url yang tersimpan (format .../d/{id})
        $masters = DB::table('master_projects')->get(['id']);
        foreach ($masters as $m) {
            $link = DB::table('project_links')->where('master_project_id', $m->id)
                ->whereNotNull('sheet_url')->first();
            if ($link && preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $link->sheet_url, $mm)) {
                DB::table('master_projects')->where('id', $m->id)
                    ->whereNull('spreadsheet_id')
                    ->update(['spreadsheet_id' => $mm[1]]);
            }
        }
        // Fallback ke .env bila masih null
        $defaultSheet = env('GOOGLE_SHEET_ID', '');
        if ($defaultSheet) {
            DB::table('master_projects')->whereNull('spreadsheet_id')
                ->update(['spreadsheet_id' => $defaultSheet]);
        }

        // project_links: tambah kolom snapshot lama
        Schema::table('project_links', function (Blueprint $t) {
            if (! Schema::hasColumn('project_links', 'sheet_key')) {
                $t->string('sheet_key')->nullable()->after('master_project_id');
            }
            if (! Schema::hasColumn('project_links', 'data')) {
                $t->longText('data')->nullable()->after('sheet_url');
            }
            if (! Schema::hasColumn('project_links', 'row_count')) {
                $t->integer('row_count')->default(0)->after('data');
            }
            if (! Schema::hasColumn('project_links', 'last_synced_at')) {
                $t->timestamp('last_synced_at')->nullable()->after('row_count');
            }
        });

        // procurements: tambah kolom detail lama
        Schema::table('procurements', function (Blueprint $t) {
            if (! Schema::hasColumn('procurements', 'parent_no')) {
                $t->string('parent_no')->nullable()->after('no');
            }
            if (! Schema::hasColumn('procurements', 'target_toa')) {
                $t->string('target_toa')->nullable()->after('vendor');
            }
            if (! Schema::hasColumn('procurements', 'jenis')) {
                $t->string('jenis')->nullable()->after('priority');
            }
            if (! Schema::hasColumn('procurements', 'payment_type')) {
                $t->string('payment_type')->nullable()->after('jenis');
            }
            if (! Schema::hasColumn('procurements', 'proc_policy')) {
                $t->string('proc_policy')->nullable()->after('payment_type');
            }
            if (! Schema::hasColumn('procurements', 'efisiensi')) {
                $t->string('efisiensi')->nullable()->after('mata_uang');
            }
            if (! Schema::hasColumn('procurements', 'due_date')) {
                $t->string('due_date')->nullable()->after('efisiensi');
            }
            if (! Schema::hasColumn('procurements', 'payment_details')) {
                $t->json('payment_details')->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('procurements', 'payment_priority')) {
                $t->string('payment_priority')->nullable()->after('payment_details');
            }
            if (! Schema::hasColumn('procurements', 'estimated_delivery')) {
                $t->string('estimated_delivery')->nullable()->after('payment_priority');
            }
            if (! Schema::hasColumn('procurements', 'delivery_status')) {
                $t->string('delivery_status')->nullable()->after('estimated_delivery');
            }
            if (! Schema::hasColumn('procurements', 'payment_status')) {
                $t->json('payment_status')->nullable()->after('delivery_status');
            }
            if (! Schema::hasColumn('procurements', 'last_synced_at')) {
                $t->timestamp('last_synced_at')->nullable()->after('payment_status');
            }
        });

        // gantt_activities: tambah kolom matriks lama (pertahankan start/end_date + progress_percentage ERD)
        Schema::table('gantt_activities', function (Blueprint $t) {
            if (! Schema::hasColumn('gantt_activities', 'wbs_no')) {
                $t->string('wbs_no')->nullable()->after('master_project_id');
            }
            if (! Schema::hasColumn('gantt_activities', 'type')) {
                $t->string('type')->default('P')->after('activity_name');
            }
            if (! Schema::hasColumn('gantt_activities', 'weight')) {
                $t->string('weight')->nullable()->after('type');
            }
            if (! Schema::hasColumn('gantt_activities', 'weekly_progress')) {
                $t->json('weekly_progress')->nullable()->after('weight');
            }
            if (! Schema::hasColumn('gantt_activities', 'start_week')) {
                $t->string('start_week')->nullable()->after('weekly_progress');
            }
            if (! Schema::hasColumn('gantt_activities', 'end_week')) {
                $t->string('end_week')->nullable()->after('start_week');
            }
            if (! Schema::hasColumn('gantt_activities', 'last_synced_at')) {
                $t->timestamp('last_synced_at')->nullable()->after('end_week');
            }
        });
    }

    public function down(): void
    {
    }
};
