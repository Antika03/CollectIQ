<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramReminder extends Model
{
    protected $fillable = [
        'ar_agent_id',
        'customer_id',
        'promise_to_pay_id',
        'follow_up_recommendation_id',
        'type',
        'scheduled_at',
        'sent_at',
        'status',
        'message',
        'telegram_response',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'telegram_response' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function arAgent()
    {
        return $this->belongsTo(ArAgent::class);
    }

    public function promiseToPay()
    {
        return $this->belongsTo(PromiseToPay::class);
    }

    public function followUpRecommendation()
    {
        return $this->belongsTo(FollowUpRecommendation::class);
    }
}