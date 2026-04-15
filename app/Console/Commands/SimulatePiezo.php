<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Console\Command;

class SimulatePiezo extends Command
{
    protected $signature = 'piezo:simulate
                            {--interval=3 : Seconds between each publish}
                            {--count=0    : Number of publishes (0 = infinite)}';

    protected $description = 'Simulate ESP32 sensor data — publishes to piezo/data via MQTT';

    // ── Voltage lookup table (mirrors firmware + backend) ────────────
    private function voltageToPercent(float $v): int
    {
        return match(true) {
            $v >= 4.15 => 100,
            $v >= 4.00 => 85,
            $v >= 3.85 => 70,
            $v >= 3.70 => 55,
            $v >= 3.55 => 40,
            $v >= 3.40 => 25,
            $v >= 3.20 => 10,
            default    => 0,
        };
    }

    public function handle(MqttService $mqtt): int
    {
        $interval = (int) $this->option('interval');
        $count    = (int) $this->option('count');

        $this->info('🔌 Piezo Simulator starting (MQTT mode)...');
        $this->info("   Broker  : " . config('mqtt-client.connections.default.host'));
        $this->info("   Interval: {$interval}s");
        $this->info("   Count   : " . ($count === 0 ? '∞ infinite' : $count));
        $this->newLine();

        // ── Initial simulated state ──────────────────────────────────
        // Start mid-range so there is room to both rise and fall.
        $voltage    = 3.70;

        // Charging burst state — controls the true/false alternation pattern.
        $burstTrueRemaining  = 0;
        $burstFalseRemaining = 0;

        $iteration = 0;

        $this->info('▶  Simulation loop running. Press Ctrl+C to stop.');
        $this->newLine();

        while (true) {
            // ── Check tracking state from DB ─────────────────────────
            $setting = SystemSetting::current();

            if (! $setting->is_tracking_on) {
                $this->line("⏸  Tracking is OFF — waiting {$interval}s...");
                sleep($interval);
                continue;
            }

            $studentName = $setting->activeStudent?->name ?? 'Unknown';

            // ── Determine is_charging for this tick ──────────────────
            // When both counters are zero, start a new burst cycle.
            if ($burstTrueRemaining <= 0 && $burstFalseRemaining <= 0) {
                $burstTrueRemaining  = rand(1, 3);
                $burstFalseRemaining = rand(4, 10);
            }

            if ($burstTrueRemaining > 0) {
                $isCharging = true;
                $burstTrueRemaining--;
            } else {
                $isCharging = false;
                $burstFalseRemaining--;
            }

            // ── Simulate voltage ─────────────────────────────────────
            // Charging ticks nudge voltage up slightly; idle ticks apply
            // tiny random noise to keep readings lifelike.
            if ($isCharging) {
                $voltage += rand(5, 15) / 1000;   // +0.005 – +0.015 V per charge tick
            } else {
                $voltage += rand(-3, 3) / 1000;   // ±0.003 V noise at idle
            }

            // Clamp to realistic battery range.
            $voltage = round(min(4.20, max(3.00, $voltage)), 2);

            // ── Build payload (mirrors real ESP32 output exactly) ────
            $payload = [
                'voltage'     => $voltage,
                'is_charging' => $isCharging,
            ];

            // ── Publish to piezo/data ────────────────────────────────
            try {
                $mqtt->publish('piezo/data', json_encode($payload), false);

                $iteration++;
                $pct = $this->voltageToPercent($voltage);

                $this->line(sprintf(
                    '[%s] #%d | 👤 %s | Voltage: %.2f V (%d%%) | Charging: %s',
                    now()->format('H:i:s'),
                    $iteration,
                    $studentName,
                    $voltage,
                    $pct,
                    $isCharging ? '⚡ YES' : '— no',
                ));

            } catch (\Exception $e) {
                $this->error('❌ MQTT publish failed: ' . $e->getMessage());
            }

            if ($count > 0 && $iteration >= $count) break;

            sleep($interval);
        }

        $this->newLine();
        $this->info('✅ Simulation complete.');
        return 0;
    }
}