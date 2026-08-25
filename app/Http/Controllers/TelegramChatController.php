<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\TelegramReminder;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramChatController extends Controller
{
    public function __construct(protected TelegramService $telegram)
    {
    }

    /**
     * Kirim pesan chat ke AR Agent terkait sebuah visit/PTP, lalu catat
     * riwayatnya di tabel telegram_reminders.
     */
    public function send(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'message' => 'required|string|max:1000',
        ]);

        $visit = Visit::with(['customer', 'arAgent'])->findOrFail($request->visit_id);

        $reminder = TelegramReminder::create([
            'ar_agent_id' => $visit->ar_agent_id,
            'customer_id' => $visit->customer_id,
            'promise_to_pay_id' => null,
            'follow_up_recommendation_id' => null,
            'type' => $visit->is_ptp ? 'ptp_due' : 'custom',
            'scheduled_at' => now(),
            'message' => $request->message,
            'status' => 'pending',
        ]);

        $result = $this->telegram->sendMessage(
            $visit->arAgent->chat_id_telegram ?? '',
            $request->message
        );

        $reminder->update([
            'status' => $result['success'] ? 'sent' : 'failed',
            'sent_at' => $result['success'] ? now() : null,
            'telegram_response' => $result['response'] ?? ['error' => $result['error']],
        ]);

        if (!$result['success']) {
            return back()->with('telegram_error', $result['error']);
        }

        return back()->with('success', 'Pesan berhasil dikirim ke ' . ($visit->arAgent->name ?? 'AR Agent') . ' via Telegram.');
    }
}