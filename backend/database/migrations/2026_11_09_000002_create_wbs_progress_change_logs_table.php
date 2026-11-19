<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wbs_progress_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')
                ->constrained('master_projects')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_role', 2);
            $table->string('wbs_no', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week');
            $table->decimal('previous_percent', 5, 2)->nullable();
            $table->decimal('new_percent', 5, 2);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(
                ['master_project_id', 'wbs_no', 'year', 'month', 'week'],
                'wbs_progress_changes_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wbs_progress_change_logs');
    }
};
