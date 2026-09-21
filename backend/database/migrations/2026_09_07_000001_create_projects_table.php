<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->string('sheet_name')->nullable();
            $table->string('name');
            $table->string('leader_name')->nullable();
            $table->string('client_name')->nullable();
            $table->decimal('net_contract_value', 15, 2)->default(0);
            $table->string('sheet_last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
