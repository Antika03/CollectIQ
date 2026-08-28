<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Visit;
use App\Models\ArAgent;
use App\Models\CaringLog;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if (strlen($q) < 2) {
            return redirect()->back()->with('error', 'Masukkan minimal 2 karakter untuk pencarian.');
        }

        // Cek apakah match persis nomor internet / NCLI → langsung redirect ke Customer 360
        $directCustomer = Customer::where('nomor_internet', $q)
            ->orWhere('ncli', $q)
            ->first();

        if ($directCustomer) {
            return redirect('/customers/' . $directCustomer->id);
        }

        // ---- FULL SEARCH MODE ----
        $customers = Customer::where(function ($qb) use ($q) {
                $qb->where('nama_pelanggan', 'like', "%{$q}%")
                   ->orWhere('nomor_internet', 'like', "%{$q}%")
                   ->orWhere('no_hp_terbaru', 'like', "%{$q}%")
                   ->orWhere('datel', 'like', "%{$q}%")
                   ->orWhere('sto', 'like', "%{$q}%")
                   ->orWhere('ncli', 'like', "%{$q}%");
            })
            ->orderBy('nama_pelanggan')
            ->take(20)
            ->get();

        $visits = Visit::with(['customer', 'arAgent'])
            ->where(function ($qb) use ($q) {
                $qb->whereHas('customer', function ($cq) use ($q) {
                    $cq->where('nama_pelanggan', 'like', "%{$q}%")
                       ->orWhere('nomor_internet', 'like', "%{$q}%");
                })
                ->orWhere('hasil_visit', 'like', "%{$q}%")
                ->orWhere('kategori_visit', 'like', "%{$q}%")
                ->orWhere('keterangan_visit', 'like', "%{$q}%");
            })
            ->orderByDesc('tanggal_input')
            ->take(10)
            ->get();

        $caringLogs = CaringLog::where(function ($qb) use ($q) {
                $qb->where('nama_pelanggan', 'like', "%{$q}%")
                   ->orWhere('nomor_internet', 'like', "%{$q}%")
                   ->orWhere('no_hp', 'like', "%{$q}%")
                   ->orWhere('voc', 'like', "%{$q}%")
                   ->orWhere('keterangan', 'like', "%{$q}%");
            })
            ->orderByDesc('tanggal_caring')
            ->take(10)
            ->get();

        $arAgents = ArAgent::where('name', 'like', "%{$q}%")
            ->withCount('visits')
            ->take(5)
            ->get();

        $totalResults = $customers->count() + $visits->count() + $caringLogs->count() + $arAgents->count();

        return view('search.results', compact(
            'q',
            'customers',
            'visits',
            'caringLogs',
            'arAgents',
            'totalResults'
        ));
    }
}
