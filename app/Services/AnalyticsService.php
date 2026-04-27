<?php

namespace App\Services;

use App\Models\EnergyLog;
use App\Models\SystemSetting;

class AnalyticsService
{
    // Rough baseline: ~2 min per 1% based on piezo tile research data
    // Used as fallback when not enough session data to compute real pace
    private const FALLBACK_PACE_MIN_PER_PCT = 2.0;

    public function compute(SystemSetting $settings, ?\App\Models\ChargingSession $activeSession = null): array
    {
        if (! $activeSession) {
            return $this->globalAnalytics();
        }

        $elapsed  = now()->diffInSeconds($activeSession->started_at);
        $overtime = $elapsed > 1200;

        $pace = null;
        $eta  = null;

        if ($activeSession->student_email && $activeSession->started_at) {

            $newer = EnergyLog::where('student_email', $activeSession->student_email)
                ->where('logged_at', '>=', $activeSession->started_at)
                ->orderByDesc('logged_at')
                ->first();

            $older = EnergyLog::where('student_email', $activeSession->student_email)
                ->where('logged_at', '>=', $activeSession->started_at)
                ->orderBy('logged_at')
                ->first();

            // ── Try to compute real pace from battery % change ────────
            if ($newer && $older && $newer->id !== $older->id) {
                $batteryDiff = $newer->battery_percentage - $older->battery_percentage;
                $timeDiff    = abs($newer->logged_at->diffInSeconds($older->logged_at));

                if ($timeDiff > 0 && $batteryDiff > 0) {
                    $pace = ($timeDiff / 60) / $batteryDiff;
                }

                // ── Fallback: compute pace from voltage change ────────
                if ($pace === null) {
                    $voltageDiff = $newer->voltage - $older->voltage;
                    if ($timeDiff > 0 && $voltageDiff > 0) {
                        // 0.95V spans ~100% battery (3.20V→4.15V)
                        $pctGainedEst = $voltageDiff * (100 / 0.95);
                        if ($pctGainedEst > 0) {
                            $pace = ($timeDiff / 60) / $pctGainedEst;
                        }
                    }
                }
            }

            // ── Last resort: use fallback pace constant ───────────────
            // This kicks in when there's only 1 log, or battery/voltage
            // haven't moved yet — so we still show an estimated ETA
            if ($pace === null) {
                $pace = self::FALLBACK_PACE_MIN_PER_PCT;
            }

            // ── Compute ETA from whichever pace we have ───────────────
            $currentBattery = $newer->battery_percentage ?? null;
            if ($currentBattery !== null) {
                $remaining = max(0, 100 - $currentBattery);
                $eta       = $pace * $remaining;
            }
        }

        return [
            'charging_pace_min_per_pct' => $pace !== null ? round($pace, 2) : null,
            'eta_to_full_minutes'       => $eta  !== null ? round($eta, 1)  : null,
            'session_elapsed_seconds'   => $elapsed,
            'is_overtime'               => $overtime,
        ];
    }

    private function globalAnalytics(): array
    {
        $latest = EnergyLog::orderByDesc('logged_at')->first();
        $oldest = EnergyLog::orderBy('logged_at')->first();

        $pace = null;
        $eta  = null;

        if ($latest && $oldest && $latest->id !== $oldest->id) {
            $batteryDiff = $latest->battery_percentage - $oldest->battery_percentage;
            $timeDiff    = abs($latest->logged_at->diffInSeconds($oldest->logged_at));

            if ($timeDiff > 0 && $batteryDiff > 0) {
                $pace = ($timeDiff / 60) / $batteryDiff;
            } else {
                $voltageDiff  = $latest->voltage - $oldest->voltage;
                $pctGainedEst = $voltageDiff * (100 / 0.95);
                if ($timeDiff > 0 && $pctGainedEst > 0) {
                    $pace = ($timeDiff / 60) / $pctGainedEst;
                }
            }

            if ($pace !== null && $latest->battery_percentage !== null) {
                $remaining = max(0, 100 - $latest->battery_percentage);
                $eta       = $pace * $remaining;
            }
        }

        // Fallback for global view too
        if ($pace === null && $latest && $latest->battery_percentage !== null) {
            $pace      = self::FALLBACK_PACE_MIN_PER_PCT;
            $remaining = max(0, 100 - $latest->battery_percentage);
            $eta       = $pace * $remaining;
        }

        return [
            'charging_pace_min_per_pct' => $pace !== null ? round($pace, 2) : null,
            'eta_to_full_minutes'       => $eta  !== null ? round($eta, 1)  : null,
            'session_elapsed_seconds'   => null,
            'is_overtime'               => false,
        ];
    }

    private function emptyAnalytics(): array
    {
        return [
            'charging_pace_min_per_pct' => null,
            'eta_to_full_minutes'       => null,
            'session_elapsed_seconds'   => null,
            'is_overtime'               => false,
        ];
    }
}