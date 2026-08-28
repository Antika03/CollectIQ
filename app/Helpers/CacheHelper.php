<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheHelper
{
    /**
     * Hapus semua cache analitik dashboard, visit, customer, dan master data.
     */
    public static function clearDashboardCaches(): void
    {
        $keys = [
            // Visit caches
            'visit_kpis',
            'visit_kpi_summary',
            'visit_chart_trend',
            'visit_extra_stats',
            'visit_filter_options',

            // Dashboard caches
            'dashboard_kpis',
            'executive_dashboard_full_stats',

            // Customer & AR caches
            'cust_kpis_admin',
            'ar_agents_all',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Jika cache tags didukung oleh driver (misal redis/memcached)
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['visits', 'dashboard', 'customers', 'kpis'])->flush();
            }
        } catch (\Throwable $e) {
            // Ignore jika store tidak mendukung tags
        }

        Log::info('[CacheHelper] Seluruh cache KPI dashboard & visit berhasil dibersihkan.');
    }
}
