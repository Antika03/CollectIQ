<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArAgent extends Model
{
    protected $fillable = [
        'name',
        'chat_id_telegram',
        'is_active',
    ];

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function followUpRecommendations()
    {
        return $this->hasMany(FollowUpRecommendation::class);
    }

    public function telegramReminders()
    {
        return $this->hasMany(TelegramReminder::class);
    }

    public function caringLogs()
    {
        return $this->hasMany(CaringLog::class);
    }
}