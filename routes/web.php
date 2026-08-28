<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ArDashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\ArAgentController;
use App\Http\Controllers\RiskScoreController;
use App\Http\Controllers\PtpController;
use App\Http\Controllers\PtpMonitoringController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\C3mrCaringController;
use App\Http\Controllers\C3mrPerformanceController;
use App\Http\Controllers\C3mrSyncController;
use App\Http\Controllers\GlobalSearchController;

/*
|--------------------------------------------------------------------------
| Railway Healthcheck
|--------------------------------------------------------------------------
|
| Endpoint khusus untuk Railway Healthcheck.
| Route ini tidak menggunakan middleware auth sehingga Railway
| dapat mengaksesnya tanpa login.
|
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok'
    ]);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes (Guest Only)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Admin & AR)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Main Dashboard & Global Search
    |--------------------------------------------------------------------------
    */

    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/ar/dashboard', [ArDashboardController::class, 'index'])
        ->name('ar.dashboard');

    Route::get('/search', [GlobalSearchController::class, 'search'])
        ->name('global.search');

    /*
    |--------------------------------------------------------------------------
    | Customer & Customer 360
    |--------------------------------------------------------------------------
    */

    Route::get('/customers/export', [CustomerController::class, 'export'])
        ->name('customers.export');

    Route::get('/customers', [CustomerController::class, 'index'])
        ->name('customers.index');

    Route::get('/customers/{customer}', [CustomerController::class, 'show'])
        ->name('customer.show');

    /*
    |--------------------------------------------------------------------------
    | Visits Monitoring & Photo Proxy
    |--------------------------------------------------------------------------
    */

    Route::get('/visits/export', [VisitController::class, 'export'])
        ->name('visit.export');

    Route::get('/visits', [VisitController::class, 'index'])
        ->name('visits.index');

    Route::get('/visits/{visit}', [VisitController::class, 'show'])
        ->name('visit.show');

    Route::get('/visits/{visit}/photo', [VisitController::class, 'photo'])
        ->name('visit.photo');

    /*
    |--------------------------------------------------------------------------
    | Collection Intelligence & Monitoring
    |--------------------------------------------------------------------------
    */

    Route::get('/ptp-monitoring/export', [PtpMonitoringController::class, 'export'])
        ->name('ptp.export');

    Route::get('/ptp-monitoring', [PtpMonitoringController::class, 'index'])
        ->name('ptp.monitoring');

    Route::get('/ptp', [PtpController::class, 'index'])
        ->name('ptp.index');

    Route::get('/piutang/export', [PiutangController::class, 'export'])
        ->name('piutang.export');

    Route::get('/piutang', [PiutangController::class, 'index'])
        ->name('piutang.index');

    Route::get('/risk-score/export', [RiskScoreController::class, 'export'])
        ->name('risk-score.export');

    Route::get('/risk-score', [RiskScoreController::class, 'index'])
        ->name('risk-score.index');

    /*
    |--------------------------------------------------------------------------
    | C3MR Intelligence (Read Views)
    |--------------------------------------------------------------------------
    */

    Route::get('/c3mr/hasil-caring/export', [C3mrCaringController::class, 'export'])
        ->name('c3mr.caring.export');

    Route::get('/c3mr/hasil-caring', [C3mrCaringController::class, 'index'])
        ->name('c3mr.caring');

    Route::get('/c3mr/performance', [C3mrPerformanceController::class, 'index'])
        ->name('c3mr.performance');

    /*
    |--------------------------------------------------------------------------
    | Administrator Only Routes (Role: admin)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | C3MR Single Unified Sync
        |--------------------------------------------------------------------------
        */

        Route::get('/c3mr', fn() => redirect('/c3mr/sync'));

        Route::get('/c3mr/sync', [C3mrSyncController::class, 'index'])
            ->name('c3mr.sync');

        Route::post('/c3mr/sync/all', [C3mrSyncController::class, 'syncAll'])
            ->name('c3mr.sync.all');

        Route::post('/c3mr/sync/data-all', [C3mrSyncController::class, 'syncDataAll'])
            ->name('c3mr.sync.data-all');

        Route::post('/c3mr/sync/caring', [C3mrSyncController::class, 'syncCaring'])
            ->name('c3mr.sync.caring');

        Route::post('/c3mr/sync/performance', [C3mrSyncController::class, 'syncPerformance'])
            ->name('c3mr.sync.performance');

        Route::post('/c3mr/sync/consolidate-ar', [C3mrSyncController::class, 'consolidateAr'])
            ->name('c3mr.sync.consolidate-ar');

        /*
        |--------------------------------------------------------------------------
        | AR Agents Master Management
        |--------------------------------------------------------------------------
        */

        Route::get('/ar-agents/export', [ArAgentController::class, 'export'])
            ->name('ar-agents.export');

        Route::get('/ar-agents', [ArAgentController::class, 'index'])
            ->name('ar-agents.index');

        /*
        |--------------------------------------------------------------------------
        | Settings
        |--------------------------------------------------------------------------
        */

        Route::get('/settings', [SettingController::class, 'index'])
            ->name('settings.index');

        Route::post('/settings', [SettingController::class, 'update'])
            ->name('settings.update');

        Route::post('/settings/test-telegram', [SettingController::class, 'testTelegram'])
            ->name('settings.test-telegram');

        Route::post('/settings/send-reminders', [SettingController::class, 'sendRemindersNow'])
            ->name('settings.send-reminders');
    });
});