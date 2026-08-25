<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
    }

    /**
     * Kirim pesan teks ke chat_id tertentu via Telegram Bot API.
     *
     * @return array{success: bool, response: array|null, error: string|null}
     */
    public function sendMessage(string $chatId, string $message): array
    {
        if (empty($this->botToken)) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'TELEGRAM_BOT_TOKEN belum diatur di file .env',
            ];
        }

        if (empty($chatId)) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'AR Agent ini belum memiliki chat_id_telegram.',
            ];
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]
            );

            if ($response->successful() && ($response->json('ok') === true)) {
                return [
                    'success' => true,
                    'response' => $response->json(),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'response' => $response->json(),
                'error' => $response->json('description') ?? 'Gagal mengirim pesan ke Telegram.',
            ];
        } catch (\Throwable $e) {
            Log::error('Telegram send failed: ' . $e->getMessage());

            return [
                'success' => false,
                'response' => null,
                'error' => 'Koneksi ke Telegram gagal: ' . $e->getMessage(),
            ];
        }
    }
}