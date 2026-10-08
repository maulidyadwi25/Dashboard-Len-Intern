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
        Schema::table('project_profiles', function (Blueprint $table) {
            $table->dropColumn(['project_code', 'progress_achieved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_profiles', function (Blueprint $table) {
            $table->string('project_code')->nullable()->after('project_name');
            $table->string('progress_achieved')->nullable()->after('contract_value');
        });
    }
};
