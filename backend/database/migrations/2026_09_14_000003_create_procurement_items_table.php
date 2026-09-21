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
        Schema::create('procurement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->string('no')->nullable();
            $table->boolean('is_sub_item')->default(false);
            $table->integer('main_no')->nullable();
            $table->string('category');
            $table->string('item_name');
            $table->string('vendor')->nullable();
            $table->string('target_date')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_items');
    }
};
