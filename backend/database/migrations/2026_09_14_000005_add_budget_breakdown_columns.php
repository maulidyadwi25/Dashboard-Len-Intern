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
        Schema::table('budget_breakdowns', function (Blueprint $table) {
            $table->decimal('sap_remaining', 15, 2)->default(0)->after('cost_center');
            $table->decimal('wbs_uninput', 15, 2)->default(0)->after('sap_remaining');
            $table->decimal('total_amount', 15, 2)->default(0)->after('wbs_uninput');
            $table->decimal('estimated_need', 15, 2)->default(0)->after('total_amount');
            $table->decimal('variance', 15, 2)->default(0)->after('estimated_need');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_breakdowns', function (Blueprint $table) {
            $table->dropColumn([
                'sap_remaining',
                'wbs_uninput',
                'total_amount',
                'estimated_need',
                'variance',
            ]);
        });
    }
};
