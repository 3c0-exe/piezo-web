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

    public function index(): JsonResponse
    {
        $settings      = SystemSetting::current();
        $activeSession = ChargingSession::whereNull('ended_at')
            ->latest('started_at')
            ->first();

        $latestLog = null;

        if ($activeSession) {
            $latestLog = EnergyLog::where('student_email', $activeSession->student_email)
                ->where('logged_at', '>=', $activeSession->started_at)
                ->orderByDesc('logged_at')
                ->first();
        }

        $analytics = $this->analytics->compute($settings, $activeSession);

return response()->json([
    'tracking_on'    => $activeSession !== null,
'active_student' => $activeSession
    ? [
        'name'       => $activeSession->student_name,
        'email'      => $activeSession->student_email,
        'started_at' => $activeSession->started_at->format('h:i A'),
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