<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\ArAgent;
use App\Models\ViseeproData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ViseeproImport implements ToCollection
{
    public int $processedCount = 0;
    public int $createdCount   = 0;
    public int $updatedCount   = 0;

    public function collection(Collection $rows)
    {
        $rows->shift();

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                if (empty($row[3])) {
                    continue;
                }

                $snd = trim((string)$row[3]);
                if (empty($snd) || !preg_match('/^\d{6,20}$/', $snd)) {
                    continue;
                }

                $customer = Customer::updateOrCreate(
                    [
                        'nomor_internet' => $snd
                    ],
                    [
                        'nama_pelanggan' => $row[4] ?? '-',
                        'no_hp_terbaru'  => $row[33] ?? null,
                    ]
                );

                $agent = null;
                if (!empty($row[10])) {
                    $agent = ArAgent::firstOrCreate(
                        [
                            'name' => trim((string)$row[10])
                        ]
                    );
                }

                $activityId = !empty($row[0]) ? trim((string)$row[0]) : null;
                if ($activityId) {
                    $vis = ViseeproData::updateOrCreate(
                        [
                            'activity_id' => $activityId,
                        ],
                        [
                            'customer_id'      => $customer->id,
                            'ncli'             => $row[2] ?? null,
                            'snd'              => $snd,
                            'nama_perusahaan'  => $row[4] ?? null,
                            'regional'         => $row[5] ?? null,
                            'witel'            => $row[6] ?? null,
                            'sto'              => $row[7] ?? null,
                            'nama_agent'       => $row[10] ?? null,
                            'activity_status'  => $row[28] ?? null,
                            'activity_reason'  => $row[29] ?? null,
                            'pic_name'         => $row[30] ?? null,
                            'pic_role'         => $row[31] ?? null,
                            'pic_cp'           => $row[33] ?? null,
                            'address'          => $row[35] ?? null,
                            'latitude'         => $row[40] ?? null,
                            'longitude'        => $row[41] ?? null,
                            'input_date'       => null,
                        ]
                    );

                    $this->processedCount++;
                    if ($vis->wasRecentlyCreated) {
                        $this->createdCount++;
                    } else {
                        $this->updatedCount++;
                    }
                }
            }
        });
    }
}