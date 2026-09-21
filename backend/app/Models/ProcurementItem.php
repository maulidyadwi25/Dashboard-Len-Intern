<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementItem extends Model
{
    protected $fillable = [
        'master_project_id',
        'no',
        'is_sub_item',
        'main_no',
        'category', 'priority',
        'item_name',
        'vendor',
        'target_date',
        'status',
        'start_date',
    ];

    protected $casts = [
        'is_sub_item' => 'boolean',
        'main_no' => 'integer',
    ];

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }
}
