<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginController;

Route::get('/', function () {
    return view('welcome');
})->name('logout');

Route::post('/login', [LoginController::class, 'login'])->name('login.post');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/dashboard', [DashboardController::class, 'gotoDashboard'])
    ->name('admin.dashboard');
Route::get('/dashboard/refresh', [DashboardController::class, 'refreshData']);

Route::get('/logs', [LogController::class, 'gotoLogs'])
    ->name('admin.logs');

Route::get('/system-status', [\App\Http\Controllers\SystemStatusController::class, 'gotoStatus'])
    ->name('admin.system-status');
Route::get('/system-status/alert', [SystemStatusController::class, 'alertFragment'])->name('admin.system-status.alert');
Route::get('/live', [\App\Http\Controllers\LiveMonitorController::class, 'gotoLive'])->name('admin.live');

// Threat Reports
Route::get('/threat-reports', [\App\Http\Controllers\ThreatReportsController::class, 'index'])
    ->name('admin.threat-reports');
Route::get('/threat-reports/export', [\App\Http\Controllers\ThreatReportsController::class, 'export'])
    ->name('admin.threat-reports.export');
Route::get('/threat-reports/data', [\App\Http\Controllers\ThreatReportsController::class, 'data'])
    ->name('admin.threat-reports.data');




