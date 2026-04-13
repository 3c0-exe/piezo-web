<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EspApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('app.esp_api_key');
        $providedKey = $request->header('X-ESP-Key');

        if (empty($expectedKey) || $providedKey !== $expectedKey) {
            return response()->json([
                'error' => 'Unauthorized. Invalid or missing ESP API key.'
            ], 401);
        }

        return $next($request);
    }
}
