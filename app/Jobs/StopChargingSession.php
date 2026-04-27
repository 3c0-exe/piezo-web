<?php

namespace App\Jobs;

use App\Mail\SessionStopped;
use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class StopChargingSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $sessionId) {}

    public function handle(MqttService $mqtt): void
    {
        $session = ChargingSession::find($this->sessionId);

        if (! $session || $session->ended_at !== null) {
            return;
        }

        $logs = EnergyLog::where('student_email', $session->student_email)
            ->where('logged_at', '>=', $session->started_at)
            ->get();

        $peakWatts   = $logs->max('watts')               ?? 0;
        $peakVoltage = $logs->max('voltage')              ?? 0;
        $totalSteps  = $logs->max('steps')                ?? 0;
        $batteryEnd  = $logs->last()?->battery_percentage ?? null;

        $session->update([
            'ended_at'         => now(),
            'total_steps'      => $totalSteps,
            'peak_watts'       => $peakWatts,
            'peak_voltage'     => $peakVoltage,
            'battery_end'      => $batteryEnd,
            'flagged_overtime' => false,
        ]);

        SystemSetting::current()->update([
            'is_tracking_on'       => false,
            'active_student_name'  => null,
            'active_student_email' => null,
            'tracking_started_at'  => null,
        ]);

        $mqtt->publish('piezo/command', [
            'tracking_on'  => false,
            'student_name' => '',
        ], retain: true);

        // ── Notify student via email ──────────────────────────────────
        Mail::to($session->student_email)->queue(new SessionStopped($session));
    }
}