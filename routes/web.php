<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
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
| Main Dashboard & Global Search
|--------------------------------------------------------------------------
*/
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/search', [GlobalSearchController::class, 'search'])->name('global.search');

/*
|--------------------------------------------------------------------------
| Customer & Customer 360
|--------------------------------------------------------------------------
*/
Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customer.show');

/*
|--------------------------------------------------------------------------
| Visits Monitoring & Photo Proxy
|--------------------------------------------------------------------------
*/
Route::get('/visits/export', [VisitController::class, 'export'])->name('visit.export');
Route::get('/visits', [VisitController::class, 'index'])->name('visits.index');
Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visit.show');
Route::get('/visits/{visit}/photo', [VisitController::class, 'photo'])->name('visit.photo');

/*
|--------------------------------------------------------------------------
| Collection Intelligence & Monitoring
|--------------------------------------------------------------------------
*/
Route::get('/ptp-monitoring/export', [PtpMonitoringController::class, 'export'])->name('ptp.export');
Route::get('/ptp-monitoring', [PtpMonitoringController::class, 'index'])->name('ptp.monitoring');
Route::get('/ptp', [PtpController::class, 'index'])->name('ptp.index');
Route::get('/piutang/export', [PiutangController::class, 'export'])->name('piutang.export');
Route::get('/piutang', [PiutangController::class, 'index'])->name('piutang.index');
Route::get('/risk-score/export', [RiskScoreController::class, 'export'])->name('risk-score.export');
Route::get('/risk-score', [RiskScoreController::class, 'index'])->name('risk-score.index');

/*
|--------------------------------------------------------------------------
| C3MR Intelligence & Unified Sync
|--------------------------------------------------------------------------
*/
Route::get('/c3mr', fn() => redirect('/c3mr/sync'));
Route::get('/c3mr/hasil-caring/export', [C3mrCaringController::class, 'export'])->name('c3mr.caring.export');
Route::get('/c3mr/hasil-caring', [C3mrCaringController::class, 'index'])->name('c3mr.caring');
Route::get('/c3mr/performance', [C3mrPerformanceController::class, 'index'])->name('c3mr.performance');

Route::get('/c3mr/sync', [C3mrSyncController::class, 'index'])->name('c3mr.sync');
Route::post('/c3mr/sync/all', [C3mrSyncController::class, 'syncAll'])->name('c3mr.sync.all');

/*
|--------------------------------------------------------------------------
| AR Agents
|--------------------------------------------------------------------------
*/
Route::get('/ar-agents/export', [ArAgentController::class, 'export'])->name('ar-agents.export');
Route::get('/ar-agents', [ArAgentController::class, 'index'])->name('ar-agents.index');

/*
|--------------------------------------------------------------------------
| Settings (Spreadsheet C3MR Terpusat)
|--------------------------------------------------------------------------
*/
Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');