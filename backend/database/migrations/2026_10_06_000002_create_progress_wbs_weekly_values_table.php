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
        Schema::create('progress_wbs_weekly_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progress_wbs_item_id')
                ->constrained('progress_wbs_items')
                ->cascadeOnDelete();
            $table->string('type', 10)->default('plan'); // 'plan' (P) or 'actual' (A)
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week');
            $table->string('period_label', 20)->nullable(); // e.g. 'Jan W1'
            $table->decimal('value', 12, 8)->nullable();
            $table->timestamps();

            $table->unique(
                ['progress_wbs_item_id', 'type', 'year', 'month', 'week'],
                'wbs_weekly_values_item_type_period_unique'
            );
            $table->index(
                ['progress_wbs_item_id', 'year', 'month'],
                'wbs_weekly_values_lookup_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_wbs_weekly_values');
    }
};
