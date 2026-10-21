<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProfile extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'project_name',
        'project_code',
        'client_name',
        'nama_PA',
        'nama_pimpro',
        'nilai_kontrak',
    ];

    protected $casts = [
        'nilai_kontrak' => 'decimal:2',
    ];

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'project_id', 'id');
    }
}
