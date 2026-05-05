<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\EventLog;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MqttListen extends Command
{
    protected $signature   = 'mqtt:listen';
    protected $description = 'Subscribe to piezo/data and process incoming ESP32 payloads';

    // ── Flat-voltage detection state ──────────────────────────────────
    private array  $recentVoltages    = [];   // [ ['voltage' => float, 'time' => Carbon], ... ]
    private const  FLAT_WINDOW        = 120;  // seconds — 2-minute buffer
    private const  FLAT_THRESHOLD     = 0.01; // V — minimum drop to count as "charging"

    // ── Other in-memory state ─────────────────────────────────────────
    private int    $nonChargingTick          = 0;
    private bool   $lastWasCharging          = false;
    private ?string $currentChargingSource   = null;

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
                $this->warn('[' . now()->format('H:i:s') . '] ⚠ Invalid JSON — skipping.');
                return;
            }

            $voltage    = $payload['voltage']     ?? null;
            $isCharging = $payload['is_charging'] ?? false;
            $stepCount  = $payload['step_count']  ?? ($isCharging ? 1 : 0);

            $this->line(sprintf(
                '[%s] voltage=%.3f | is_charging=%s | steps=%d',
                now()->format('H:i:s'),
                $voltage ?? 0,
                $isCharging ? 'true' : 'false',
                $stepCount
            ));

            // ── Always cache latest reading for dashboard ─────────────
            if ($voltage !== null) {
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

    // ── Payload Processor ─────────────────────────────────────────────
    private function processPayload(?float $voltage, bool $isCharging, int $stepCount): void
    {
        if ($voltage === null) {
            $this->warn('[' . now()->format('H:i:s') . '] ⚠ Missing voltage — skipping.');
            return;
        }

        $settings = SystemSetting::current();

        $studentEmail = $settings->active_student_email;
        $studentName  = $settings->active_student_name;

        // ── Recover from orphaned open session if settings were cleared ─
        if (! $studentEmail) {
            $orphan = ChargingSession::whereNull('ended_at')->latest('started_at')->first();
            if ($orphan) {
                $studentEmail = $orphan->student_email;
                $studentName  = $orphan->student_name;
                $this->warn('[' . now()->format('H:i:s') . '] ⚠ Recovered student from open session.');
            } else {
                $this->warn('[' . now()->format('H:i:s') . '] ⚠ No active student — skipping.');
                return;
            }
        }

        $batteryPercentage = $this->deriveBatteryPercentage($voltage);
        $batteryHealth     = $this->deriveBatteryHealth($voltage);

        // ── Find active session ───────────────────────────────────────
        $session = ChargingSession::where('student_email', $studentEmail)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (! $session) {
            $this->warn('[' . now()->format('H:i:s') . '] ⚠ No active session — skipping.');
            return;
        }

        // ── Set battery_start on first log ────────────────────────────
        if ($session->battery_start === null) {
            $session->update(['battery_start' => $batteryPercentage]);
        }

        // ── Track voltage for flat detection ──────────────────────────
        $this->recentVoltages[] = ['voltage' => $voltage, 'time' => now()];

        // Trim entries older than the flat window
        $cutoff = now()->subSeconds(self::FLAT_WINDOW);
        $this->recentVoltages = array_filter(
            $this->recentVoltages,
            fn($r) => $r['time']->gte($cutoff)
        );
        $this->recentVoltages = array_values($this->recentVoltages);

        // ── Check if voltage has been flat for 2 minutes ──────────────
        if ($this->isVoltageFlatForWindow()) {
            $this->pauseSession($session, $settings, $studentEmail);
            return;
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

            if ($watts > ($session->peak_watts ?? 0)) {
                $session->update(['peak_watts' => $watts]);
            }

            $this->lastWasCharging = true;

        } else {
            // ── Non-charging tick ─────────────────────────────────────
            $this->nonChargingTick++;

            $wasCharging           = $this->lastWasCharging;
            $this->lastWasCharging = false;
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

    // ── Flat Voltage Detection ────────────────────────────────────────
    private function isVoltageFlatForWindow(): bool
    {
        // Need at least 2 readings to compare
        if (count($this->recentVoltages) < 2) {
            return false;
        }

        // The oldest reading in the window must be >= FLAT_WINDOW seconds ago
        $oldest = $this->recentVoltages[0]['time'];
        if (now()->diffInSeconds($oldest) < self::FLAT_WINDOW) {
            return false; // haven't accumulated a full 2-minute window yet
        }

        // Check every consecutive pair — any drop > threshold means not flat
        for ($i = 1; $i < count($this->recentVoltages); $i++) {
            $drop = $this->recentVoltages[$i - 1]['voltage'] - $this->recentVoltages[$i]['voltage'];
            if ($drop > self::FLAT_THRESHOLD) {
                return false;
            }
        }

        return true;
    }

    // ── Pause Session ─────────────────────────────────────────────────
    private function pauseSession(ChargingSession $session, SystemSetting $settings, string $studentEmail): void
    {
        $elapsed   = now()->diffInSeconds($session->started_at);
        $timeLimit = $session->time_limit ?? 1200;
        $remaining = max(0, $timeLimit - $elapsed);

        $this->warn('[' . now()->format('H:i:s') . "] ⚠ Voltage flat for 2 minutes — pausing session. Remaining: {$remaining}s");

        // ── Save remaining time to cache for 24 hours ─────────────────
        Cache::put("piezo_remaining_{$studentEmail}", (int) $remaining, now()->addHours(24));

        // ── Finalize session in DB ────────────────────────────────────
        $logs        = EnergyLog::where('student_email', $studentEmail)
            ->where('logged_at', '>=', $session->started_at)
            ->get();

        $session->update([
            'ended_at'     => now(),
            'total_steps'  => $logs->last()?->steps ?? 0,
            'peak_watts'   => $logs->max('watts')   ?? 0,
            'peak_voltage' => $logs->max('voltage')  ?? 0,
            'battery_end'  => $logs->last()?->battery_percentage ?? null,
            'pause_reason' => 'voltage_flat',
        ]);

        // ── Clear tracking state ──────────────────────────────────────
        $settings->update([
            'is_tracking_on'       => false,
            'active_student_name'  => null,
            'active_student_email' => null,
            'tracking_started_at'  => null,
        ]);

        // ── Reset in-memory state ─────────────────────────────────────
        $this->recentVoltages  = [];
        $this->lastWasCharging = false;
        $this->currentChargingSource = null;
        $this->nonChargingTick = 0;

        EventLog::record(
            'session_paused',
            "Session paused due to flat voltage. Remaining: {$remaining}s",
            ['student_email' => $studentEmail, 'remaining_seconds' => $remaining]
        );
    }

    // ── Voltage Lookup Tables ─────────────────────────────────────────
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