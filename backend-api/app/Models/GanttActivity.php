<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GanttActivity extends Model
{
    use HasFactory;

    protected $table = 'gantt_activities';

    protected $fillable = [
        'master_project_id',
        'activity_name',
        'start_date',
        'end_date',
        'progress_percentage',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress_percentage' => 'integer',
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
