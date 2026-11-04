<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementRecommendation extends Model
{
    protected $fillable = [
        'master_project_id',
        'procurement_no',
        'summary',
        'recommendations',
        'source',
    ];

    protected $casts = [
        'recommendations' => 'array',
    ];
}
