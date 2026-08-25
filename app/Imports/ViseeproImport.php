<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\ArAgent;
use App\Models\ViseeproData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ViseeproImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $rows->shift();

        foreach ($rows as $row) {

            if (empty($row[3])) {
                continue;
            }

            $customer = Customer::updateOrCreate(
                [
                    'nomor_internet' => trim($row[3])
                ],
                [
                    'nama_pelanggan' => $row[4] ?? '-',
                    'no_hp_terbaru' => $row[33] ?? null,
                ]
            );

            $agent = null;

            if (!empty($row[10])) {

                $agent = ArAgent::firstOrCreate(
                    [
                        'name' => trim($row[10])
                    ]
                );
            }

            ViseeproData::updateOrCreate(
    [
        'activity_id' => $row[0] ?? null,
    ],
    [
        'customer_id'      => $customer->id,
        'ncli'             => $row[2] ?? null,
        'snd'              => $row[3] ?? null,
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
        }
    }
}