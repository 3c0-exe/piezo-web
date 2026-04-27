<?php

namespace App\Console\Commands;

use App\Mail\SessionOvertime;
use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\EventLog;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class MqttListen extends Command
{
    protected $signature   = 'mqtt:listen';
    protected $description = 'Subscribe to piezo/data and process incoming ESP32 payloads';

    public function handle(MqttService $mqtt): int
    {
        $this->info('📡 MQTT Listener starting...');
        $this->info('   Broker  : ' . config('mqtt-client.connections.default.host'));
        $this->info('   Topic   : piezo/data');
        $this->newLine();
        $this->info('▶  Listening. Press Ctrl+C to stop.');
        $this->newLine();

        $mqtt->subscribe('piezo/data', function (string $topic, string $raw) {
            $payload = json_decode($raw, true);

            if (! is_array($payload)) {
                $this->warn('[' . now()->format('H:i:s') . '] ⚠ Invalid JSON received — skipping.');
                return;
            }

            $voltage    = $payload['voltage']     ?? null;
            $isCharging = $payload['is_charging'] ?? false;
            $stepCount  = $payload['step_count']  ?? ($isCharging ? 1 : 0);

            $this->line(sprintf(
                '[%s] voltage=%.3f | is_charging=%s | steps=%d',
                now()->format('H:i:s'),
                $voltage,
                $isCharging ? 'true' : 'false',
                $stepCount
            ));

            // ── Always cache the latest raw reading from the ESP32 ────────
            // This lets the dashboard show live battery/voltage data even
            // when no charging session is active.
if ($voltage !== null) {
                // ── Persist device lifetime steps to DB (survives restarts) ──
                if ($stepCount > 0) {
                    SystemSetting::where('id', 1)->increment('device_total_steps', $stepCount);
                }
                $deviceSteps = SystemSetting::current()->device_total_steps;

                $watts = $stepCount > 0
                                ? min(0.8, round(0.05 + ($stepCount * 0.03) + mt_rand(0, 80) / 1000, 4))
                                : 0.0;

                $prev = Cache::get('esp32_latest', []);
                Cache::put('esp32_latest', [
                    'voltage'            => $voltage,
                    'battery_percentage' => $this->deriveBatteryPercentage($voltage),
                    'battery_health'     => $this->deriveBatteryHealth($voltage),
                    'is_charging'        => (bool) $isCharging,
                    'charging_source'    => null,
                    'steps'              => $deviceSteps,
                    'watts'              => $stepCount > 0 ? $watts : ($prev['watts'] ?? 0.0),
                    'logged_at'          => now()->toISOString(),
                ], 86400);
            }

            $this->processPayload($voltage, (bool) $isCharging, (int) $stepCount);
        });

        return 0;
    }

    // ── State ─────────────────────────────────────────────────────────
    private int  $nonChargingTick          = 0;
    private int  $lastOvertimeMinuteLogged = 0;
    private bool $lastWasCharging          = false;
    private ?string $currentChargingSource = null;

    // ── Payload Processor ─────────────────────────────────────────────
    private function processPayload(?float $voltage, bool $isCharging, int $stepCount): void
    {
        $settings = SystemSetting::current();

        

        if ($voltage === null) {
            $this->warn('[' . now()->format('H:i:s') . '] ⚠ Missing voltage — skipping.');
            return;
        }

        $studentEmail      = $settings->active_student_email;
        $studentName       = $settings->active_student_name;
        $batteryPercentage = $this->deriveBatteryPercentage($voltage);
        $batteryHealth     = $this->deriveBatteryHealth($voltage);

        // ── Find active session ───────────────────────────────────────
        $session = ChargingSession::where('student_email', $studentEmail)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (! $session) {
            $this->warn('[' . now()->format('H:i:s') . '] ⚠ No active session found — skipping.');
            return;
        }

        // ── Set battery_start on first log ────────────────────────────
        if ($session->battery_start === null) {
            $session->update(['battery_start' => $batteryPercentage]);
        }

        $lastLog      = EnergyLog::where('student_email', $studentEmail)
            ->orderByDesc('logged_at')
            ->first();
        $currentSteps = $lastLog ? $lastLog->steps : 0;

        if ($isCharging) {
            // ── Charging tick ─────────────────────────────────────────
            $currentSteps += $stepCount;

            $baseWatts = $stepCount > 0
                ? round(0.05 + ($stepCount * 0.03) + mt_rand(0, 80) / 1000, 4)
                : 0.0;
            $watts = min(0.8, $baseWatts);

            if (! $this->lastWasCharging) {
                $this->currentChargingSource = $stepCount > 0 ? 'piezo' : 'ac';
            }

            EnergyLog::create([
                'student_email'      => $studentEmail,
                'student_name'       => $studentName,
                'steps'              => $currentSteps,
                'watts'              => $watts,
                'voltage'            => $voltage,
                'battery_percentage' => $batteryPercentage,
                'battery_health'     => $batteryHealth,
                'is_charging'        => true,
                'charging_source'    => $this->currentChargingSource,
                'logged_at'          => now(),
            ]);

            // ── Update peak watts on session ──────────────────────────
            if ($watts > ($session->peak_watts ?? 0)) {
                $session->update(['peak_watts' => $watts]);
            }

            $this->checkOvertime($settings, $session);
            $this->lastWasCharging = true;

        } else {
            // ── Non-charging tick ─────────────────────────────────────
            $this->nonChargingTick++;

            $wasCharging = $this->lastWasCharging;
            $this->lastWasCharging       = false;
            $this->currentChargingSource = null;

            if (! $wasCharging && $this->nonChargingTick % 16 !== 1) {
                return;
            }

            EnergyLog::create([
                'student_email'      => $studentEmail,
                'student_name'       => $studentName,
                'steps'              => $currentSteps,
                'watts'              => 0,
                'voltage'            => $voltage,
                'battery_percentage' => $batteryPercentage,
                'battery_health'     => $batteryHealth,
                'is_charging'        => false,
                'charging_source'    => null,
                'logged_at'          => now(),
            ]);
        }
    }

    // ── Overtime Check ────────────────────────────────────────────────
    private function checkOvertime(SystemSetting $settings, ChargingSession $session): void
    {
        if (! $settings->tracking_started_at) {
            return;
        }

        $elapsed        = now()->diffInSeconds($settings->tracking_started_at);
        $elapsedMinutes = (int) floor($elapsed / 60);

        if ($elapsed <= 1200) {
            return;
        }

        if ($elapsedMinutes === 21) {
            $session->update(['flagged_overtime' => true]);

            // ── Notify student once at the 21-minute mark ─────────────
            Mail::to($session->student_email)->queue(new SessionOvertime($session));
        }

        if ($elapsedMinutes > $this->lastOvertimeMinuteLogged) {
            $this->lastOvertimeMinuteLogged = $elapsedMinutes;

            EventLog::record(
                'session_overtime',
                "Session is overtime at {$elapsedMinutes} minutes.",
                ['elapsed_seconds' => $elapsed, 'student_email' => $session->student_email]
            );

            $this->warn('[' . now()->format('H:i:s') . "] ⚠ OVERTIME — {$elapsedMinutes} min elapsed.");
        }
    }

    // ── Voltage Lookup Table ──────────────────────────────────────────
    private function deriveBatteryPercentage(float $voltage): int
    {
        return match(true) {
            $voltage >= 4.15 => 100,
            $voltage >= 4.00 =>  85,
            $voltage >= 3.85 =>  70,
            $voltage >= 3.70 =>  55,
            $voltage >= 3.55 =>  40,
            $voltage >= 3.40 =>  25,
            $voltage >= 3.20 =>  10,
            default          =>   0,
        };
    }

    // ── Health Lookup Table ───────────────────────────────────────────
    private function deriveBatteryHealth(float $voltage): string
    {
        return match(true) {
            $voltage >= 3.85 => 'Good',
            $voltage >= 3.55 => 'Fair',
            $voltage >= 3.20 => 'Low',
            default          => 'Critical',
        };
    }
}