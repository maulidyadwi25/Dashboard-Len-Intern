<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WbsItem extends Model
{
    protected $table = 'progress_wbs_items';

    protected $fillable = [
        'master_project_id',
        'wbs_no',
        'parent_wbs_no',
        'level',
        'milestone',
        'activity_name',
        'pic',
        'divisi',
        'nilai_kegiatan',
        'bobot_plan',
        'bobot_real',
        'plan_start_period',
        'plan_end_period',
        'actual_start_period',
        'actual_end_period',
        'durasi',
        'status',
        'order_index',
    ];

    protected $casts = [
        'level' => 'integer',
        'nilai_kegiatan' => 'decimal:2',
        'bobot_plan' => 'decimal:4',
        'bobot_real' => 'decimal:4',
        'order_index' => 'integer',
    ];

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }

    public function weeklyValues(): HasMany
    {
        return $this->hasMany(WbsWeeklyValue::class, 'progress_wbs_item_id');
    }

    public function planValues(): HasMany
    {
        return $this->hasMany(WbsWeeklyValue::class, 'progress_wbs_item_id')->where('type', 'plan');
    }

    public function actualValues(): HasMany
    {
        return $this->hasMany(WbsWeeklyValue::class, 'progress_wbs_item_id')->where('type', 'actual');
    }
}
