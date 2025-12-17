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




