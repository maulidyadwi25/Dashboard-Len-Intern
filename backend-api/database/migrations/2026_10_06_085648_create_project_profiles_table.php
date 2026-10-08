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
        Schema::create('project_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->unique()->constrained('master_projects')->cascadeOnDelete();
            $table->string('project_name')->nullable();
            $table->string('project_code')->nullable();
            $table->string('client_name')->nullable();
            $table->string('pimpro_name')->nullable();
            $table->string('report_pic')->nullable();
            $table->string('contract_value')->nullable();
            $table->string('progress_achieved')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_profiles');
    }
};
