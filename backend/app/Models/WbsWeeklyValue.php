<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WbsWeeklyValue extends Model
{
    protected $table = 'progress_wbs_weekly_values';

    protected $fillable = [
        'progress_wbs_item_id',
        'type',
        'year',
        'month',
        'week',
        'period_label',
        'value',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'week' => 'integer',
        'value' => 'decimal:8',
    ];

    public function wbsItem(): BelongsTo
    {
        return $this->belongsTo(WbsItem::class, 'progress_wbs_item_id');
    }
}
