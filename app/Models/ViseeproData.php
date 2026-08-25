<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViseeproData extends Model
{
    protected $fillable = [
        'customer_id',
        'activity_id',
        'ncli',
        'snd',
        'nama_perusahaan',
        'regional',
        'witel',
        'sto',
        'nama_agent',
        'activity_status',
        'activity_reason',
        'pic_name',
        'pic_role',
        'pic_cp',
        'address',
        'latitude',
        'longitude',
        'input_date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}