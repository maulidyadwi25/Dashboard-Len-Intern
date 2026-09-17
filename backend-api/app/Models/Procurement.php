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
        'nilai_rab',
        'realisasi',
        'mata_uang',
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
