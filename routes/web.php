<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\LiveMonitorController;
use App\Http\Controllers\ThreatReportsController;


Route::get('/', function () {
    return view('welcome');
})->name('logout');

use Illuminate\Support\Facades\View;

Route::get('/login', function () {
    if (View::exists('auth.login')) {
        return view('auth.login');
    }

    return view('welcome', ['showNoAccountModal' => true]);
})->name('login');

Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout.action');


Route::middleware('check.session')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'gotoDashboard'])
        ->name('admin.dashboard');
    Route::get('/dashboard/refresh', [DashboardController::class, 'refreshData']);

    Route::get('/logs', [LogController::class, 'gotoLogs'])
        ->name('admin.logs');

    Route::get('/system-status', [SystemStatusController::class, 'gotoStatus'])
        ->name('admin.system-status');
    Route::get('/system-status/alert', [SystemStatusController::class, 'alertFragment'])->name('admin.system-status.alert');
    
    Route::get('/live', [LiveMonitorController::class, 'gotoLive'])->name('admin.live');

    Route::get('/threat-reports', [ThreatReportsController::class, 'index'])
        ->name('admin.threat-reports');
    Route::get('/threat-reports/export', [ThreatReportsController::class, 'export'])
        ->name('admin.threat-reports.export');
    Route::get('/threat-reports/data', [ThreatReportsController::class, 'data'])
        ->name('admin.threat-reports.data');
});





