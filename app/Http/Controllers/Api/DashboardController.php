<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\SystemSetting;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(protected AnalyticsService $analytics) {}

    /**
     * GET /api/dashboard-data
     * Called every 3 seconds by the dashboard frontend.
     */
    public function index(): JsonResponse
    {
        $settings      = SystemSetting::current();
        $activeSession = ChargingSession::whereNull('ended_at')
            ->latest('started_at')
            ->first();

        $latestLog = $settings->is_tracking_on && $settings->active_student_email && $settings->tracking_started_at
            ? EnergyLog::where('student_email', $settings->active_student_email)
                ->where('logged_at', '>=', $settings->tracking_started_at)
                ->orderByDesc('logged_at')
                ->first()
            : EnergyLog::where('student_email', $settings->active_student_email)
                ->orderByDesc('logged_at')
                ->first();

        $analytics = $this->analytics->compute($settings);

        return response()->json([
            'tracking_on'    => $settings->is_tracking_on,
            'active_student' => $activeSession
                ? [
                    'name'  => $activeSession->student_name,
                    'email' => $activeSession->student_email,
                ]
                : null,
            'latest_log' => $latestLog
                ? [
                    'steps'              => $latestLog->steps,
                    'watts'              => $latestLog->watts,
                    'voltage'            => $latestLog->voltage,
                    'battery_percentage' => $latestLog->battery_percentage,
                    'battery_health'     => $latestLog->battery_health,
                    'is_charging'        => (bool) $latestLog->is_charging,
                    'charging_source'    => $latestLog->charging_source,
                    'logged_at'          => $latestLog->logged_at->toISOString(),
                ]
                : null,
            'analytics' => $analytics,
        ]);
    }
}