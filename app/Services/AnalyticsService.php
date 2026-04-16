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

if ($settings->is_tracking_on && $settings->active_student_id && $settings->tracking_started_at) {
$newer = EnergyLog::where('student_id', $settings->active_student_id)
        ->where('logged_at', '>=', $settings->tracking_started_at)
        ->orderByDesc('logged_at')
        ->first();

    $older = EnergyLog::where('student_id', $settings->active_student_id)
        ->where('logged_at', '>=', $settings->tracking_started_at)
        ->orderBy('logged_at')
        ->first();

    if ($newer && $older && $newer->id !== $older->id) {
$batteryDiff  = $newer->battery_percentage - $older->battery_percentage;
$voltageDiff  = $newer->voltage - $older->voltage;
$timeDiff     = abs($newer->logged_at->diffInSeconds($older->logged_at));

if ($timeDiff > 0 && ($batteryDiff > 0 || $voltageDiff > 0)) {
    if ($batteryDiff > 0) {
        $pace = ($timeDiff / 60) / $batteryDiff;
    } else {
        // Estimate pace from voltage trend (0.95V total range: 3.20–4.15)
        $pctPerVolt   = 100 / 0.95;
        $pctGainedEst = $voltageDiff * $pctPerVolt;
        $pace = $pctGainedEst > 0 ? ($timeDiff / 60) / $pctGainedEst : null;
    }

    if ($pace !== null) {
        $remaining = 100 - $newer->battery_percentage;
        $eta = $pace * $remaining;
    }
}
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
