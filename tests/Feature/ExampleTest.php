<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\ArAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('CollectIQ Telkom');
    }

    public function test_admin_can_access_dashboard_and_settings(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_test@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/');
        $response->assertStatus(200);

        $settingsResp = $this->actingAs($admin)->get('/settings');
        $settingsResp->assertStatus(200);

        $syncResp = $this->actingAs($admin)->get('/c3mr/sync');
        $syncResp->assertStatus(200);
    }

    public function test_ar_user_access_and_authorization(): void
    {
        $agent = ArAgent::create([
            'name'      => 'Tatang Test',
            'is_active' => true,
        ]);

        $arUser = User::create([
            'name'        => 'Tatang Test',
            'email'       => 'ar_test@telkom.co.id',
            'password'    => Hash::make('password123'),
            'role'        => 'ar',
            'ar_agent_id' => $agent->id,
        ]);

        // AR accessing / is redirected to AR dashboard
        $response = $this->actingAs($arUser)->get('/');
        $response->assertRedirect('/ar/dashboard');

        $arDashResp = $this->actingAs($arUser)->get('/ar/dashboard');
        $arDashResp->assertStatus(200);

        // AR accessing /settings should be Forbidden (403)
        $settingsResp = $this->actingAs($arUser)->get('/settings');
        $settingsResp->assertStatus(403);

        // AR accessing /c3mr/sync should be Forbidden (403)
        $syncResp = $this->actingAs($arUser)->get('/c3mr/sync');
        $syncResp->assertStatus(403);
    }

    public function test_customer_list_and_pranpc_detection(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin2@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $cust = Customer::create([
            'nomor_internet' => '131427100001',
            'nama_pelanggan' => 'Test PRANPC Customer',
            'is_pranpc'      => true,
            'bill_category'  => 'PRANPC',
        ]);

        $response = $this->actingAs($admin)->get('/customers');
        $response->assertStatus(200);
        $response->assertSee('Daftar Master Pelanggan');

        $pranpcResponse = $this->actingAs($admin)->get('/customers?is_pranpc=1');
        $pranpcResponse->assertStatus(200);
        $pranpcResponse->assertSee('PRANPC');
    }

    public function test_customer_360_page(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin3@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $customer = Customer::create([
            'nomor_internet' => '131427100002',
            'nama_pelanggan' => 'Test 360 Customer',
        ]);

        $response = $this->actingAs($admin)->get('/customers/' . $customer->id);
        $response->assertStatus(200);
        $response->assertSee($customer->nomor_internet);
        $response->assertSee('Customer 360');
    }
}
