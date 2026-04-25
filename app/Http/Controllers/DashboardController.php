<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\EventLog;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected MqttService $mqtt) {}

    public function index(): View
    {
        $settings      = SystemSetting::current();
        $activeSession = ChargingSession::whereNull('ended_at')
            ->latest('started_at')
            ->first();

        return view('dashboard.index', compact('settings', 'activeSession'));
    }

public function stopSession(): RedirectResponse
{
    $settings      = SystemSetting::current();
    $activeSession = ChargingSession::whereNull('ended_at')
        ->latest('started_at')
        ->first();

    if (! $activeSession) {
        return back()->with('error', 'No active session to stop.');
    }

    try {
        $elapsed = now()->diffInSeconds($activeSession->started_at);

        $logs        = EnergyLog::where('student_email', $activeSession->student_email)
            ->where('logged_at', '>=', $activeSession->started_at)
            ->get();

        $peakWatts   = $logs->max('watts')               ?? 0;
        $peakVoltage = $logs->max('voltage')              ?? 0;
        $totalSteps  = $logs->last()?->steps              ?? 0;
        $batteryEnd  = $logs->last()?->battery_percentage ?? null;

        $activeSession->update([
            'ended_at'         => now(),
            'total_steps'      => $totalSteps,
            'peak_watts'       => $peakWatts,
            'peak_voltage'     => $peakVoltage,
            'battery_end'      => $batteryEnd,
            'flagged_overtime' => $elapsed > 1200,
        ]);

        $settings->update([
            'is_tracking_on'       => false,
            'active_student_name'  => null,
            'active_student_email' => null,
            'tracking_started_at'  => null,
        ]);

        $this->mqtt->publish('piezo/command', [
            'tracking_on'  => false,
            'student_name' => '',
        ], retain: true);

return back()->with('success', 'Session stopped successfully.');

        return back()->with('success', 'Session stopped successfully.');

    } catch (\Throwable $e) {
        dd($e->getMessage(), $e->getTraceAsString());
    }
}
}