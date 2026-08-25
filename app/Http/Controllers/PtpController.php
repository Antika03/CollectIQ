<?php

namespace App\Http\Controllers;

use App\Models\Visit;

class PtpController extends Controller
{
    public function index()
    {
        $ptps = Visit::with([
                'customer',
                'arAgent'
            ])
            ->where('is_ptp', true)
            ->latest('tanggal_input')
            ->paginate(20);

        return view(
            'ptp.index',
            compact('ptps')
        );
    }
}