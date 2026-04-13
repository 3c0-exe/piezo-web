<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimulatePiezo extends Command
{
    protected $signature = 'piezo:simulate
                            {--interval=3  : Seconds between each push}
                            {--count=0     : Number of pushes (0 = infinite)}
                            {--url=        : Base URL of the server}';

    protected $description = 'Simulate ESP32 sensor data for testing without hardware';

    public function handle(): int
    {
        $interval = (int) $this->option('interval');
        $count    = (int) $this->option('count');
        $baseUrl  = rtrim($this->option('url') ?: config('app.url'), '/');
        $apiKey   = config('app.esp_api_key');

        $headers = [
            'X-ESP-Key'    => $apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        $this->info("🔌 Piezo Simulator starting...");
        $this->info("   Server  : {$baseUrl}");
        $this->info("   Interval: {$interval}s");
        $this->info("   Count   : " . ($count === 0 ? '∞ infinite' : $count));
        $this->newLine();

        // ── Boot: POST /api/connect ──────────────────────────────────
        try {
            $res = Http::withHeaders($headers)->post("{$baseUrl}/api/connect");
            $this->info("✅ Connected to server: " . $res->body());
        } catch (\Exception $e) {
            $this->error("❌ Could not reach server: " . $e->getMessage());
            return 1;
        }

        // ── Simulate state ───────────────────────────────────────────
        $steps      = rand(100, 300);
        $battery    = round(rand(10, 40) + rand(0, 99) / 100, 2);
        $voltage    = 3.50;
        $iteration  = 0;

        $this->info("▶  Starting simulation loop. Press Ctrl+C to stop.");
        $this->newLine();

        while (true) {
            // ── Check /api/command first ─────────────────────────────
            try {
                $cmd = Http::withHeaders($headers)->get("{$baseUrl}/api/command");
                $cmdData = $cmd->json();

                if (! ($cmdData['tracking_on'] ?? false)) {
                    $this->line("⏸  Tracking is OFF — skipping push. Waiting {$interval}s...");
                    sleep($interval);

                    $iteration++;
                    if ($count > 0 && $iteration >= $count) break;
                    continue;
                }

                $studentName = $cmdData['active_student']['name'] ?? 'No student';
                $this->line("👤 Active student: {$studentName}");

            } catch (\Exception $e) {
                $this->error("❌ Command check failed: " . $e->getMessage());
                sleep($interval);
                continue;
            }

            // ── Simulate realistic increments ────────────────────────
            $steps   += rand(8, 25);
            $battery  = min(100, round($battery + rand(0, 30) / 100, 2));
            $voltage  = round(min(4.20, $voltage + rand(-5, 10) / 1000), 4);
            $watts    = round(rand(20, 80) / 1000, 4); // 0.020 – 0.080 W

            $health = match(true) {
                $battery >= 60 => 'Good',
                $battery >= 40 => 'Fair',
                $battery >= 20 => 'Low',
                default        => 'Critical',
            };

            $payload = [
                'steps'              => $steps,
                'watts'              => $watts,
                'voltage'            => $voltage,
                'battery_percentage' => $battery,
                'battery_health'     => $health,
            ];

            // ── POST /api/data ────────────────────────────────────────
            try {
                $res = Http::withHeaders($headers)->post("{$baseUrl}/api/data", $payload);

                $iteration++;
                $this->line(sprintf(
                    "[%s] #%d | Steps: %d | Watts: %.4f W | Voltage: %.3f V | Battery: %.2f%% (%s) → %s",
                    now()->format('H:i:s'),
                    $iteration,
                    $steps,
                    $watts,
                    $voltage,
                    $battery,
                    $health,
                    $res->json('status') ?? $res->body()
                ));

            } catch (\Exception $e) {
                $this->error("❌ Data push failed: " . $e->getMessage());
            }

            if ($count > 0 && $iteration >= $count) break;

            sleep($interval);
        }

        $this->newLine();
        $this->info("✅ Simulation complete.");
        return 0;
    }
}
