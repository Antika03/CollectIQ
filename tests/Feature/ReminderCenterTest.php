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
}
