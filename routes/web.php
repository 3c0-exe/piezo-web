<?php

use App\Http\Controllers\Auth\LoginController;
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

// ── Authenticated routes ──────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard
    Route::get('/dashboard',                 [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/toggle',         [DashboardController::class, 'toggleTracking'])->name('dashboard.toggle');
    Route::post('/dashboard/active-student', [DashboardController::class, 'setActiveStudent'])->name('dashboard.active-student');

    // Students
    Route::get('/students',              [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create',       [StudentController::class, 'create'])->name('students.create');
    Route::post('/students',             [StudentController::class, 'store'])->name('students.store');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    // Reports — Phase 6
    Route::get('/reports',                [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/sessions',[ReportsController::class, 'exportSessions'])->name('reports.export.sessions');
    Route::get('/reports/export/events',  [ReportsController::class, 'exportEvents'])->name('reports.export.events');

});
