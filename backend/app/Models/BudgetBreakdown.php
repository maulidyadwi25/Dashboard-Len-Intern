<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetBreakdown extends Model
{
    protected $fillable = [
        'master_project_id',
        'cost_center',
        'sap_remaining',
        'wbs_uninput',
        'total_amount',
        'estimated_need',
        'variance',
        'allocated_amount',
        'used_amount',
    ];

    protected $casts = [
        'sap_remaining' => 'decimal:2',
        'wbs_uninput' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'estimated_need' => 'decimal:2',
        'variance' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
    ];

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }
}
