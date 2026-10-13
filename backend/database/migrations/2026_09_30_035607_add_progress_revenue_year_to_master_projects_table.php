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
            $table->unsignedSmallInteger('progress_revenue_year')->nullable()->after('sheet_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_projects', function (Blueprint $table) {
            $table->dropColumn('progress_revenue_year');
        });
    }
};
