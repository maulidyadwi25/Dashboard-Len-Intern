<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressRevenueValue extends Model
{
    protected $fillable = [
        'master_project_id',
        'year',
        'month',
        'week',
        'is_baseline',
        'indicator_key',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:8',
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
