<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromiseToPay extends Model
{
    protected $fillable = [
        'customer_id',
        'visit_id',
        'tanggal_janji_bayar',
        'status',
        'realisasi_bayar_at',
    ];

    protected $casts = [
        'tanggal_janji_bayar' => 'date',
        'realisasi_bayar_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}