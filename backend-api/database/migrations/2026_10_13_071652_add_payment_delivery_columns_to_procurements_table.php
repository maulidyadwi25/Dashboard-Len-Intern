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
       Schema::table('procurements', function (Blueprint $table) {
            $table->string('payment_priority')->nullable()->after('priority');
            $table->string('delivery_status')->nullable()->after('payment_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->dropColumn(['payment_priority', 'delivery_status']);
        });
    }
};
