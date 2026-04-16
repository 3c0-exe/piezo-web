<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\SystemSetting;
use App\Models\EnergyLog;
use App\Models\SystemSetting;
use App\Models\EnergyLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Esp32Controller extends Controller
{
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

    public function ingest(Request $request): JsonResponse
    {
        $settings = SystemSetting::current();

        if (! $settings->is_tracking_on || ! $settings->active_student_id) {
            return response()->json(['status' => 'not_tracking']);
        }

        $validated = $request->validate([
            'steps'              => 'required|integer',
            'watts'              => 'required|numeric',
            'voltage'            => 'required|numeric',
            'battery_percentage' => 'required|numeric',
            'battery_health'     => 'nullable|string',
        ]);

        EnergyLog::create([
            ...$validated,
            'student_id' => $settings->active_student_id,
            'logged_at'  => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
