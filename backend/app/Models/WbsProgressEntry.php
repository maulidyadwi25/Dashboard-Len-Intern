<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WbsProgressEntry extends Model
{
    protected $table = 'progress_wbs_progress_entries';

    protected $fillable = [
        'master_project_id',
        'wbs_no',
        'year',
        'month',
        'week',
        'percent',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'week' => 'integer',
        'percent' => 'decimal:2',
    ];

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }
}
