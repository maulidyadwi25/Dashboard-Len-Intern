<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wbs_progress_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')
                ->constrained('master_projects')
                ->cascadeOnDelete();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('requester_name');
            $table->string('wbs_no', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week');
            $table->decimal('previous_percent', 5, 2)->nullable();
            $table->decimal('requested_percent', 5, 2);
            $table->text('comment');
            $table->string('status', 20)->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['master_project_id', 'status', 'created_at'],
                'wbs_progress_requests_queue_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wbs_progress_change_requests');
    }
};
