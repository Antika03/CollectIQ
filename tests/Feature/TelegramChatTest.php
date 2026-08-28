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

    public function test_automatic_telegram_reminders_command_is_disabled()
    {
        $this->artisan('collectiq:send-telegram-reminders')
            ->expectsOutput('Automatic daily Telegram reminder telah dinonaktifkan sesuai requirement sistem.')
            ->assertExitCode(0);
    }

    public function test_send_daily_reminders_service_is_deactivated()
    {
        $service = new TelegramService();
        $result = $service->sendDailyReminders();

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['sent']);
        $this->assertEquals(0, $result['failed']);
        $this->assertStringContainsString('dinonaktifkan', $result['message']);
    }
}
