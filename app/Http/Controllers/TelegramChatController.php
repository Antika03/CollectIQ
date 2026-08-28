<?php

namespace App\Http\Controllers;

use App\Models\ArAgent;
use App\Models\Customer;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramChatController extends Controller
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Kirim pesan teks / chat langsung ke Telegram AR Agent
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'ar_agent_id' => 'required|exists:ar_agents,id',
            'message'     => 'required|string|min:2|max:3000',
        ]);

        $res = $this->telegram->sendCustomMessage(
            (int)$request->ar_agent_id,
            $request->message
        );

        if ($request->wantsJson()) {
            return response()->json($res, $res['success'] ? 200 : 422);
        }

        if ($res['success']) {
            return back()->with('success', $res['message']);
        }

        return back()->with('error', $res['message']);
    }

    /**
     * Kirim detail profil / saldo pelanggan ke Telegram AR Agent
     */
    public function sendCustomerCard(Request $request, Customer $customer)
    {
        $request->validate([
            'ar_agent_id' => 'required|exists:ar_agents,id',
            'custom_note' => 'nullable|string|max:500',
        ]);

        $res = $this->telegram->sendCustomerBillingAlert(
            (int)$request->ar_agent_id,
            $customer->id,
            $request->custom_note
        );

        if ($request->wantsJson()) {
            return response()->json($res, $res['success'] ? 200 : 422);
        }

        if ($res['success']) {
            return back()->with('success', $res['message']);
        }

        return back()->with('error', $res['message']);
    }

    /**
     * Kirim broadcast pesan ke semua AR Agent yang memiliki Chat ID
     */
    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:2|max:3000',
        ]);

        $agents = ArAgent::where('is_active', true)
            ->whereNotNull('chat_id_telegram')
            ->where('chat_id_telegram', '!=', '')
            ->get();

        if ($agents->isEmpty()) {
            $msg = 'Tidak ada AR Agent dengan Chat ID Telegram yang aktif.';
            return $request->wantsJson() 
                ? response()->json(['success' => false, 'message' => $msg], 422) 
                : back()->with('error', $msg);
        }

        $sent = 0;
        $failed = 0;

        foreach ($agents as $agent) {
            $res = $this->telegram->sendCustomMessage($agent->id, $request->message);
            if ($res['success']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $msg = "Broadcast selesai: {$sent} pesan terkirim, {$failed} gagal.";
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'sent' => $sent, 'failed' => $failed]);
        }

        return back()->with('success', $msg);
    }
}