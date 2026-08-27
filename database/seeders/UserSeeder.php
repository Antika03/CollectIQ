<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ArAgent;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Default Administrator
        User::updateOrCreate(
            ['email' => 'admin@telkom.co.id'],
            [
                'name'     => 'Administrator CollectIQ',
                'password' => Hash::make('AdminTelkom#2026'),
                'role'     => 'admin',
            ]
        );

        // 2. Akun AR Representatives (terhubung ke master AR Agent)
        $arAccounts = [
            [
                'name'     => 'Tatang',
                'email'    => 'tatang@telkom.co.id',
                'password' => Hash::make('ArTelkom#2026'),
                'ar_name'  => 'Tatang',
            ],
            [
                'name'     => 'Sayus Supriyanto',
                'email'    => 'sayus@telkom.co.id',
                'password' => Hash::make('ArTelkom#2026'),
                'ar_name'  => 'Sayus Supriyanto',
            ],
            [
                'name'     => 'Santi Surahman',
                'email'    => 'santi@telkom.co.id',
                'password' => Hash::make('ArTelkom#2026'),
                'ar_name'  => 'Santi Surahman',
            ],
            [
                'name'     => 'Wahyu Mulyadi',
                'email'    => 'wahyu@telkom.co.id',
                'password' => Hash::make('ArTelkom#2026'),
                'ar_name'  => 'Wahyu Mulyadi',
            ],
            [
                'name'     => 'Fajar Ramdhani Ishak',
                'email'    => 'fajar@telkom.co.id',
                'password' => Hash::make('ArTelkom#2026'),
                'ar_name'  => 'Fajar Ramdhani Ishak',
            ],
            [
                'name'     => 'Novi',
                'email'    => 'novi@telkom.co.id',
                'password' => Hash::make('ArTelkom#2026'),
                'ar_name'  => 'Novi',
            ],
        ];

        foreach ($arAccounts as $acc) {
            $agent = ArAgent::firstOrCreate(
                ['name' => $acc['ar_name']],
                ['is_active' => true]
            );

            User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name'        => $acc['name'],
                    'password'    => $acc['password'],
                    'role'        => 'ar',
                    'ar_agent_id' => $agent->id,
                ]
            );
        }
    }
}
