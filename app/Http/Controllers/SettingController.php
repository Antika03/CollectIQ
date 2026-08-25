<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();

        return view(
            'settings.index',
            compact('setting')
        );
    }

    public function update(Request $request)
    {
        $request->validate([
            'report_prq_url' => 'required|url',
            'viseepro_url'   => 'required|url',
        ]);

        $setting = Setting::first();

        if (!$setting) {

            $setting = Setting::create([
                'report_prq_url' => $request->report_prq_url,
                'viseepro_url'   => $request->viseepro_url,
            ]);

        } else {

            $setting->update([
                'report_prq_url' => $request->report_prq_url,
                'viseepro_url'   => $request->viseepro_url,
            ]);
        }

        return back()->with(
            'success',
            'Setting berhasil disimpan'
        );
    }
}