<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_projects', function (Blueprint $table) {
            $table->string('project_id')->nullable()->after('id')->index();
            $table->text('spreadsheet_url')->nullable()->after('net_contract_value');
            $table->string('spreadsheet_id')->nullable()->after('spreadsheet_url');
            $table->string('type')->default('procurement')->after('spreadsheet_id');
            $table->string('title')->nullable()->after('type');
            $table->boolean('is_active')->default(true)->after('title');
            $table->timestamp('last_synced_at')->nullable()->after('is_active');
        });

        // Migrate data from project_spreadsheets if table exists
        if (Schema::hasTable('project_spreadsheets')) {
            $spreadsheets = DB::table('project_spreadsheets')->orderBy('id')->get();

            foreach ($spreadsheets as $sheet) {
                DB::table('master_projects')
                    ->where('id', $sheet->master_project_id)
                    ->update([
                        'project_id' => DB::raw('COALESCE(project_id, project_code)'),
                        'spreadsheet_id' => $sheet->spreadsheet_id,
                        'spreadsheet_url' => "https://docs.google.com/spreadsheets/d/{$sheet->spreadsheet_id}/edit",
                        'type' => $sheet->type ?? 'procurement',
                        'title' => $sheet->title ?? 'Google Spreadsheet',
                        'is_active' => (bool) $sheet->is_active,
                        'last_synced_at' => $sheet->last_synced_at,
                    ]);
            }

            Schema::dropIfExists('project_spreadsheets');
        } else {
            // Fill default spreadsheet ID if empty
            DB::table('master_projects')
                ->whereNull('spreadsheet_id')
                ->update([
                    'project_id' => DB::raw('COALESCE(project_id, project_code)'),
                    'spreadsheet_id' => '1NlWhk4kzjKmeuujnMxJsZpL8LWfZudY5fC4nEyUWU70',
                    'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/1NlWhk4kzjKmeuujnMxJsZpL8LWfZudY5fC4nEyUWU70/edit',
                    'type' => 'procurement',
                    'title' => 'Procurement & Budget Sheet',
                    'is_active' => true,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('project_spreadsheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->string('spreadsheet_id');
            $table->string('type');
            $table->string('title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::table('master_projects', function (Blueprint $table) {
            $table->dropColumn([
                'project_id',
                'spreadsheet_url',
                'spreadsheet_id',
                'type',
                'title',
                'is_active',
                'last_synced_at',
            ]);
        });
    }
};
