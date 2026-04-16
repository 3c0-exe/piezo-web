<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $settings  = SystemSetting::current()->load('activeStudent');
        $latestLog = $settings->is_tracking_on && $settings->active_student_id && $settings->tracking_started_at
            ? EnergyLog::where('student_id', $settings->active_student_id)
                ->where('logged_at', '>=', $settings->tracking_started_at)
                ->orderByDesc('logged_at')
                ->first()
            : EnergyLog::where('student_id', $settings->active_student_id)
                ->orderByDesc('logged_at')
                ->first();
        $previousLog = $latestLog
            ? EnergyLog::where('student_id', $settings->active_student_id)
                ->where('id', '<', $latestLog->id)
                ->orderByDesc('logged_at')
                ->first()
            : null;
        $analytics = $this->analytics->compute($settings);

        return response()->json([
            'tracking_on'    => $settings->is_tracking_on,
            'active_student' => $settings->activeStudent
                ? [
                    'id'         => $settings->activeStudent->id,
                    'name'       => $settings->activeStudent->name,
                    'student_id' => $settings->activeStudent->student_id,
                    'section'    => $settings->activeStudent->section,
                    'year_level' => $settings->activeStudent->year_level,
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
                    'charging_source'    => $latestLog->is_charging
                                            ? ($latestLog->watts > 0 && $previousLog?->is_charging ? 'piezo' : 'ac')
                                            : null,
                    'logged_at'          => $latestLog->logged_at->toISOString(),
                ]
                : null,
            'analytics' => $analytics,
        ]);
    }
}
