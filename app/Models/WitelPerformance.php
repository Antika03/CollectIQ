<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WitelPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'witel',
        'segmen',
        'kategori',
        'billing',
        'cash_coll',
        'cyc_percent',
        'cr_percent',
        'c3mr_percent',
        'saldo',
        'gap',
        'rank',
        'periode',
    ];

    protected $casts = [
        'billing'      => 'decimal:2',
        'cash_coll'    => 'decimal:2',
        'cyc_percent'  => 'decimal:2',
        'cr_percent'   => 'decimal:2',
        'c3mr_percent' => 'decimal:2',
        'saldo'        => 'decimal:2',
        'gap'          => 'decimal:2',
    ];
}
