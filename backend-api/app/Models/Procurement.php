<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Procurement extends Model
{
    use HasFactory;

    protected $table = 'procurements';

    protected $fillable = [
        'master_project_id',
        'no',
        'name',
        'vendor',
        'target_tgl',
        'jenis',
        'payment_type',
        'proc_policy',
        'no_hps',
        'tanggal_hps',
        'rks',
        'tanggal_rks',
        'pic_ome',
        'no_pr',
        'proc_ops',
        'no_po',
        'kontrak_status',
        'nilai_rab',
        'realisasi',
        'mata_uang',
        'due_date',
        'status',
        'progress',
        'priority',
    ];

    protected $casts = [
        'nilai_rab' => 'float',
        'realisasi' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }
}
