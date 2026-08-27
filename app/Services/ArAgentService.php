<?php

namespace App\Services;

use App\Models\ArAgent;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\CaringLog;
use App\Models\TelegramReminder;
use App\Models\FollowUpRecommendation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArAgentService
{
    /**
     * Dapatkan nama kanonikal resmi untuk nama AR
     */
    public static function getCanonicalName(?string $rawName): ?string
    {
        return DataNormalizerService::normalizeArName($rawName);
    }

    /**
     * Dapatkan atau buat AR Agent berdasarkan nama mentah secara idempotent
     */
    public static function findOrCreateAgent(?string $rawName, ?string $chatId = null): ?ArAgent
    {
        $canonical = self::getCanonicalName($rawName);
        if (!$canonical) {
            return null;
        }

        $agent = ArAgent::where('name', $canonical)->first();

        if (!$agent) {
            $agent = ArAgent::create([
                'name'              => $canonical,
                'chat_id_telegram'  => $chatId,
                'is_active'         => true,
            ]);
        } elseif (!empty($chatId) && empty($agent->chat_id_telegram)) {
            $agent->chat_id_telegram = $chatId;
            $agent->save();
        }

        return $agent;
    }

    /**
     * Konsolidasi menyeluruh seluruh record AR Agent di database:
     * 1. Hapus / bersihkan record yang bukan personil AR (channel bank, loket, payment gateway)
     * 2. Kelompokkan variasi nama AR ke satu record master kanonikal
     * 3. Alihkan seluruh relasi (visits, customers, caring_logs, telegram_reminders, users) ke master
     * 4. Hapus record duplikat
     */
    public static function consolidateAgents(): array
    {
        return DB::transaction(function () {
            $allAgents = ArAgent::all();
            $mergedCount = 0;
            $deletedNonArCount = 0;
            $reassignedVisits = 0;
            $reassignedCustomers = 0;
            $reassignedCaring = 0;

            // 1. Bersihkan entri non-AR (Bank, Loket, Merchant, dll)
            foreach ($allAgents as $agent) {
                if (DataNormalizerService::isNonArChannel($agent->name)) {
                    // Lepaskan relasi atau alihkan ke null
                    Customer::where('assigned_ar_agent_id', $agent->id)->update(['assigned_ar_agent_id' => null]);
                    Visit::where('ar_agent_id', $agent->id)->update(['ar_agent_id' => null]);
                    CaringLog::where('ar_agent_id', $agent->id)->update(['ar_agent_id' => null]);
                    TelegramReminder::where('ar_agent_id', $agent->id)->delete();
                    FollowUpRecommendation::where('ar_agent_id', $agent->id)->delete();
                    User::where('ar_agent_id', $agent->id)->update(['ar_agent_id' => null]);

                    $agent->delete();
                    $deletedNonArCount++;
                }
            }

            // 2. Refresh sisa agen yang valid
            $validAgents = ArAgent::withCount('visits')->get();
            $grouped = [];

            foreach ($validAgents as $agent) {
                $canonicalName = self::getCanonicalName($agent->name);
                if (!$canonicalName) {
                    $canonicalName = ucwords(strtolower(trim($agent->name)));
                }
                $grouped[$canonicalName][] = $agent;
            }

            $finalCount = 0;

            foreach ($grouped as $canonicalName => $agentList) {
                // Urutkan: yang punya visits terbanyak dan punya chat_id di posisi pertama
                usort($agentList, function ($a, $b) {
                    if ($a->visits_count !== $b->visits_count) {
                        return $b->visits_count <=> $a->visits_count;
                    }
                    return !empty($b->chat_id_telegram) <=> !empty($a->chat_id_telegram);
                });

                $master = $agentList[0];
                $duplicates = array_slice($agentList, 1);

                foreach ($duplicates as $dup) {
                    $reassignedVisits += Visit::where('ar_agent_id', $dup->id)->update(['ar_agent_id' => $master->id]);
                    $reassignedCustomers += Customer::where('assigned_ar_agent_id', $dup->id)->update(['assigned_ar_agent_id' => $master->id]);
                    $reassignedCaring += CaringLog::where('ar_agent_id', $dup->id)->update(['ar_agent_id' => $master->id]);
                    TelegramReminder::where('ar_agent_id', $dup->id)->update(['ar_agent_id' => $master->id]);
                    FollowUpRecommendation::where('ar_agent_id', $dup->id)->update(['ar_agent_id' => $master->id]);
                    User::where('ar_agent_id', $dup->id)->update(['ar_agent_id' => $master->id]);

                    if (!empty($dup->chat_id_telegram) && empty($master->chat_id_telegram)) {
                        $master->chat_id_telegram = $dup->chat_id_telegram;
                    }

                    $dup->delete();
                    $mergedCount++;
                }

                $master->name = $canonicalName;
                $master->is_active = true;
                $master->save();
                $finalCount++;
            }

            Log::info("[ArAgentService] Konsolidasi AR selesai: {$finalCount} master AR unik (Merged: {$mergedCount}, Deleted Non-AR: {$deletedNonArCount})");

            return [
                'final_agent_count'    => $finalCount,
                'merged_count'         => $mergedCount,
                'deleted_non_ar_count' => $deletedNonArCount,
                'reassigned_visits'    => $reassignedVisits,
                'reassigned_customers' => $reassignedCustomers,
                'reassigned_caring'    => $reassignedCaring,
            ];
        });
    }
}
