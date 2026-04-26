<?php

namespace App\Http\Controllers;

use App\Models\ChargingSession;
use App\Models\EventLog;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    // Fallback defaults when no query param is provided
    private const DEFAULT_SESSIONS_PER_PAGE = 10;
    private const DEFAULT_EVENTS_PER_PAGE   = 15;

    // Allowed values — prevents abuse via ?sessions_per_page=999999
    private const ALLOWED_PER_PAGE = [10, 25, 50, 100];

    /**
     * GET /reports
     */
    public function index(): View
    {
        $sessionsPerPage = $this->resolvePerPage('sessions_per_page', self::DEFAULT_SESSIONS_PER_PAGE);
        $eventsPerPage   = $this->resolvePerPage('events_per_page',   self::DEFAULT_EVENTS_PER_PAGE);

        $sessions = ChargingSession::orderByDesc('started_at')
            ->paginate($sessionsPerPage, ['*'], 'sessions_page');

        $events = EventLog::orderByDesc('occurred_at')
            ->paginate($eventsPerPage, ['*'], 'events_page');

        return view('reports.index', compact('sessions', 'events', 'sessionsPerPage', 'eventsPerPage'));
    }

    /**
     * GET /reports/export/sessions
     *
     * NOTE: Return type must be StreamedResponse, NOT Illuminate\Http\Response.
     * response()->stream() returns a StreamedResponse. Using the wrong type hint
     * causes a fatal TypeError before the download ever starts.
     */
    public function exportSessions(): StreamedResponse
    {
        $sessions = ChargingSession::orderByDesc('started_at')
            ->get();

        $filename = 'charging_sessions_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($sessions) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM — makes Excel auto-detect UTF-8 correctly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID', 'Student Name', 'Student Email',
                'Started At', 'Ended At', 'Duration',
                'Total Steps', 'Peak Watts', 'Peak Voltage',
                'Battery Start (%)', 'Battery End (%)', 'Overtime',
            ]);

            foreach ($sessions as $s) {
                fputcsv($handle, [
                    $s->id,
                    $s->student_name,
                    $s->student_email,
                    $s->started_at?->format('Y-m-d H:i:s') ?? '',
                    $s->ended_at?->format('Y-m-d H:i:s')   ?? '',
                    $s->durationFormatted(),
                    $s->total_steps,
                    number_format($s->peak_watts, 4),
                    number_format($s->peak_voltage, 4),
                    number_format($s->battery_start ?? 0, 2),
                    number_format($s->battery_end   ?? 0, 2),
                    $s->flagged_overtime ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * GET /reports/export/events
     */
    public function exportEvents(): StreamedResponse
    {
        $events = EventLog::orderByDesc('occurred_at')->get();

        $filename = 'event_logs_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($events) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Event Type', 'Description', 'Meta (JSON)', 'Occurred At']);

            foreach ($events as $e) {
                fputcsv($handle, [
                    $e->id,
                    $e->eventTypeLabel(),
                    $e->description,
                    $e->meta ? json_encode($e->meta) : '',
                    $e->occurred_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Resolve a per-page value from the request, clamped to allowed values.
     */
    private function resolvePerPage(string $param, int $default): int
    {
        $requested = (int) request($param, $default);
        return in_array($requested, self::ALLOWED_PER_PAGE, true) ? $requested : $default;
    }
}
