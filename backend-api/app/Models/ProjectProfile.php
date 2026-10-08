<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProfile extends Model
{
    use HasFactory;

    protected $table = 'project_profiles';

    protected $fillable = [
        'master_project_id',
        'type',
        'project_name',
        'pimpro_name',
        'report_pic',
        'contract_value',
    ];

    public function masterProject(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }
}
