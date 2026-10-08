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
        Schema::table('procurements', function (Blueprint $table) {
            $table->string('target_tgl')->nullable()->after('vendor');
            $table->string('jenis')->nullable()->after('target_tgl');
            $table->string('payment_type')->nullable()->after('jenis');
            $table->string('proc_policy')->nullable()->after('payment_type');
            $table->string('no_hps')->nullable()->after('proc_policy');
            $table->string('tanggal_hps')->nullable()->after('no_hps');
            $table->string('rks')->nullable()->after('tanggal_hps');
            $table->string('tanggal_rks')->nullable()->after('rks');
            $table->string('pic_ome')->nullable()->after('tanggal_rks');
            $table->string('no_pr')->nullable()->after('pic_ome');
            $table->string('proc_ops')->nullable()->after('no_pr');
            $table->string('no_po')->nullable()->after('proc_ops');
            $table->string('kontrak_status')->nullable()->after('no_po');
            $table->string('due_date')->nullable()->after('mata_uang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->dropColumn([
                'target_tgl', 'jenis', 'payment_type', 'proc_policy',
                'no_hps', 'tanggal_hps', 'rks', 'tanggal_rks',
                'pic_ome', 'no_pr', 'proc_ops', 'no_po',
                'kontrak_status', 'due_date',
            ]);
        });
    }
};
