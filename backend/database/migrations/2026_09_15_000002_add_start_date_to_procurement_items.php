<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->string('start_date')->nullable()->after('status');
            $table->string('tgl_mulai')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'tgl_mulai']);
        });
    }
};