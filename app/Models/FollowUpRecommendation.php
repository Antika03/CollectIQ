<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpRecommendation extends Model
{
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
}