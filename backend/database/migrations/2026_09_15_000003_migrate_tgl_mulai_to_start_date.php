<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Copy tgl_mulai values to start_date, then drop tgl_mulai.
     */
    public function up(): void
    {
        // Copy nilai tgl_mulai ke start_date untuk semua baris yang belum memiliki start_date
        \DB::statement("
            UPDATE procurement_items
            SET start_date = COALESCE(start_date, tgl_mulai)
            WHERE tgl_mulai IS NOT NULL AND start_date IS NULL
        ");

        // Drop kolom tgl_mulai
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->dropColumn('tgl_mulai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->string('tgl_mulai')->nullable()->after('start_date');
        });

        // Copy nilai start_date kembali ke tgl_mulai
        \DB::statement("
            UPDATE procurement_items
            SET tgl_mulai = COALESCE(tgl_mulai, start_date)
            WHERE start_date IS NOT NULL AND tgl_mulai IS NULL
        ");
    }
};