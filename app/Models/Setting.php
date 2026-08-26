<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'c3mr_url',
        'report_prq_url',
        'viseepro_url',
        'last_sync_at',
        'last_sync_status',
        'last_sync_result',
    ];

    protected $casts = [
        'last_sync_at'     => 'datetime',
        'last_sync_result' => 'array',
    ];
}