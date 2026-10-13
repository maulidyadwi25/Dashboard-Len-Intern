<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->foreignId('project_id')->constrained('master_projects')->cascadeOnDelete();
            $table->string('project_name');
            $table->string('nama_PA')->nullable();
            $table->string('nama_pimpro')->nullable();
            $table->decimal('nilai_kontrak', 15, 2)->default(0);
            $table->timestamps();
        });

        // Seed initial project_profiles records from existing master_projects
        $masterProjects = DB::table('master_projects')->get();
        foreach ($masterProjects as $mp) {
            DB::table('project_profiles')->insert([
                'project_id' => $mp->id,
                'project_name' => $mp->project_name ?? 'REFURBISHMENT KRI SIGMA',
                'nama_PA' => 'KURNIAWAN',
                'nama_pimpro' => $mp->leader_name ?? 'SETIAJI',
                'nilai_kontrak' => $mp->net_contract_value ?? 7786777609140.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_profiles');
    }
};
