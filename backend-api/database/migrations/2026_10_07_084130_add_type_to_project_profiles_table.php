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
        Schema::table('project_profiles', function (Blueprint $table) {
            $table->string('type')->nullable()->after('master_project_id');
        });
        // Baris yang ada berasal dari sheet progress → beri type-nya dulu
        DB::table('project_profiles')->whereNull('type')->update(['type' => 'progress_revenue']);
        Schema::table('project_profiles', function (Blueprint $table) {
            $table->dropUnique(['master_project_id']);
            $table->unique(['master_project_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_profiles', function (Blueprint $table) {
            $table->dropUnique(['master_project_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
