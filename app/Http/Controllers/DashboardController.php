<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\SystemSetting;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $settings       = SystemSetting::current();
        $activeSession  = ChargingSession::whereNull('ended_at')
            ->latest('started_at')
            ->first();

        return view('dashboard.index', compact('settings', 'activeSession'));
    }
}