<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MasterProject extends Model
{
    protected $fillable = [
        'project_id',
        'project_code',
        'project_name',
        'client_name',
        'leader_name',
        'net_contract_value',
        'spreadsheet_url',
        'spreadsheet_id',
        'type',
        'title',
        'is_active',
        'last_synced_at',
        'sheet_updated_at',
        'progress_revenue_year',
    ];

    protected $casts = [
        'net_contract_value' => 'decimal:2',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'progress_revenue_year' => 'integer',
    ];

    /**
     * Relationship with all ProjectProfiles.
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(ProjectProfile::class, 'project_id', 'id');
    }

    /**
     * Profile specific to Budget Control sheet.
     */
    public function budgetProfile(): HasOne
    {
        return $this->hasOne(ProjectProfile::class, 'project_id', 'id')
            ->where('type', 'budget_control');
    }

    /**
     * Profile specific to Procurement sheet.
     */
    public function procurementProfile(): HasOne
    {
        return $this->hasOne(ProjectProfile::class, 'project_id', 'id')
            ->where('type', 'procurement');
    }

    /**
     * Helper to get single profile by type or fallback to first.
     */
    public function profile(?string $type = null): ?ProjectProfile
    {
        if ($type) {
            return $this->profiles->firstWhere('type', $type)
                ?: $this->profiles()->where('type', $type)->first();
        }

        return $this->profiles->first() ?: $this->profiles()->first();
    }

    /**
     * Profile specific to Progress & Revenue sheet.
     * Stored in project_profiles with type='progress_revenue'.
     */
    public function progressRevenueProfile(): HasOne
    {
        return $this->hasOne(ProjectProfile::class, 'project_id', 'id')
            ->where('type', 'progress_revenue');
    }

    /**
     * Transactional items parsed from Google Sheets.
     */
    public function procurementItems(): HasMany
    {
        return $this->hasMany(ProcurementItem::class, 'master_project_id');
    }

    public function budgetBreakdowns(): HasMany
    {
        return $this->hasMany(BudgetBreakdown::class, 'master_project_id');
    }

    public function progressRevenueValues(): HasMany
    {
        return $this->hasMany(ProgressRevenueValue::class, 'master_project_id');
    }

    public function wbsItems(): HasMany
    {
        return $this->hasMany(WbsItem::class, 'master_project_id');
    }

    public function wbsProgressEntries(): HasMany
    {
        return $this->hasMany(WbsProgressEntry::class, 'master_project_id');
    }

    public function wbsAiSummaries(): HasMany
    {
        return $this->hasMany(WbsAiSummary::class, 'master_project_id');
    }

    /**
     * Extract spreadsheet ID from URL or return spreadsheet_id directly.
     */
    public function getEffectiveSpreadsheetIdAttribute(): string
    {
        if (! empty($this->spreadsheet_id)) {
            return (string) $this->spreadsheet_id;
        }

        if (! empty($this->spreadsheet_url)) {
            if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $this->spreadsheet_url, $matches)) {
                return $matches[1];
            }
        }

        return (string) (config('services.google_sheets.spreadsheet_id')
            ?? env('GOOGLE_SPREADSHEET_ID', '1NlWhk4kzjKmeuujnMxJsZpL8LWfZudY5fC4nEyUWU70'));
    }
}
