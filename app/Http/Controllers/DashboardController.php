<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\EnergyLog;
use App\Models\EventLog;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Services\MqttService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected MqttService $mqtt) {}

    public function index(): View
    {
        $settings = SystemSetting::current()->load('activeStudent');
        $students = Student::orderBy('name')->get();

        return view('dashboard.index', compact('settings', 'students'));
    }

    public function toggleTracking(Request $request): RedirectResponse
    {
        $settings = SystemSetting::current()->load('activeStudent');

        if ($settings->is_tracking_on) {
            // ── Stop tracking ────────────────────────────────────────
            $elapsed = $settings->tracking_started_at
                ? now()->diffInSeconds($settings->tracking_started_at)
                : 0;

            if ($settings->active_student_id) {
                $session = ChargingSession::where('student_id', $settings->active_student_id)
                    ->whereNull('ended_at')
                    ->latest('started_at')
                    ->first();

                if ($session) {
                    $firstLog = EnergyLog::where('student_id', $settings->active_student_id)
                        ->where('logged_at', '>=', $session->started_at)
                        ->orderBy('logged_at')
                        ->first();

                    $lastLog = EnergyLog::where('student_id', $settings->active_student_id)
                        ->where('logged_at', '>=', $session->started_at)
                        ->orderByDesc('logged_at')
                        ->first();

                    $peakWatts = EnergyLog::where('student_id', $settings->active_student_id)
                        ->where('logged_at', '>=', $session->started_at)
                        ->max('watts') ?? 0;

                    $session->update([
                        'ended_at'         => now(),
                        'duration_seconds' => $elapsed,
                        'battery_start'    => $firstLog?->battery_percentage ?? 0,
                        'battery_end'      => $lastLog?->battery_percentage ?? 0,
                        'capacity_added'   => ($lastLog?->battery_percentage ?? 0) - ($firstLog?->battery_percentage ?? 0),
                        'total_steps'      => $lastLog?->steps ?? 0,
                        'peak_watts'       => $peakWatts,
                        'flagged_overtime' => $elapsed > 1200,
                    ]);

                    EventLog::record('session_completed', 'Charging session completed.', [
                        'student_id'       => $settings->active_student_id,
                        'duration_seconds' => $elapsed,
                    ]);
                }
            }

            $settings->update([
                'is_tracking_on'      => false,
                'tracking_started_at' => null,
            ]);

            $this->mqtt->publish('piezo/command', [
                'tracking_on'  => false,
                'student_id'   => null,
                'student_name' => null,
            ], retain: true);

            EventLog::record('tracking_stopped', 'Tracking was stopped by admin.', [
                'elapsed_seconds' => $elapsed,
            ]);

            return back()->with('success', 'Tracking stopped and session saved.');

        } else {
            // ── Start tracking ───────────────────────────────────────
            if (! $settings->active_student_id) {
                return back()->with('error', 'Please select an active student before starting tracking.');
            }

            $settings->update([
                'is_tracking_on'      => true,
                'tracking_started_at' => now(),
            ]);

            ChargingSession::create([
                'student_id' => $settings->active_student_id,
                'started_at' => now(),
            ]);

            $this->mqtt->publish('piezo/command', [
                'tracking_on'  => true,
                'student_id'   => $settings->activeStudent->student_id,
                'student_name' => $settings->activeStudent->name,
            ], retain: true);

            EventLog::record('tracking_started', 'Tracking was started by admin.', [
                'student_id' => $settings->active_student_id,
            ]);

            return back()->with('success', 'Tracking started.');
        }
    }

    public function setActiveStudent(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($request->student_id);

        $settings = SystemSetting::current();
        $settings->update([
            'active_student_id' => $student->id,
        ]);

        if ($settings->is_tracking_on) {
            $this->mqtt->publish('piezo/command', [
                'tracking_on'  => true,
                'student_id'   => $student->student_id,
                'student_name' => $student->name,
            ], retain: true);
        }

        EventLog::record('student_assigned', "Active student set to {$student->name}.", [
            'student_id'   => $student->id,
            'student_name' => $student->name,
        ]);

        return back()->with('success', "Active student set to {$student->name}.");
    }
}
