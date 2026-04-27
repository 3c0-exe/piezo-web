<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\SystemSetting;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(protected AnalyticsService $analytics) {}

    public function index(): JsonResponse
    {
        $settings      = SystemSetting::current();
        $activeSession = ChargingSession::whereNull('ended_at')
            ->latest('started_at')
            ->first();

        // ── Latest log: prefer session-scoped DB record, fall back to cache ──
        $latestLog     = null;
        $latestLogData = null;

        $firstSessionLog = null;

        if ($activeSession) {
            $firstSessionLog = EnergyLog::where('student_email', $activeSession->student_email)
                ->where('logged_at', '>=', $activeSession->started_at)
                ->orderBy('logged_at')
                ->first();

            $latestLog = EnergyLog::where('student_email', $activeSession->student_email)
                ->where('logged_at', '>=', $activeSession->started_at)
                ->orderByDesc('logged_at')
                ->first();

            if ($latestLog) {
                $latestLogData = [
                    'steps'              => $latestLog->steps,
                    'watts'              => $latestLog->watts,
                    'voltage'            => $latestLog->voltage,
                    'battery_percentage' => $latestLog->battery_percentage,
                    'battery_health'     => $latestLog->battery_health,
                    'is_charging'        => (bool) $latestLog->is_charging,
                    'charging_source'    => $latestLog->charging_source,
                    'logged_at'          => $latestLog->logged_at->toISOString(),
                    'source'             => 'session',
                ];
            }
        }

        // No session log — use the cached live reading from ESP32 if available
        if (! $latestLogData) {
            $cached = Cache::get('esp32_latest');
            if ($cached) {
                $latestLogData = array_merge($cached, ['source' => 'live']);
            }
        }

        $analytics = $this->analytics->compute($settings, $activeSession);

        $sessionsToday = \App\Models\ChargingSession::whereDate('started_at', today())->count();

        return response()->json([
            'tracking_on'    => $activeSession !== null,
            'sessions_today' => $sessionsToday,
            'active_student' => $activeSession
                ? [
                    'name'          => $activeSession->student_name,
                    'email'         => $activeSession->student_email,
                    'started_at'    => $activeSession->started_at->format('h:i A'),
                    'started_at_ms' => $activeSession->started_at->timestamp * 1000,
                    'steps_start'   => $firstSessionLog?->steps ?? null,
                ]
                : null,
            'latest_log' => $latestLogData,
            'analytics'  => $analytics,
        ]);
    }
}