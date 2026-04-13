<?php

namespace App\Services;

use App\Models\EnergyLog;
use App\Models\SystemSetting;

class AnalyticsService
{
    /**
     * Compute all analytics for the dashboard poll.
     * Returns an array with pace, eta, session_elapsed, is_overtime.
     */
    public function compute(SystemSetting $settings): array
    {
        $elapsed   = null;
        $overtime  = false;

        if ($settings->is_tracking_on && $settings->tracking_started_at) {
            $elapsed  = now()->diffInSeconds($settings->tracking_started_at);
            $overtime = $elapsed > 1200; // 20 minutes
        }

        $pace = null;
        $eta  = null;

        // Need at least 2 logs to compute pace
        $latestTwo = EnergyLog::orderByDesc('logged_at')
            ->limit(2)
            ->get();

        if ($latestTwo->count() === 2) {
            $newer = $latestTwo->first();
            $older = $latestTwo->last();

            $batteryDiff = $newer->battery_percentage - $older->battery_percentage;
            $timeDiff    = abs($newer->logged_at->diffInSeconds($older->logged_at));

            // Only compute pace when battery is actually gaining
            if ($batteryDiff > 0 && $timeDiff > 0) {
                // Minutes per 1% battery gained
                $pace = ($timeDiff / 60) / $batteryDiff;
                $remaining = 100 - $newer->battery_percentage;
                $eta  = $pace * $remaining; // in minutes
            }
        }

        return [
            'charging_pace_min_per_pct' => $pace  !== null ? round($pace, 2)  : null,
            'eta_to_full_minutes'       => $eta   !== null ? round($eta, 1)   : null,
            'session_elapsed_seconds'   => $elapsed,
            'is_overtime'               => $overtime,
        ];
    }
}
