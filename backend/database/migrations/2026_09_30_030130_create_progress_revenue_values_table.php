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
        Schema::create('progress_revenue_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week')->nullable();
            $table->boolean('is_baseline')->default(false);
            $table->string('indicator_key');
            $table->decimal('value', 12, 8)->nullable();
            $table->timestamps();

            $table->index(
                ['master_project_id', 'year', 'month', 'indicator_key'],
                'progress_revenue_values_lookup_index'
            );
        });

        /*
         * A plain UNIQUE(master_project_id, year, month, week, indicator_key) cannot
         * prevent duplicate baseline rows: both MySQL and SQLite treat NULL as
         * distinct in unique indexes, so repeated baseline inserts with week = NULL
         * would all succeed. Indexing coalesce(week, 0) collapses the NULL baseline
         * to a single sentinel value, guaranteeing one row per period + indicator.
         */
        DB::statement(
            'CREATE UNIQUE INDEX progress_revenue_values_period_unique'
            .' ON progress_revenue_values (master_project_id, year, month, (coalesce(week, 0)), indicator_key)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_revenue_values');
    }
};
