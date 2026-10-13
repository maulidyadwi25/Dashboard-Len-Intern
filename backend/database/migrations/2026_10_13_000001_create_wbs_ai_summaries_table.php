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
        Schema::create('wbs_ai_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->date('summary_date');
            $table->json('content');
            $table->enum('source', ['ai', 'manual'])->default('ai');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['master_project_id', 'summary_date'], 'wbs_ai_summaries_unique');
            $table->index(['master_project_id', 'source'], 'wbs_ai_summaries_project_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wbs_ai_summaries');
    }
};
