<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// ── Auth routes ───────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── QR / Google OAuth routes (no auth required) ───────────────────────
Route::get('/scan',              [GoogleController::class, 'landing'])->name('qr.landing');
Route::get('/auth/google',       [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
Route::get('/scan/success',      [GoogleController::class, 'success'])->name('qr.success');

// ── Authenticated routes ──────────────────────────────────────────────
Route::middleware('auth')->group(function () {

Route::get('/students', [StudentController::class, 'index'])->name('students.index');

    Route::post('/dashboard/stop', [DashboardController::class, 'stopSession'])->name('dashboard.stop');

    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Reports
    Route::get('/reports',                 [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/sessions', [ReportsController::class, 'exportSessions'])->name('reports.export.sessions');
    Route::get('/reports/export/events',   [ReportsController::class, 'exportEvents'])->name('reports.export.events');

});

Route::get('/debug-session', function () {
    return response()->json([
        'settings' => App\Models\SystemSetting::current()->toArray(),
        'latest_session' => App\Models\ChargingSession::latest()->first()?->toArray(),
    ]);
});