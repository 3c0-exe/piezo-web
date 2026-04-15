<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Esp32Controller;
use Illuminate\Support\Facades\Route;

// ── ESP32-facing routes ───────────────────────────────────────────────
Route::middleware('esp.key')->group(function () {
    Route::post('/connect', [Esp32Controller::class, 'connect']);
});

// ── Dashboard poll route ──────────────────────────────────────────────
// Uses 'web' middleware so it shares the browser session cookie
Route::middleware('web')->get('/dashboard-data', [DashboardController::class, 'index']);
