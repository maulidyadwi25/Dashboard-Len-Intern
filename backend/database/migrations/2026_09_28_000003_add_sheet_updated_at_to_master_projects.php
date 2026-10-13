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
            $table->string('sheet_updated_at')->nullable()->after('last_synced_at')
                ->comment('Tanggal update yang tercantum di Google Sheet (baris Tanggal Update)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_projects', function (Blueprint $table) {
            $table->dropColumn('sheet_updated_at');
        });
    }
};
