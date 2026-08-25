<?php

namespace App\Services;

use App\Models\ArAgent;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class ArAgentService
{
    /**
     * Peta normalisasi nama variasi ke nama kanonikal resmi
     */
    public static function getCanonicalName(string $rawName): string
    {
        $cleaned = trim($rawName);
        $upper = strtoupper($cleaned);

        // Hapus titik dan spasi berlebih
        $upper = str_replace(['.', ',', ';', '-', '_'], ' ', $upper);
        $upper = preg_replace('/\s+/', ' ', $upper);
        $upper = trim($upper);

        $map = [
            'SAYUS'                 => 'Sayus Supriyanto',
            'SAYUS S'               => 'Sayus Supriyanto',
            'SAYUSS'                => 'Sayus Supriyanto',
            'SAYUS SUPRIYANTO'      => 'Sayus Supriyanto',
            'SAYUS SUPRIYSNTO'      => 'Sayus Supriyanto',
            'SAYYS S'               => 'Sayus Supriyanto',

            'SANTI'                 => 'Santi Surahman',
            'SANTI SURAHMAN'        => 'Santi Surahman',

            'WAHYU'                 => 'Wahyu Mulyadi',
            'WAHYU MULYADI'         => 'Wahyu Mulyadi',
            'WAHYU IHWAYUDA'        => 'Wahyu Ihwayuda',

            'YAYAT'                 => 'Yayat Ruhiyat',
            'YAYAT RUHIYAT'         => 'Yayat Ruhiyat',

            'FAJAR'                 => 'Fajar Ramdhani Ishak',
            'FAJAR RAMDHANI'        => 'Fajar Ramdhani Ishak',
            'FAJAR RAMDHANI ISHAK'  => 'Fajar Ramdhani Ishak',

            'RAFLI'                 => 'Rafli Zulfikar',
            'RAFLI ZULFIKAR'        => 'Rafli Zulfikar',

            'BAMBANG'               => 'Bambang',

            'TATANG'                => 'Tatang',

            'IDA'                   => 'Ida Herlina',
            'IDA HERLINA'           => 'Ida Herlina',

            'YANA'                  => 'Yana Suryana',
            'YANA SURYANA'          => 'Yana Suryana',
            'YANA YANASURYANA'      => 'Yana Suryana',

            'VINA'                  => 'Vina Aprilia',
            'VINA APRILIA'          => 'Vina Aprilia',
            'VINA APRIL'            => 'Vina Aprilia',
            'FINA'                  => 'Vina Aprilia',

            'AHMAD'                 => 'Ahmad Ali Subarkah',
            'AHMAD ALI SUBARKAH'    => 'Ahmad Ali Subarkah',

            'AGNES PRAWESTI'        => 'Agnes Prawesti Puspa Lestari',
            'AGNES PRAWESTI PUSPA LESTARI' => 'Agnes Prawesti Puspa Lestari',

            'MERIN MERIANI'         => 'Merin Meriani',

            'NOVI'                  => 'Novi',

            'NINA ROSANA'           => 'Nina Rosana',
            'NURHAYATI'             => 'Nurhayati',
            'SHOKIKAH'              => 'Shokikah',
            'SONI YUNIAR'           => 'Soni Yuniar',
            'WISNU'                 => 'Wisnu',
            'DEA RAUDHAH JANNAH SUDRAJAT' => 'Dea Raudhah Jannah Sudrajat',
            'DINDA NINENGAH SANDRA' => 'Dinda Ninengah Sandra',
            'MOHAMAD ALLAN SADAT'   => 'Mohamad Allan Sadat',
            'UNKNOWN'               => 'Unknown / Tim Pusat',
        ];

        return $map[$upper] ?? ucwords(strtolower($cleaned));
    }

    /**
     * Konsolidasi record AR Agent duplikat di database secara aman:
     * - Mengelompokkan berdasarkan nama kanonikal
     * - Memilih 1 master record untuk setiap nama kanonikal
     * - Mengalihkan FK visits ke master record
     * - Menghapus record duplikat yang sudah dialihkan
     */
    public static function consolidateAgents(): array
    {
        return DB::transaction(function () {
            $agents = ArAgent::withCount('visits')->get();
            $grouped = [];

            foreach ($agents as $agent) {
                $canonicalName = self::getCanonicalName($agent->name);
                $grouped[$canonicalName][] = $agent;
            }

            $mergedCount = 0;
            $reassignedVisits = 0;
            $finalCount = 0;

            foreach ($grouped as $canonicalName => $agentList) {
                // Urutkan: yang punya visits terbanyak di posisi pertama
                usort($agentList, fn($a, $b) => $b->visits_count <=> $a->visits_count);

                $master = $agentList[0];
                $duplicates = array_slice($agentList, 1);

                foreach ($duplicates as $dup) {
                    $affected = Visit::where('ar_agent_id', $dup->id)
                        ->update(['ar_agent_id' => $master->id]);

                    $reassignedVisits += $affected;

                    if (!empty($dup->chat_id_telegram) && empty($master->chat_id_telegram)) {
                        $master->chat_id_telegram = $dup->chat_id_telegram;
                    }

                    // Hapus duplikat
                    $dup->delete();
                    $mergedCount++;
                }

                // Update master name ke canonical name
                $master->name = $canonicalName;
                $master->is_active = true;
                $master->save();
                $finalCount++;
            }

            return [
                'final_agent_count' => $finalCount,
                'merged_count'      => $mergedCount,
                'reassigned_visits' => $reassignedVisits,
            ];
        });
    }
}
