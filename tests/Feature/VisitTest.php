<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\ArAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class VisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_visits_index(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_visit@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $customer = Customer::create([
            'nomor_internet' => '131427101111',
            'nama_pelanggan' => 'Toko Elektronik Makmur',
            'saldo_piutang'  => 500000,
        ]);

        $agent = ArAgent::create([
            'name'      => 'AR Budi Santoso',
            'is_active' => true,
        ]);

        $visit = Visit::create([
            'collect_id'     => 'COL-TEST-999',
            'customer_id'    => $customer->id,
            'ar_agent_id'    => $agent->id,
            'tanggal_input'  => now()->toDateString(),
            'hasil_visit'    => 'Contacted',
            'kategori_visit' => 'Janji Bayar',
            'is_ptp'         => true,
        ]);

        $response = $this->actingAs($admin)->get('/visits');
        $response->assertStatus(200);
        $response->assertSee('Visit Monitoring');
        $response->assertSee('Toko Elektronik Makmur');
        $response->assertSee('AR Budi Santoso');
    }

    public function test_visit_search_filter_by_customer_name(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_visit2@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $customer1 = Customer::create([
            'nomor_internet' => '131427102222',
            'nama_pelanggan' => 'Rumah Sakit Sehat Sejahtera',
        ]);

        $customer2 = Customer::create([
            'nomor_internet' => '131427103333',
            'nama_pelanggan' => 'Kantin Kampus UNPAD',
        ]);

        Visit::create([
            'collect_id'    => 'COL-V-01',
            'customer_id'   => $customer1->id,
            'tanggal_input' => now()->toDateString(),
            'hasil_visit'   => 'Contacted',
        ]);

        Visit::create([
            'collect_id'    => 'COL-V-02',
            'customer_id'   => $customer2->id,
            'tanggal_input' => now()->toDateString(),
            'hasil_visit'   => 'Uncontacted',
        ]);

        $response = $this->actingAs($admin)->get('/visits?search=Rumah+Sakit');
        $response->assertStatus(200);
        $response->assertSee('Rumah Sakit Sehat Sejahtera');
        $response->assertDontSee('Kantin Kampus UNPAD');
    }

    public function test_visit_show_details(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_visit3@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $customer = Customer::create([
            'nomor_internet' => '131427104444',
            'nama_pelanggan' => 'Hotel Grand Priangan',
        ]);

        $visit = Visit::create([
            'collect_id'       => 'COL-V-03',
            'customer_id'      => $customer->id,
            'tanggal_input'    => now()->toDateString(),
            'hasil_visit'      => 'Janji Bayar',
            'keterangan_visit' => 'Pelanggan berjanji bayar akhir bulan',
        ]);

        $response = $this->actingAs($admin)->get('/visits/' . $visit->id);
        $response->assertStatus(200);
        $response->assertSee('Hotel Grand Priangan');
        $response->assertSee('Pelanggan berjanji bayar akhir bulan');
    }
}
