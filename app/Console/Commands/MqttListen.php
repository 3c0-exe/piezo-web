<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\EventLog;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Console\Command;

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
            $stepCount  = $payload['step_count']  ?? ($isCharging ? 1 : 0); // Default to 1 for backward compatibility

            $this->line(sprintf(
                '[%s] voltage=%.3f | is_charging=%s | steps=%d',
                now()->format('H:i:s'),
                $voltage,
                $isCharging ? 'true' : 'false',
                $stepCount
            ));

            // ── Payload processor goes here (Task 2.2) ───────────────
            $this->processPayload($voltage, (bool) $isCharging, (int) $stepCount);
        });

        return 0;
    }

    // ── State ────────────────────────────────────────────────────────────────
private int  $nonChargingTick         = 0;
private int  $lastOvertimeMinuteLogged = 0;
private bool $lastWasCharging          = false;

    // ── Payload Processor ────────────────────────────────────────────────────
    private function processPayload(?float $voltage, bool $isCharging, int $stepCount): void
    {
        $settings = SystemSetting::current();

        if (! $settings->is_tracking_on) {
            return;
        }

        if ($voltage === null) {
            $this->warn('[' . now()->format('H:i:s') . '] ⚠ Missing voltage — skipping.');
            return;
        }

        $studentId         = $settings->active_student_id;
        $batteryPercentage = $this->deriveBatteryPercentage($voltage);
        $batteryHealth     = $this->deriveBatteryHealth($voltage);

        // ── Current step count ───────────────────────────────────────────────
        $lastLog      = EnergyLog::where('student_id', $studentId)
                            ->orderByDesc('logged_at')
                            ->first();
        $currentSteps = $lastLog ? $lastLog->steps : 0;

        if ($isCharging) {
            // ── Charging tick ────────────────────────────────────────────────
            $currentSteps += $stepCount; // 0 if USB charging, >0 if stepped

            // Watts only meaningful when steps are involved
            $baseWatts = $stepCount > 0
                ? round(0.05 + ($stepCount * 0.03) + mt_rand(0, 80) / 1000, 4)
                : 0.0;
            $watts = min(0.8, $baseWatts);

            EnergyLog::create([
                'student_id'         => $studentId,
                'steps'              => $currentSteps,
                'watts'              => $watts,
                'voltage'            => $voltage,
                'battery_percentage' => $batteryPercentage,
                'battery_health'     => $batteryHealth,
                'is_charging'        => true,
                'logged_at'          => now(),
            ]);

$this->checkOvertime($settings, $studentId);
            $this->lastWasCharging = true;

        } else {
    $this->nonChargingTick++;

    $wasCharging = $this->lastWasCharging;
    $this->lastWasCharging = false;

    if (! $wasCharging && $this->nonChargingTick % 16 !== 1) {
        return;
    }

            EnergyLog::create([
                'student_id'         => $studentId,
                'steps'              => $currentSteps,
                'watts'              => 0,
                'voltage'            => $voltage,
                'battery_percentage' => $batteryPercentage,
                'battery_health'     => $batteryHealth,
                'is_charging'        => false,
                'logged_at'          => now(),
            ]);
        }
    }

    // ── Overtime Check ───────────────────────────────────────────────────────
    private function checkOvertime(SystemSetting $settings, ?int $studentId): void
    {
        if (! $settings->tracking_started_at) {
            return;
        }

        $elapsed        = now()->diffInSeconds($settings->tracking_started_at);
        $elapsedMinutes = (int) floor($elapsed / 60);

        if ($elapsed <= 1200) {
            return;
        }

        // ── Flag the session once on first crossing ──────────────────────────
        if ($elapsedMinutes === 21) {
            // First minute past the 20-min mark — flag the session
            ChargingSession::where('student_id', $studentId)
                ->whereNull('ended_at')
                ->update(['flagged_overtime' => true]);
        }

        // ── Log once per minute while overtime ──────────────────────────────
        if ($elapsedMinutes > $this->lastOvertimeMinuteLogged) {
            $this->lastOvertimeMinuteLogged = $elapsedMinutes;

            EventLog::record(
                'session_overtime',
                "Session is overtime at {$elapsedMinutes} minutes.",
                ['elapsed_seconds' => $elapsed, 'student_id' => $studentId]
            );

            $this->warn('[' . now()->format('H:i:s') . "] ⚠ OVERTIME — {$elapsedMinutes} min elapsed.");
        }
    }

    // ── Voltage Lookup Table ─────────────────────────────────────────────────
    private function deriveBatteryPercentage(float $voltage): int
    {
        return match(true) {
            $voltage >= 4.15 => 100,
            $voltage >= 4.00 => 85,
            $voltage >= 3.85 => 70,
            $voltage >= 3.70 => 55,
            $voltage >= 3.55 => 40,
            $voltage >= 3.40 => 25,
            $voltage >= 3.20 => 10,
            default          => 0,
        };
    }

    // ── Health Lookup Table ──────────────────────────────────────────────────
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