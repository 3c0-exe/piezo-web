<?php

namespace App\Jobs;

use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StopChargingSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $sessionId) {}

    public function handle(MqttService $mqtt): void
    {
        $session = ChargingSession::find($this->sessionId);

        // Already stopped manually — do nothing
        if (! $session || $session->ended_at !== null) {
            return;
        }

        // ── Gather final stats from energy logs ───────────────────────
        $logs = EnergyLog::where('student_email', $session->student_email)
            ->where('logged_at', '>=', $session->started_at)
            ->get();

        $peakWatts   = $logs->max('watts')              ?? 0;
        $peakVoltage = $logs->max('voltage')             ?? 0;
        $totalSteps  = $logs->max('steps')               ?? 0;
        $batteryEnd  = $logs->last()?->battery_percentage ?? null;

        // ── Close the session ─────────────────────────────────────────
        $session->update([
            'ended_at'         => now(),
            'total_steps'      => $totalSteps,
            'peak_watts'       => $peakWatts,
            'peak_voltage'     => $peakVoltage,
            'battery_end'      => $batteryEnd,
            'flagged_overtime' => false,
        ]);

        // ── Reset SystemSetting ───────────────────────────────────────
        SystemSetting::current()->update([
            'is_tracking_on'       => false,
            'active_student_name'  => null,
            'active_student_email' => null,
            'tracking_started_at'  => null,
        ]);

        // ── Signal ESP32 to stop ──────────────────────────────────────
        $mqtt->publish('piezo/command', [
            'tracking_on'  => false,
            'student_name' => '',
        ], retain: true);
    }
}