<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WbsProgressChangeLog extends Model
{
    protected $fillable = [
        'master_project_id',
        'user_id',
        'actor_name',
        'actor_role',
        'wbs_no',
        'year',
        'month',
        'week',
        'previous_percent',
        'new_percent',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'previous_percent' => 'float',
            'new_percent' => 'float',
        ];
    }

    /** @return BelongsTo<MasterProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(MasterProject::class, 'master_project_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
