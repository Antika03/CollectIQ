<?php

namespace App\Services;

use App\Models\ArAgent;
use App\Models\Customer;
use App\Models\Visit;
use App\Models\TelegramReminder;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TelegramService
{
    protected ?string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
    }

    /**
     * Uji koneksi bot Telegram (getMe)
     */
    public function testConnection(): array
    {
        if (empty($this->botToken)) {
            return [
                'success' => false,
                'message' => 'TELEGRAM_BOT_TOKEN belum diatur di file .env',
            ];
        }

        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$this->botToken}/getMe");
            if ($response->successful() && $response->json('ok') === true) {
                $bot = $response->json('result');
                return [
                    'success' => true,
                    'bot'     => $bot,
                    'message' => "Bot Telegram terhubung: @{$bot['username']} ({$bot['first_name']})",
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal terhubung ke bot Telegram: ' . ($response->json('description') ?? 'Status HTTP ' . $response->status()),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Koneksi ke Telegram timeout / gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim pesan teks ke chat_id tertentu via Telegram Bot API
     */
    public function sendMessage(string $chatId, string $message): array
    {
        if (empty($this->botToken)) {
            return [
                'success'  => false,
                'response' => null,
                'error'    => 'TELEGRAM_BOT_TOKEN belum diatur di file .env',
            ];
        }

        if (empty($chatId)) {
            return [
                'success'  => false,
                'response' => null,
                'error'    => 'Chat ID Telegram belum diatur.',
            ];
        }

        try {
            $response = Http::timeout(15)->post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'chat_id'                  => $chatId,
                    'text'                     => $message,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => true,
                ]
            );

            if ($response->successful() && ($response->json('ok') === true)) {
                return [
                    'success'  => true,
                    'response' => $response->json(),
                    'error'    => null,
                ];
            }

            return [
                'success'  => false,
                'response' => $response->json(),
                'error'    => $response->json('description') ?? 'Gagal mengirim pesan ke Telegram.',
            ];
        } catch (\Throwable $e) {
            Log::error('[TelegramService] Send error: ' . $e->getMessage());

            return [
                'success'  => false,
                'response' => null,
                'error'    => 'Koneksi ke Telegram gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Bangun pesan ringkasan harian untuk satu AR Agent
     */
    public static function buildArSummaryMessage(ArAgent $agent): ?string
    {
        // 1. Pelanggan tanggung jawab AR yang belum pernah divisit
        $unvisited = Customer::where('assigned_ar_agent_id', $agent->id)
            ->whereNull('last_visit_at')
            ->where('saldo_piutang', '>', 0)
            ->orderByDesc('saldo_piutang')
            ->take(5)
            ->get();

        // 2. Pelanggan yang perlu visit ulang (visit terakhir > 14 hari yang lalu dan masih ada piutang)
        $revisit = Customer::where('assigned_ar_agent_id', $agent->id)
            ->whereNotNull('last_visit_at')
            ->where('last_visit_at', '<=', now()->subDays(14))
            ->where('saldo_piutang', '>', 0)
            ->orderByDesc('saldo_piutang')
            ->take(5)
            ->get();

        // 3. PTP yang aktif / mendekati jatuh tempo
        $ptpVisits = Visit::where('ar_agent_id', $agent->id)
            ->where('is_ptp', true)
            ->latest('tanggal_input')
            ->take(5)
            ->get();

        // Jika tidak ada data yang perlu ditindaklanjuti, return null
        if ($unvisited->isEmpty() && $revisit->isEmpty() && $ptpVisits->isEmpty()) {
            return null;
        }

        $namaAr = strtoupper($agent->name);
        $tglStr = now()->translatedFormat('l, d F Y');

        $msg = "🔔 <b>REMINDER COLLECTIQ TELKOM</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Halo Kak <b>{$namaAr}</b>,\n";
        $msg .= "Berikut ringkasan pelanggan yang perlu ditindaklanjuti per <b>{$tglStr}</b>:\n\n";

        if ($unvisited->isNotEmpty()) {
            $msg .= "📋 <b>BELUM VISIT (Prioritas Saldo):</b>\n";
            foreach ($unvisited as $idx => $c) {
                $num = $idx + 1;
                $saldo = number_format($c->saldo_piutang, 0, ',', '.');
                $pranpcBadge = $c->is_pranpc ? ' [PRANPC]' : '';
                $msg .= "{$num}. <b>{$c->nama_pelanggan}</b>{$pranpcBadge}\n";
                $msg .= "   • No. Internet: <code>{$c->nomor_internet}</code>\n";
                $msg .= "   • Saldo: Rp {$saldo}\n";
                $msg .= "   • Alamat: " . ($c->alamat ? substr($c->alamat, 0, 45) . '...' : ($c->datel ?: '-')) . "\n";
                if ($c->formatted_wa_number) {
                    $msg .= "   • WhatsApp: +{$c->formatted_wa_number}\n";
                }
            }
            $msg .= "\n";
        }

        if ($revisit->isNotEmpty()) {
            $msg .= "🔄 <b>PERLU VISIT ULANG (>14 hari):</b>\n";
            foreach ($revisit as $idx => $c) {
                $num = $idx + 1;
                $saldo = number_format($c->saldo_piutang, 0, ',', '.');
                $lastVisit = $c->last_visit_at ? $c->last_visit_at->format('d/m/Y') : '-';
                $msg .= "{$num}. <b>{$c->nama_pelanggan}</b>\n";
                $msg .= "   • No. Internet: <code>{$c->nomor_internet}</code>\n";
                $msg .= "   • Last Visit: {$lastVisit} | Saldo: Rp {$saldo}\n";
            }
            $msg .= "\n";
        }

        if ($ptpVisits->isNotEmpty()) {
            $msg .= "🤝 <b>MONITORING JANJI BAYAR (PTP):</b>\n";
            foreach ($ptpVisits as $idx => $v) {
                $num = $idx + 1;
                $custName = $v->customer ? $v->customer->nama_pelanggan : 'Pelanggan';
                $tgl = $v->tanggal_input ? $v->tanggal_input->format('d/m/Y') : '-';
                $msg .= "{$num}. <b>{$custName}</b> ({$tgl})\n";
                $msg .= "   • Hasil: {$v->hasil_visit}\n";
                $msg .= "   • Ket: " . substr($v->keterangan_visit, 0, 40) . "\n";
            }
            $msg .= "\n";
        }

        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "💡 <i>Silakan buka aplikasi CollectIQ untuk melihat detail & update penagihan. Semangat & salam sukses!</i>";

        return $msg;
    }

    /**
     * Automatic daily Telegram reminder telah dinonaktifkan.
     * Mengembalikan status aman tanpa melakukan pengiriman HTTP ke Telegram.
     */
    public function sendDailyReminders(): array
    {
        return [
            'success'       => true,
            'total_agents'  => 0,
            'sent'          => 0,
            'failed'        => 0,
            'skipped'       => 0,
            'message'       => 'Automatic Telegram reminder dinonaktifkan. Reminder pelanggan dapat dikelola melalui Reminder Center.',
        ];
    }

    /**
     * Kirim pesan kustom / interaktif langsung ke AR Agent via Telegram
     */
    public function sendCustomMessage(int $arAgentId, string $message): array
    {
        $agent = ArAgent::find($arAgentId);
        if (!$agent) {
            return ['success' => false, 'message' => 'AR Agent tidak ditemukan.'];
        }

        if (empty($agent->chat_id_telegram)) {
            return ['success' => false, 'message' => "AR Agent {$agent->name} belum memiliki Chat ID Telegram. Silakan lengkapi Chat ID terlebih dahulu."];
        }

        // Catat ke log reminders
        $reminder = TelegramReminder::create([
            'ar_agent_id'  => $agent->id,
            'type'         => 'custom_chat',
            'scheduled_at' => now(),
            'status'       => 'pending',
            'message'      => $message,
        ]);

        $res = $this->sendMessage($agent->chat_id_telegram, $message);

        if ($res['success']) {
            $reminder->update([
                'status'            => 'sent',
                'sent_at'           => now(),
                'telegram_response' => $res['response'],
            ]);
            return [
                'success' => true,
                'message' => "Pesan berhasil terkirim ke Telegram AR {$agent->name}!",
            ];
        } else {
            $reminder->update([
                'status'            => 'failed',
                'telegram_response' => ['error' => $res['error']],
            ]);
            return [
                'success' => false,
                'message' => "Gagal mengirim ke Telegram: " . ($res['error'] ?? 'Terjadi kesalahan.'),
            ];
        }
    }

    /**
     * Kirim Kartu Profil / Tagihan Pelanggan langsung ke Telegram AR
     */
    public function sendCustomerBillingAlert(int $arAgentId, int $customerId, ?string $customNote = null): array
    {
        $agent = ArAgent::find($arAgentId);
        if (!$agent) {
            return ['success' => false, 'message' => 'AR Agent tidak ditemukan.'];
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return ['success' => false, 'message' => 'Data pelanggan tidak ditemukan.'];
        }

        $saldo = number_format($customer->saldo_piutang, 0, ',', '.');
        $pranpc = $customer->is_pranpc ? ' ⚠️ [PRANPC]' : '';
        $risk = strtoupper($customer->risk_level ?? 'LOW');
        $tglStr = now()->translatedFormat('l, d F Y, H:i');

        $msg = "⚡ <b>PENUGASAN / DISPOSISI PELANGGAN</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Halo Kak <b>" . strtoupper($agent->name) . "</b>,\n";
        $msg .= "Berikut informasi pelanggan untuk segera ditindaklanjuti:\n\n";
        $msg .= "👤 <b>Nama:</b> {$customer->nama_pelanggan}{$pranpc}\n";
        $msg .= "🌐 <b>No. Internet (SND):</b> <code>{$customer->nomor_internet}</code>\n";
        if ($customer->no_hp_terbaru) {
            $msg .= "📱 <b>No. HP / WA:</b> +{$customer->formatted_wa_number}\n";
        }
        $msg .= "💰 <b>Saldo Piutang:</b> Rp {$saldo}\n";
        $msg .= "🎯 <b>Risk Level:</b> {$risk} (Score: {$customer->risk_score})\n";
        $msg .= "📍 <b>Alamat / Datel:</b> " . ($customer->alamat ?: ($customer->datel ?: '-')) . "\n";
        $msg .= "🏢 <b>STO:</b> " . ($customer->sto ?: '-') . "\n";

        if (!empty($customNote)) {
            $msg .= "\n📝 <b>Catatan Admin/Koordinator:</b>\n<i>\"" . htmlspecialchars($customNote) . "\"</i>\n";
        }

        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "⏰ <i>Dikirim dari CollectIQ pada {$tglStr} WIB</i>";

        return $this->sendCustomMessage($arAgentId, $msg);
    }
}