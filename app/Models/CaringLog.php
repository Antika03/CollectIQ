<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaringLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'ar_agent_id',
        'nomor_internet',
        'nama_pelanggan',
        'no_hp',
        'petugas_caring',
        'tanggal_caring',
        'status_caring',
        'voc',
        'keterangan',
        'frekuensi',
        'is_ptp',
        'tanggal_janji_bayar',
        'status_bayar',
        'jumlah_bayar',
        'bill_category',
        'umur_customer',
    ];

    protected $casts = [
        'tanggal_caring'      => 'date',
        'tanggal_janji_bayar' => 'date',
        'is_ptp'              => 'boolean',
        'jumlah_bayar'        => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function arAgent()
    {
        return $this->belongsTo(ArAgent::class);
    }
}
