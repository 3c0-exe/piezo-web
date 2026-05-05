<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\EventLog;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WatchdogCheck extends Command
{
    protected $signature   = 'piezo:watchdog';
    protected $description = 'Pause session if ESP32 has gone silent for 2+ minutes';

    public function handle(): int
    {
        $latest = Cache::get('esp32_latest');

        // No active session — nothing to watch
        $session = ChargingSession::whereNull('ended_at')->latest('started_at')->first();
        if (! $session) {
            return 0;
        }

        $settings     = SystemSetting::current();
        $studentEmail = $session->student_email;
        $studentName  = $session->student_name;

        // Check last MQTT message time
        $lastSeen = $latest['logged_at'] ?? null;

        $silentFor = $lastSeen
            ? now()->diffInSeconds(\Carbon\Carbon::parse($lastSeen))
            : now()->diffInSeconds($session->started_at);

        if ($silentFor < 120) {
            $this->info("ESP32 last seen {$silentFor}s ago — OK.");
            return 0;
        }

        // ── Silent for 2+ minutes — pause session ─────────────────────
        $this->warn("ESP32 silent for {$silentFor}s — pausing session.");

        $elapsed   = now()->diffInSeconds($session->started_at);
        $timeLimit = $session->time_limit ?? 1200;
        $remaining = max(0, $timeLimit - $elapsed);

        Cache::put("piezo_remaining_{$studentEmail}", (int) $remaining, now()->addHours(24));

        $logs = EnergyLog::where('student_email', $studentEmail)
            ->where('logged_at', '>=', $session->started_at)
            ->get();

        $session->update([
            'ended_at'     => now(),
            'total_steps'  => $logs->last()?->steps ?? 0,
            'peak_watts'   => $logs->max('watts')    ?? 0,
            'peak_voltage' => $logs->max('voltage')   ?? 0,
            'battery_end'  => $logs->last()?->battery_percentage ?? null,
            'pause_reason' => 'device_silent',
        ]);

        $settings->update([
            'is_tracking_on'       => false,
            'active_student_name'  => null,
            'active_student_email' => null,
            'tracking_started_at'  => null,
        ]);

        EventLog::record(
            'session_paused',
            "Session paused — ESP32 silent for {$silentFor}s. Remaining: {$remaining}s",
            ['student_email' => $studentEmail, 'remaining_seconds' => $remaining]
        );

        $this->info("Session paused. Remaining {$remaining}s cached for {$studentEmail}.");

        return 0;
    }
}