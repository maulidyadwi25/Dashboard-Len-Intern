<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('budget_summaries');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('budget_summaries', function ($table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('sisa_anggaran_sap', 15, 2)->default(0);
            $table->decimal('wbs_belum_input', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('estimasi_kebutuhan', 15, 2)->default(0);
            $table->decimal('selisih_variance', 15, 2)->default(0);
            $table->timestamps();
        });
    }
};
