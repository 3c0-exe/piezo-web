<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
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
}
