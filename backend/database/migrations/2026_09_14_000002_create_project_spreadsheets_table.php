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
        Schema::create('project_spreadsheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->string('spreadsheet_id');
            $table->string('type'); // e.g. 'procurement', 'budget_control'
            $table->string('title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_spreadsheets');
    }
};
