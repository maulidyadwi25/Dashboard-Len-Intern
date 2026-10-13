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
        Schema::table('project_profiles', function (Blueprint $table) {
            $table->string('type')->default('general')->after('project_id');
            $table->unique(['project_id', 'type']);
        });

        // Set existing profile as 'budget_control' or create default profiles
        $profiles = DB::table('project_profiles')->get();
        foreach ($profiles as $p) {
            DB::table('project_profiles')
                ->where('id', $p->id)
                ->update(['type' => 'budget_control']);

            // Insert initial procurement profile for this project
            DB::table('project_profiles')->insertOrIgnore([
                'project_id' => $p->project_id,
                'type' => 'procurement',
                'project_name' => $p->project_name,
                'nama_PA' => $p->nama_PA,
                'nama_pimpro' => $p->nama_pimpro,
                'nilai_kontrak' => $p->nilai_kontrak,
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
        Schema::table('project_profiles', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
