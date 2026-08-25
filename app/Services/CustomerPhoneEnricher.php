<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ViseeproData;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class CustomerPhoneEnricher
{
    public static function enrichPhoneNumbers(): array
    {
        $cleanedInvalid = 0;
        $enrichedFromViseepro = 0;
        $enrichedFromVisits = 0;

        $customers = Customer::all();

        foreach ($customers as $c) {
            $currentHp = $c->no_hp_terbaru;
            $validHp = CustomerSyncService::cleanPhoneNumber($currentHp);

            if ($currentHp && !$validHp) {
                // Berisi teks tidak valid (misal "Janji bayar"), reset terlebih dahulu
                $c->no_hp_terbaru = null;
                $cleanedInvalid++;
            } elseif ($validHp) {
                $c->no_hp_terbaru = $validHp;
            }

            // Jika belum ada nomor valid, coba cari dari ViseeproData
            if (empty($c->no_hp_terbaru)) {
                $vp = ViseeproData::where('customer_id', $c->id)
                    ->orWhere('snd', $c->nomor_internet)
                    ->whereNotNull('pic_cp')
                    ->first();

                if ($vp && !empty($vp->pic_cp)) {
                    $vpHp = CustomerSyncService::cleanPhoneNumber($vp->pic_cp);
                    if ($vpHp) {
                        $c->no_hp_terbaru = $vpHp;
                        $enrichedFromViseepro++;
                    }
                }
            }

            // Jika masih belum ada, coba cari dari snapshot visit yang valid
            if (empty($c->no_hp_terbaru)) {
                $visit = Visit::where('customer_id', $c->id)
                    ->whereNotNull('no_hp_snapshot')
                    ->first();

                if ($visit) {
                    $vHp = CustomerSyncService::cleanPhoneNumber($visit->no_hp_snapshot);
                    if ($vHp) {
                        $c->no_hp_terbaru = $vHp;
                        $enrichedFromVisits++;
                    }
                }
            }

            $c->save();
        }

        return [
            'cleaned_invalid'        => $cleanedInvalid,
            'enriched_from_viseepro' => $enrichedFromViseepro,
            'enriched_from_visits'   => $enrichedFromVisits,
            'valid_phone_count'      => Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count(),
            'total_customers'        => Customer::count(),
        ];
    }
}
