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
        Schema::create('progress_wbs_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->string('wbs_no', 50)->index();
            $table->string('parent_wbs_no', 50)->nullable()->index();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('milestone')->nullable();
            $table->text('activity_name')->nullable();
            $table->string('pic')->nullable();
            $table->string('divisi')->nullable();
            $table->decimal('nilai_kegiatan', 18, 2)->default(0);
            $table->decimal('bobot_plan', 8, 4)->default(0);
            $table->decimal('bobot_real', 8, 4)->default(0);
            $table->string('plan_start_period', 50)->nullable();
            $table->string('plan_end_period', 50)->nullable();
            $table->string('actual_start_period', 50)->nullable();
            $table->string('actual_end_period', 50)->nullable();
            $table->string('durasi', 100)->nullable();
            $table->string('status', 50)->nullable()->default('on_track');
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();

            $table->index(['master_project_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_wbs_items');
    }
};
