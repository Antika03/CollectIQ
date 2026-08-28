<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\ArAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ReminderCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_reminder_center_index(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_rem@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $customer = Customer::create([
            'nomor_internet' => '131429114242',
            'nama_pelanggan' => 'Warung Indra Dikar',
            'saldo_piutang'  => 850000,
        ]);

        $response = $this->actingAs($admin)->get('/reminders');
        $response->assertStatus(200);
        $response->assertSee('Reminder Center');
        $response->assertSee('Warung Indra Dikar');
        $response->assertSee('masked-snd-wrapper');
    }

    public function test_reminder_preview_endpoint_returns_valid_message(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_rem2@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $agent = ArAgent::create([
            'name'             => 'Sayus Supriyanto',
            'chat_id_telegram' => '12345678',
            'is_active'        => true,
        ]);

        $customer = Customer::create([
            'nomor_internet'       => '131429114242',
            'nama_pelanggan'       => 'Warung Indra Dikar',
            'saldo_piutang'        => 850000,
            'assigned_ar_agent_id' => $agent->id,
        ]);

        Visit::create([
            'collect_id'       => 'COLLECT-2548',
            'customer_id'      => $customer->id,
            'ar_agent_id'      => $agent->id,
            'tanggal_input'    => '2026-08-18',
            'hasil_visit'      => 'Contacted',
            'kategori_visit'   => 'Janji Bayar',
            'keterangan_visit' => 'Janji bayar di bulan ini.',
            'is_ptp'           => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/reminders/preview', [
            'customer_id' => $customer->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'customer' => [
                'nama_pelanggan' => 'Warung Indra Dikar',
                'nomor_internet' => '131429114242',
            ],
        ]);

        $message = $response->json('message');
        $this->assertStringContainsString('REMINDER COLLECTION', $message);
        $this->assertStringContainsString('Warung Indra Dikar', $message);
        $this->assertStringContainsString('131429114242', $message);
    }

    public function test_reminder_export_csv(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin_rem3@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $customer = Customer::create([
            'nomor_internet' => '131428127845',
            'nama_pelanggan' => 'Bengkel Motor Cibitung',
            'saldo_piutang'  => 450000,
        ]);

        $response = $this->actingAs($admin)->get('/reminders/export/csv');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_visits_index_returns_200_without_cache_deserialization_error(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin Visits',
            'email'    => 'admin_visits@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $agent = ArAgent::create([
            'name'             => 'Yayat Ruhiyat',
            'chat_id_telegram' => '-1002503356198',
            'is_active'        => true,
        ]);

        $customer = Customer::create([
            'nomor_internet' => '131428127845',
            'nama_pelanggan' => 'Bengkel Motor Cibitung',
            'saldo_piutang'  => 450000,
        ]);

        Visit::create([
            'collect_id'       => 'COLLECT-2548',
            'customer_id'      => $customer->id,
            'ar_agent_id'      => $agent->id,
            'tanggal_input'    => '2026-08-23',
            'hasil_visit'      => 'Janji bayar',
            'kategori_visit'   => 'Contacted',
            'keterangan_visit' => 'Janji bayar mau di sampaikan ke bosnya.',
            'is_ptp'           => true,
        ]);

        $response = $this->actingAs($admin)->get('/visits');
        $response->assertStatus(200);
        $response->assertSee('Visit Monitoring');
        $response->assertSee('Bengkel Motor Cibitung');
    }

    public function test_customers_index_and_show_return_200(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin Cust',
            'email'    => 'admin_cust@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $agent = ArAgent::create([
            'name'             => 'Sayus Supriyanto',
            'chat_id_telegram' => '12345678',
            'is_active'        => true,
        ]);

        $customer = Customer::create([
            'nomor_internet'       => '131429114242',
            'nama_pelanggan'       => 'Warung Indra Dikar',
            'saldo_piutang'        => 850000,
            'assigned_ar_agent_id' => $agent->id,
        ]);

        $indexResponse = $this->actingAs($admin)->get('/customers');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Warung Indra Dikar');

        $showResponse = $this->actingAs($admin)->get("/customers/{$customer->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Warung Indra Dikar');
    }

    public function test_profile_route_returns_200(): void
    {
        $admin = User::create([
            'name'     => 'Test Admin Profile',
            'email'    => 'admin_prof@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Profil');
    }
}
