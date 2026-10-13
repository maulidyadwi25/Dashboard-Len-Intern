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
        Schema::create('progress_wbs_progress_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')
                ->constrained('master_projects')
                ->cascadeOnDelete();
            $table->string('wbs_no', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week');
            $table->decimal('percent', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['master_project_id', 'wbs_no', 'year', 'month', 'week'],
                'wbs_progress_entries_unique'
            );

            $table->index(
                ['master_project_id', 'wbs_no'],
                'wbs_progress_entries_project_wbs_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_wbs_progress_entries');
    }
};
