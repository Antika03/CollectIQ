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

        // 1. Build lookup map from ViseeproData (only ~1k rows)
        $vpMap = [];
        $vps = ViseeproData::whereNotNull('pic_cp')->where('pic_cp', '!=', '')->get(['snd', 'pic_cp']);
        foreach ($vps as $vp) {
            $cleaned = CustomerSyncService::cleanPhoneNumber($vp->pic_cp);
            if ($cleaned && !empty($vp->snd)) {
                $vpMap[trim((string)$vp->snd)] = $cleaned;
            }
        }

        // 2. Build lookup map from Visits (only ~1k rows)
        $visitMap = [];
        $visits = Visit::whereNotNull('no_hp_snapshot')->where('no_hp_snapshot', '!=', '')->get(['customer_id', 'no_hp_snapshot']);
        foreach ($visits as $v) {
            $cleaned = CustomerSyncService::cleanPhoneNumber($v->no_hp_snapshot);
            if ($cleaned && !empty($v->customer_id)) {
                $visitMap[$v->customer_id] = $cleaned;
            }
        }

        // 3. Process customers in chunks of 1000
        Customer::chunkById(1000, function ($customers) use (&$cleanedInvalid, &$enrichedFromViseepro, &$enrichedFromVisits, $vpMap, $visitMap) {
            foreach ($customers as $c) {
                $currentHp = $c->no_hp_terbaru;
                $validHp = CustomerSyncService::cleanPhoneNumber($currentHp);
                $newHp = null;
                $isDirty = false;

                if ($currentHp && !$validHp) {
                    $newHp = null;
                    $cleanedInvalid++;
                    $isDirty = true;
                } elseif ($validHp && $validHp !== $currentHp) {
                    $newHp = $validHp;
                    $isDirty = true;
                } else {
                    $newHp = $currentHp;
                }

                // If empty, try ViseeproData map
                if (empty($newHp) && isset($vpMap[$c->nomor_internet])) {
                    $newHp = $vpMap[$c->nomor_internet];
                    $enrichedFromViseepro++;
                    $isDirty = true;
                }

                // If still empty, try Visit map
                if (empty($newHp) && isset($visitMap[$c->id])) {
                    $newHp = $visitMap[$c->id];
                    $enrichedFromVisits++;
                    $isDirty = true;
                }

                if ($isDirty) {
                    $c->no_hp_terbaru = $newHp;
                    $c->save();
                }
            }
        });

        return [
            'cleaned_invalid'        => $cleanedInvalid,
            'enriched_from_viseepro' => $enrichedFromViseepro,
            'enriched_from_visits'   => $enrichedFromVisits,
            'valid_phone_count'      => Customer::whereNotNull('no_hp_terbaru')->where('no_hp_terbaru', '!=', '')->count(),
            'total_customers'        => Customer::count(),
        ];
    }
}
