<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WbsAiSummary extends Model
{
    protected $table = 'wbs_ai_summaries';

    protected $fillable = [
        'master_project_id',
        'summary_date',
        'content',
        'source',
        'generated_at',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'content' => 'array',
        'generated_at' => 'datetime',
    ];

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }
}
