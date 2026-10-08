<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProject extends Model
{
    use HasFactory;

    protected $table = 'master_projects';

    protected $fillable = [
        'project_code',
        'project_name',
        'type',
        'spreadsheet_id',
        'description',
    ];

    /** 1-to-many ke PROJECT_LINKS */
    public function projectLinks(): HasMany
    {
        return $this->hasMany(ProjectLink::class, 'master_project_id');
    }

    public function sheetData(): HasMany
    {
        return $this->hasMany(ProjectLink::class, 'master_project_id');
    }

    /** 1-to-many ke PROCUREMENTS */
    public function procurements(): HasMany
    {
        return $this->hasMany(Procurement::class, 'master_project_id');
    }

    /** 1-to-many ke GANTT_ACTIVITIES */
    public function ganttActivities(): HasMany
    {
        return $this->hasMany(GanttActivity::class, 'master_project_id');
    }
}
