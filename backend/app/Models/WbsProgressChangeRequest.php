<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WbsProgressChangeRequest extends Model
{
    protected $fillable = [
        'master_project_id',
        'requested_by',
        'reviewed_by',
        'requester_name',
        'wbs_no',
        'year',
        'month',
        'week',
        'previous_percent',
        'requested_percent',
        'comment',
        'status',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_percent' => 'float',
            'requested_percent' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }
}
