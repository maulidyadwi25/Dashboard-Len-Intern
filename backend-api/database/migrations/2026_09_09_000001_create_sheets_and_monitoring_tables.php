<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Snapshot Sheet Data (Untuk query cepat ke Web)
        Schema::create('sheet_data', function (Blueprint $table) {
            $table->id();
            $table->string('sheet_key')->unique();
            $table->string('sheet_name');
            $table->longText('data'); // JSON 2D array
            $table->integer('row_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Project Header (Untuk data ringkasan proyek dari Sheet '2. Progress & Revenue')
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name')->nullable();
            $table->string('project_code')->nullable();
            $table->string('contract_value')->nullable();
            $table->string('overall_progress')->nullable();
            $table->string('progress_trend')->nullable();
            $table->string('spi_value')->nullable();
            $table->string('spi_status')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Gantt Activities (Untuk WBS / Jadwal Gantt Chart dari Sheet '2. Progress & Revenue')
        Schema::create('gantt_activities', function (Blueprint $table) {
            $table->id();
            $table->string('wbs_no')->nullable();
            $table->string('activity_name');
            $table->string('type')->default('P'); // P = Plan, A = Actual
            $table->string('weight')->nullable(); // Bobot %
            $table->json('weekly_progress')->nullable(); // JSON data mingguan (Jan-Des W1-W4)
            $table->string('start_week')->nullable();
            $table->string('end_week')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // 4. Tabel Procurements (Untuk Data Pengadaan dari Sheet '6. Procurement')
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->string('no'); // Misal '1', '1.1'
            $table->string('parent_no')->nullable(); // Relasi induk sub-nomor
            $table->string('name');
            $table->string('vendor')->nullable();
            $table->string('target_toa')->nullable();
            $table->string('status')->nullable(); // In Progress, Done
            $table->string('progress')->nullable(); // SPH, HPS, RKS, PO, KONTRAK, dll
            $table->string('priority')->nullable(); // High, Medium, Low
            $table->string('jenis')->nullable(); // Barang, Jasa, Barang & Jasa
            $table->string('payment_type')->nullable();
            $table->string('proc_policy')->nullable();
            $table->decimal('nilai_rab', 20, 2)->default(0);
            $table->decimal('realisasi', 20, 2)->default(0);
            $table->string('mata_uang')->default('IDR');
            $table->string('efisiensi')->nullable();
            $table->string('due_date')->nullable();
            $table->json('payment_details')->nullable(); // DP, Termin 1-3 (tgl, nilai, ok, bpp)
            $table->string('payment_priority')->nullable();
            $table->string('estimated_delivery')->nullable();
            $table->string('delivery_status')->nullable();
            $table->json('payment_status')->nullable(); // Status bayar DP, T1, T2, T3
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurements');
        Schema::dropIfExists('gantt_activities');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('sheet_data');
    }
};
