<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_profiles_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('project_name')->nullable();
            $table->string('pimpro_name')->nullable();
            $table->string('report_pic')->nullable();
            $table->string('contract_value')->nullable();
            $table->timestamps();
            $table->unique(['master_project_id', 'type']);
        });
        DB::table('project_profiles_new')->insertUsing(
            ['id', 'master_project_id', 'type', 'project_name', 'pimpro_name', 'report_pic', 'contract_value', 'created_at', 'updated_at'],
            DB::table('project_profiles')->select('id', 'master_project_id', 'type', 'project_name', 'pimpro_name', 'report_pic', 'contract_value', 'created_at', 'updated_at')
        );
        Schema::drop('project_profiles');
        Schema::rename('project_profiles_new', 'project_profiles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('project_profiles_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->string('project_name')->nullable();
            $table->string('pimpro_name')->nullable();
            $table->string('report_pic')->nullable();
            $table->string('contract_value')->nullable();
            $table->timestamps();
            $table->string('type')->nullable();
            $table->unique(['master_project_id', 'type']);
        });
        DB::table('project_profiles_old')->insertUsing(
            ['id', 'master_project_id', 'project_name', 'pimpro_name', 'report_pic', 'contract_value', 'created_at', 'updated_at', 'type'],
            DB::table('project_profiles')->select('id', 'master_project_id', 'project_name', 'pimpro_name', 'report_pic', 'contract_value', 'created_at', 'updated_at', 'type')
        );
        Schema::drop('project_profiles');
        Schema::rename('project_profiles_old', 'project_profiles');
    }
};
