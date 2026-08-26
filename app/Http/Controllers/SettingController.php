<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\C3mrSyncService;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();
        $activeUrl = C3mrSyncService::getActiveSpreadsheetUrl();
        $sheetId = C3mrSyncService::getActiveSpreadsheetId();

        $lastSyncFormatted = C3mrSyncService::formatIndonesianDate($setting?->last_sync_at);
        $lastSyncStatus = $setting?->last_sync_status;

        return view('settings.index', compact(
            'setting',
            'activeUrl',
            'sheetId',
            'lastSyncFormatted',
            'lastSyncStatus'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'c3mr_url' => 'required|url',
        ], [
            'c3mr_url.required' => 'URL Google Spreadsheet C3MR wajib diisi.',
            'c3mr_url.url'      => 'Format URL Google Spreadsheet tidak valid.',
        ]);

        $url = trim($request->c3mr_url);

        // Validasi akses spreadsheet
        $validation = C3mrSyncService::validateSpreadsheetUrl($url);
        if (!$validation['valid']) {
            return back()->withInput()->with('error', $validation['message']);
        }

        $setting = Setting::first();

        if (!$setting) {
            Setting::create([
                'c3mr_url'       => $url,
                'report_prq_url' => $url,
                'viseepro_url'   => $url,
            ]);
        } else {
            $setting->update([
                'c3mr_url'       => $url,
                'report_prq_url' => $url,
                'viseepro_url'   => $url,
            ]);
        }

        return back()->with('success', 'Spreadsheet C3MR berhasil terhubung dan disimpan.');
    }
}