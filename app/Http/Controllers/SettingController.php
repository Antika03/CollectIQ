<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\C3mrSyncService;
use App\Services\PritiSyncService;
use App\Services\TelegramService;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();
        $activeC3mrUrl  = C3mrSyncService::getActiveSpreadsheetUrl();
        $c3mrSheetId    = C3mrSyncService::getActiveSpreadsheetId();
        $activePritiUrl = PritiSyncService::getActivePritiUrl();
        $pritiSheetId   = C3mrSyncService::extractSpreadsheetId($activePritiUrl);

        $lastSyncFormatted = C3mrSyncService::formatIndonesianDate($setting?->last_sync_at);
        $lastSyncStatus    = $setting?->last_sync_status;

        $telegramService = new TelegramService();
        $botStatus = $telegramService->testConnection();

        return view('settings.index', compact(
            'setting',
            'activeC3mrUrl',
            'c3mrSheetId',
            'activePritiUrl',
            'pritiSheetId',
            'lastSyncFormatted',
            'lastSyncStatus',
            'botStatus'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'c3mr_url'  => 'required|url',
            'priti_url' => 'required|url',
        ], [
            'c3mr_url.required'  => 'URL Google Spreadsheet C3MR wajib diisi.',
            'c3mr_url.url'       => 'Format URL Google Spreadsheet C3MR tidak valid.',
            'priti_url.required' => 'URL Google Spreadsheet PRITI DATA wajib diisi.',
            'priti_url.url'      => 'Format URL Google Spreadsheet PRITI DATA tidak valid.',
        ]);

        $c3mrUrl  = trim($request->c3mr_url);
        $pritiUrl = trim($request->priti_url);

        $valC3mr  = C3mrSyncService::validateSpreadsheetUrl($c3mrUrl);
        $valPriti = C3mrSyncService::validateSpreadsheetUrl($pritiUrl);

        if (!$valC3mr['valid']) {
            return back()->withInput()->with('error', "C3MR: " . $valC3mr['message']);
        }
        if (!$valPriti['valid']) {
            return back()->withInput()->with('error', "PRITI: " . $valPriti['message']);
        }

        $reminderEnabled = $request->boolean('telegram_reminder_enabled');
        $morningTime     = $request->input('telegram_morning_time', '08:30');
        $afternoonTime   = $request->input('telegram_afternoon_time', '15:30');

        $setting = Setting::first();

        $payload = [
            'c3mr_url'                  => $c3mrUrl,
            'priti_url'                 => $pritiUrl,
            'report_prq_url'            => $c3mrUrl,
            'viseepro_url'              => $c3mrUrl,
            'telegram_reminder_enabled' => $reminderEnabled,
            'telegram_morning_time'     => $morningTime,
            'telegram_afternoon_time'   => $afternoonTime,
        ];

        if (!$setting) {
            Setting::create($payload);
        } else {
            $setting->update($payload);
        }

        return back()->with('success', 'Pengaturan Spreadsheet PRITI DATA, C3MR & Telegram berhasil disimpan.');
    }

    public function testTelegram(Request $request)
    {
        $service = new TelegramService();
        $res = $service->testConnection();

        if ($res['success']) {
            return back()->with('success', $res['message']);
        }
        return back()->with('error', $res['message']);
    }

    public function sendRemindersNow(Request $request)
    {
        $service = new TelegramService();
        $res = $service->sendDailyReminders();

        if ($res['success']) {
            return back()->with('success', $res['message']);
        }
        return back()->with('error', $res['message']);
    }
}