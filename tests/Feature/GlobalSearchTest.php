<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\ArAgent;
use App\Models\CaringLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_by_customer_name(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_search@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $customer = Customer::create([
            'nomor_internet' => '131427109999',
            'nama_pelanggan' => 'PT. MAKMUR SEJAHTERA TELKOM',
            'saldo_piutang'  => 1500000,
        ]);

        $agent = ArAgent::create([
            'name'      => 'AR Budi',
            'is_active' => true,
        ]);

        Visit::create([
            'collect_id'     => 'COL-TEST-001',
            'customer_id'    => $customer->id,
            'ar_agent_id'    => $agent->id,
            'tanggal_input'  => now()->toDateString(),
            'hasil_visit'    => 'Contacted',
            'kategori_visit' => 'Janji Bayar',
            'is_ptp'         => true,
        ]);

        CaringLog::create([
            'nomor_internet'  => $customer->nomor_internet,
            'nama_pelanggan'  => $customer->nama_pelanggan,
            'tanggal_caring'  => now()->toDateString(),
            'status_caring'   => 'CONTACTED',
            'voc'             => 'Akan bayar segera',
        ]);

        // Search with partial name
        $response = $this->actingAs($admin)->get('/search?q=MAKMUR');
        $response->assertStatus(200);
        $response->assertSee('Hasil Pencarian');
        $response->assertSee('PT. MAKMUR SEJAHTERA TELKOM');
    }

    public function test_global_search_exact_internet_redirects_to_customer(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_search2@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $customer = Customer::create([
            'nomor_internet' => '131427888888',
            'nama_pelanggan' => 'SMK TELKOM 1',
        ]);

        $response = $this->actingAs($admin)->get('/search?q=131427888888');
        $response->assertRedirect('/customers/' . $customer->id);
    }
}
