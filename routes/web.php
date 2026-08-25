<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\ArAgentController;
use App\Http\Controllers\RiskScoreController;
use App\Http\Controllers\PtpController;
use App\Http\Controllers\PtpMonitoringController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\C3mrCaringController;
use App\Http\Controllers\C3mrPerformanceController;
use App\Http\Controllers\C3mrSyncController;
use App\Http\Controllers\GlobalSearchController;

/*
|--------------------------------------------------------------------------
| Main Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/', [DashboardController::class, 'index']);
Route::get('/search', [GlobalSearchController::class, 'search'])->name('global.search');

/*
|--------------------------------------------------------------------------
| Customer & Customer 360
|--------------------------------------------------------------------------
*/
Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
Route::get('/customers', [CustomerController::class, 'index']);
Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customer.show');

/*
|--------------------------------------------------------------------------
| Visits Monitoring & Photo Proxy
|--------------------------------------------------------------------------
*/
Route::get('/visits/export', [VisitController::class, 'export'])->name('visit.export');
Route::get('/visits', [VisitController::class, 'index']);
Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visit.show');
Route::get('/visits/{visit}/photo', [VisitController::class, 'photo'])->name('visit.photo');

/*
|--------------------------------------------------------------------------
| Collection Intelligence & Monitoring
|--------------------------------------------------------------------------
*/
Route::get('/ptp-monitoring/export', [PtpMonitoringController::class, 'export'])->name('ptp.export');
Route::get('/ptp-monitoring', [PtpMonitoringController::class, 'index']);
Route::get('/ptp', [PtpController::class, 'index']);
Route::get('/piutang/export', [PiutangController::class, 'export'])->name('piutang.export');
Route::get('/piutang', [PiutangController::class, 'index'])->name('piutang.index');
Route::get('/risk-score/export', [RiskScoreController::class, 'export'])->name('risk-score.export');
Route::get('/risk-score', [RiskScoreController::class, 'index']);

/*
|--------------------------------------------------------------------------
| C3MR Intelligence
|--------------------------------------------------------------------------
*/
Route::get('/c3mr/hasil-caring/export', [C3mrCaringController::class, 'export'])->name('c3mr.caring.export');
Route::get('/c3mr/hasil-caring', [C3mrCaringController::class, 'index'])->name('c3mr.caring');
Route::get('/c3mr/performance', [C3mrPerformanceController::class, 'index'])->name('c3mr.performance');

Route::get('/c3mr/sync', [C3mrSyncController::class, 'index'])->name('c3mr.sync');
Route::post('/c3mr/sync/data-all', [C3mrSyncController::class, 'syncDataAll']);
Route::post('/c3mr/sync/caring', [C3mrSyncController::class, 'syncCaring']);
Route::post('/c3mr/sync/performance', [C3mrSyncController::class, 'syncPerformance']);
Route::post('/c3mr/sync/consolidate-ar', [C3mrSyncController::class, 'consolidateAr']);

/*
|--------------------------------------------------------------------------
| AR Agents
|--------------------------------------------------------------------------
*/
Route::get('/ar-agents/export', [ArAgentController::class, 'export'])->name('ar-agents.export');
Route::get('/ar-agents', [ArAgentController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Legacy Import & Settings
|--------------------------------------------------------------------------
*/
Route::get('/import', [ImportController::class, 'index']);
Route::post('/import', [ImportController::class, 'import']);
Route::get('/sync-priti', [SyncController::class, 'sync']);
Route::get('/settings', [SettingController::class, 'index']);
Route::post('/settings', [SettingController::class, 'update']);