<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MasterProject extends Model
{
    protected $fillable = [
        'project_code',
        'project_name',
        'client_name',
        'leader_name',
        'net_contract_value',
    ];

    protected $casts = [
        'net_contract_value' => 'decimal:2',
    ];

    public function projectDetail(): HasOne
    {
        return $this->hasOne(Project::class, 'project_code', 'project_code');
    }

    public function spreadsheets(): HasMany
    {
        return $this->hasMany(ProjectSpreadsheet::class, 'master_project_id');
    }

    public function procurementItems(): HasMany
    {
        return $this->hasMany(ProcurementItem::class, 'master_project_id');
    }

    public function budgetBreakdowns(): HasMany
    {
        return $this->hasMany(BudgetBreakdown::class, 'master_project_id');
    }
}
