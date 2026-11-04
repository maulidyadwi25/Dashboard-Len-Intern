<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->string('procurement_no', 20);
            $table->text('summary')->nullable();
            $table->json('recommendations');
            $table->string('source', 20)->default('ai');
            $table->timestamps();
            $table->unique(['master_project_id', 'procurement_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_recommendations');
    }
};
