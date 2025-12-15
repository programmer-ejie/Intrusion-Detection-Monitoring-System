<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;

Route::get('/', function () {
    return view('welcome');
})->name('logout');

Route::get('/dashboard', [DashboardController::class, 'gotoDashboard'])
    ->name('admin.dashboard');
Route::get('/dashboard/refresh', [DashboardController::class, 'refreshData']);

Route::get('/logs', [LogController::class, 'gotoLogs'])
    ->name('admin.logs');




