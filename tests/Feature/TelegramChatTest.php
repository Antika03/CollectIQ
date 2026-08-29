<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ArAgent;
use App\Models\Customer;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_telegram_chat_to_agent()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $agent = ArAgent::create([
            'name'             => 'AR Test Telegram',
            'chat_id_telegram' => '123456789',
            'is_active'        => true,
        ]);

        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendCustomMessage')
                ->once()
                ->andReturn(['success' => true, 'message' => 'Pesan terkirim ke Telegram']);
        });

        $response = $this->actingAs($admin)->post('/telegram/send', [
            'ar_agent_id' => $agent->id,
            'message'     => 'Halo selamat siang!',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_admin_can_send_customer_card_to_telegram()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $agent = ArAgent::create([
            'name'             => 'AR Test Budi',
            'chat_id_telegram' => '987654321',
            'is_active'        => true,
        ]);
        $customer = Customer::create([
            'nomor_internet' => '131499999999',
            'nama_pelanggan' => 'Pelanggan Test Telegram',
            'saldo_piutang'  => 500000,
        ]);

        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendCustomerBillingAlert')
                ->once()
                ->andReturn(['success' => true, 'message' => 'Notifikasi pelanggan terkirim']);
        });

        $response = $this->actingAs($admin)->post("/telegram/send-customer/{$customer->id}", [
            'ar_agent_id' => $agent->id,
            'custom_note' => 'Segera kunjungi',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_telegram_ui_elements_are_completely_removed_from_views()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $agent = ArAgent::create([
            'name'             => 'AR Test UI Check',
            'chat_id_telegram' => '999888777',
            'is_active'        => true,
        ]);
        $customer = Customer::create([
            'nomor_internet' => '131499990099',
            'nama_pelanggan' => 'Pelanggan Test UI',
            'saldo_piutang'  => 500000,
        ]);

        // 1. Settings view check
        $settingsResp = $this->actingAs($admin)->get('/settings');
        $settingsResp->assertStatus(200);
        $settingsResp->assertDontSee('Integrasi Telegram Bot (Opsional)');
        $settingsResp->assertDontSee('Aktifkan Integrasi Bot');
        $settingsResp->assertDontSee('Waktu Pagi (WIB)');
        $settingsResp->assertDontSee('Waktu Sore (WIB)');
        $settingsResp->assertDontSee('Status & Uji Bot Telegram');
        $settingsResp->assertDontSee('Test Koneksi Bot');
        $settingsResp->assertDontSee('Kirim Reminder Sekarang');
        $settingsResp->assertDontSee('Bot Aktif');

        // 2. Customer Show view check
        $custResp = $this->actingAs($admin)->get('/customers/' . $customer->id);
        $custResp->assertStatus(200);
        $custResp->assertDontSee('Kirim ke Telegram AR');
        $custResp->assertDontSee('Disposisi Pelanggan ke Telegram AR');
        $custResp->assertDontSee('modalSendTelegram');
        $custResp->assertDontSee('Kirim Notifikasi Telegram');

        // 3. AR Agents view check
        $arResp = $this->actingAs($admin)->get('/ar-agents');
        $arResp->assertStatus(200);
        $arResp->assertDontSee('modalTelegramChat');
        $arResp->assertDontSee('Kirim Pesan Telegram');
    }
}
