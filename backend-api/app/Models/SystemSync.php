<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSync extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'last_synced_at',
    ];
}