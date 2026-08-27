<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'c3mr_url',
        'priti_url',
        'report_prq_url',
        'viseepro_url',
        'telegram_reminder_enabled',
        'telegram_morning_time',
        'telegram_afternoon_time',
        'last_sync_at',
        'last_sync_status',
        'last_sync_result',
    ];

    protected $casts = [
        'telegram_reminder_enabled' => 'boolean',
        'last_sync_at'              => 'datetime',
        'last_sync_result'          => 'array',
    ];
}