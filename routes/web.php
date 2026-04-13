<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// ── Auth routes (unauthenticated only) ───────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ── Logout ───────────────────────────────────────────────────────────
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Authenticated routes ─────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard (stub — replaced in Phase 4)
    Route::get('/dashboard', fn () => view('dashboard.index'))->name('dashboard');

    // Students (stub — replaced in Phase 5)
    Route::get('/students', fn () => view('dashboard.index'))->name('students.index');

    // Reports (stub — replaced in Phase 6)
    Route::get('/reports', fn () => view('dashboard.index'))->name('reports.index');

});
