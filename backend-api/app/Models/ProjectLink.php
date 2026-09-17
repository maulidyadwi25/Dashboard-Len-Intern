<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLink extends Model
{
    use HasFactory;

    protected $table = 'project_links';

    protected $fillable = [
        'master_project_id',
        'sheet_name',
        'sheet_url',
        'sheet_key',
        'data',
        'row_count',
        'last_synced_at',
    ];

    protected $casts = [
        'data' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }

    public function getSpreadsheetIdAttribute(): ?string
    {
        if ($this->sheet_url && preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $this->sheet_url, $m)) {
            return $m[1];
        }
        return $this->masterProject?->spreadsheet_id;
    }
}
