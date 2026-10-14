<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_projects', function (Blueprint $table) {
            if (Schema::hasColumn('master_projects', 'project_id')) {
                // Drop index first for SQLite compatibility in tests
                try {
                    $table->dropIndex(['project_id']);
                } catch (\Throwable $e) {
                    // ignore if index already absent
                }
                $table->dropColumn('project_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('master_projects', 'project_id')) {
                $table->string('project_id')->nullable()->after('id')->index();
            }
        });
    }
};
