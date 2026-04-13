<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\EventLog;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Esp32Controller extends Controller
{
    /**
     * POST /api/data
     * Ingest sensor payload from the ESP32.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'steps'              => 'required|integer|min:0',
            'watts'              => 'required|numeric|min:0',
            'voltage'            => 'required|numeric|min:0',
            'battery_percentage' => 'required|numeric|min:0|max:100',
            'battery_health'     => 'required|in:Good,Fair,Low,Critical',
        ]);

        $settings = SystemSetting::current();

        // Silently ignore data when tracking is off
        if (! $settings->is_tracking_on) {
            return response()->json(['status' => 'ignored', 'reason' => 'tracking_off']);
        }

        $log = EnergyLog::create([
            'student_id'         => $settings->active_student_id,
            'steps'              => $validated['steps'],
            'watts'              => $validated['watts'],
            'voltage'            => $validated['voltage'],
            'battery_percentage' => $validated['battery_percentage'],
            'battery_health'     => $validated['battery_health'],
            'logged_at'          => now(),
        ]);

        // Check for session overtime and flag it
        if ($settings->tracking_started_at) {
            $elapsed = now()->diffInSeconds($settings->tracking_started_at);

            if ($elapsed > 1200) {
                // Update the open charging session if not already flagged
                ChargingSession::where('student_id', $settings->active_student_id)
                    ->whereNull('ended_at')
                    ->where('flagged_overtime', false)
                    ->update(['flagged_overtime' => true]);

                EventLog::record(
                    'session_overtime',
                    'Session exceeded 20 minutes.',
                    ['elapsed_seconds' => $elapsed]
                );
            }
        }

        EventLog::record(
            'data_received',
            'Sensor data received from ESP32.',
            [
                'steps'              => $validated['steps'],
                'watts'              => $validated['watts'],
                'battery_percentage' => $validated['battery_percentage'],
            ]
        );

        return response()->json(['status' => 'ok', 'log_id' => $log->id]);
    }

    /**
     * GET /api/command
     * ESP32 polls this to know whether to push data or stay idle.
     */
    public function command(): JsonResponse
    {
        $settings = SystemSetting::current()->load('activeStudent');

        return response()->json([
            'tracking_on'    => $settings->is_tracking_on,
            'active_student' => $settings->activeStudent
                ? [
                    'id'         => $settings->activeStudent->id,
                    'name'       => $settings->activeStudent->name,
                    'student_id' => $settings->activeStudent->student_id,
                ]
                : null,
        ]);
    }

    /**
     * POST /api/connect
     * ESP32 calls this once on boot to log its connection.
     */
    public function connect(Request $request): JsonResponse
    {
        EventLog::record(
            'esp32_connected',
            'ESP32 device connected to the server.',
            [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json(['status' => 'connected']);
    }
}
