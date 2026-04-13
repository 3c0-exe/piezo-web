@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<style>
    .piezo-table-wrap {
        max-height: 420px;
        overflow-y: auto;
        overflow-x: auto;
    }
    .piezo-table-wrap thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #111827;
        box-shadow: inset 0 -1px 0 #1f2937;
    }
    .piezo-table-wrap::-webkit-scrollbar { width: 6px; height: 6px; }
    .piezo-table-wrap::-webkit-scrollbar-track { background: #111827; }
    .piezo-table-wrap::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
    .piezo-table-wrap::-webkit-scrollbar-thumb:hover { background: #4b5563; }
</style>
<div class="pt-6 space-y-6">

    {{-- ── Page header ──────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Session & Event Reports</h2>
            <p class="text-xs text-gray-500 mt-1">All recorded charging sessions and system events</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('reports.export.sessions') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold
                      bg-green-500/10 border border-green-500/25 text-green-400
                      hover:bg-green-500/20 hover:border-green-500/40 transition-all duration-150">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Sessions CSV
            </a>
            <a href="{{ route('reports.export.events') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold
                      bg-blue-500/10 border border-blue-500/25 text-blue-400
                      hover:bg-blue-500/20 hover:border-blue-500/40 transition-all duration-150">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Events CSV
            </a>
        </div>
    </div>

    {{-- ── 4 stat cards ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 leading-none">Total Sessions</p>
                <p class="text-2xl font-bold text-white mt-1 leading-none tabular-nums">{{ $sessions->total() }}</p>
            </div>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 leading-none">Completed</p>
                <p class="text-2xl font-bold text-emerald-400 mt-1 leading-none tabular-nums">
                    {{ \App\Models\ChargingSession::whereNotNull('ended_at')->count() }}
                </p>
            </div>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 leading-none">Overtime</p>
                <p class="text-2xl font-bold text-red-400 mt-1 leading-none tabular-nums">
                    {{ \App\Models\ChargingSession::where('flagged_overtime', true)->count() }}
                </p>
            </div>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 leading-none">Total Events</p>
                <p class="text-2xl font-bold text-blue-400 mt-1 leading-none tabular-nums">{{ $events->total() }}</p>
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- CHARGING SESSIONS                                               --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--
        Sticky thead fix:
        - The outer rounded-2xl card uses overflow-hidden to clip corners.
        - We add a max-h + overflow-y-auto wrapper INSIDE the card so the
          thead can be sticky relative to that scrolling ancestor.
        - overflow-x-auto is on a separate inner div so horizontal scroll
          still works independently.
    --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">

        {{-- Card header + rows-per-page control --}}
        <div class="px-5 py-3.5 border-b border-gray-800 flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                <h3 class="text-sm font-semibold text-white">Charging Sessions</h3>
            </div>
            <div class="flex items-center gap-3">
                {{-- Rows per page dropdown --}}
                <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-1.5">
                    {{-- Preserve the events table's current per-page and page --}}
                    <input type="hidden" name="events_per_page" value="{{ $eventsPerPage }}">
                    <input type="hidden" name="events_page"     value="{{ request('events_page', 1) }}">
                    <label for="sessions_per_page" class="text-xs text-gray-500 whitespace-nowrap">Rows:</label>
                    <select id="sessions_per_page" name="sessions_per_page" onchange="this.form.submit()"
                            class="bg-gray-800 border border-gray-700 text-gray-300 text-xs rounded-md
                                   px-2 py-1 focus:outline-none focus:border-green-500/50 cursor-pointer">
                        @foreach ([10, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ $sessionsPerPage === $opt ? 'selected' : '' }}>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <span class="text-xs text-gray-600 tabular-nums">
                    {{ $sessions->total() }} total &middot; page {{ $sessions->currentPage() }}/{{ $sessions->lastPage() }}
                </span>
            </div>
        </div>

        @if ($sessions->isEmpty())
            <div class="py-14 text-center">
                <div class="w-10 h-10 rounded-xl bg-gray-800 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-400">No sessions recorded yet</p>
                <p class="text-xs text-gray-600 mt-1">Start tracking on the Dashboard to generate data.</p>
            </div>
        @else
            {{--
                max-h caps the table body region so it scrolls vertically.
                overflow-y-auto enables vertical scroll — thead sticky works relative to this.
                overflow-x-auto on the inner div handles wide tables.
            --}}
            <div class="piezo-table-wrap">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Started</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Ended</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Steps</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Peak W</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Batt. Start</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Batt. End</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Added</th>
                            <th class="text-center px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        @foreach ($sessions as $session)
                            @php
                                $isActive   = is_null($session->ended_at);
                                $isOvertime = $session->flagged_overtime;
                                $added      = $session->capacity_added;
                            @endphp
                            <tr class="hover:bg-gray-800/25 transition-colors duration-100
                                       {{ $isOvertime ? 'border-l-2 border-l-red-500/40' : '' }}">

                                <td class="px-5 py-2.5">
                                    @if ($session->student)
                                        <p class="text-sm font-semibold text-white leading-tight">{{ $session->student->name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $session->student->student_id }} &middot; {{ $session->student->section }}
                                        </p>
                                    @else
                                        <span class="text-xs text-gray-600 italic">Deleted student</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <p class="text-xs text-gray-300">{{ $session->started_at?->format('M d, Y') }}</p>
                                    <p class="text-xs text-gray-600 font-mono mt-0.5">{{ $session->started_at?->format('H:i:s') }}</p>
                                </td>

                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if ($session->ended_at)
                                        <p class="text-xs text-gray-300">{{ $session->ended_at->format('M d, Y') }}</p>
                                        <p class="text-xs text-gray-600 font-mono mt-0.5">{{ $session->ended_at->format('H:i:s') }}</p>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-yellow-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span>
                                            In progress
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5">
                                    @if ($isActive)
                                        <span class="text-xs text-gray-700">—</span>
                                    @else
                                        <span class="text-xs font-mono text-gray-300">{{ $session->durationFormatted() }}</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    <span class="text-xs font-mono text-gray-300">{{ number_format($session->total_steps) }}</span>
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    <span class="text-xs font-mono text-gray-300">{{ number_format($session->peak_watts, 2) }}W</span>
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    <span class="text-xs font-mono text-gray-300">{{ number_format($session->battery_start, 1) }}%</span>
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    <span class="text-xs font-mono text-gray-300">{{ number_format($session->battery_end, 1) }}%</span>
                                </td>

                                <td class="px-4 py-2.5 text-right">
                                    @if ($isActive)
                                        <span class="text-xs text-gray-700">—</span>
                                    @else
                                        <span class="text-xs font-mono font-semibold
                                            {{ $added > 0 ? 'text-emerald-400' : ($added < 0 ? 'text-red-400' : 'text-gray-500') }}">
                                            {{ $added >= 0 ? '+' : '' }}{{ number_format($added, 1) }}%
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 text-center">
                                    @if ($isActive)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                     bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">Active</span>
                                    @elseif ($isOvertime)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                     bg-red-500/10 text-red-400 border border-red-500/20">Overtime</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                     bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Done</span>
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sessions->hasPages())
                <div class="px-5 py-3 border-t border-gray-800 flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-600 tabular-nums">
                        Showing {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }}
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($sessions->onFirstPage())
                            <span class="px-2.5 py-1 rounded-md text-xs text-gray-700 cursor-not-allowed select-none">← Prev</span>
                        @else
                            <a href="{{ $sessions->appends(request()->query())->previousPageUrl() }}"
                               class="px-2.5 py-1 rounded-md text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white transition">← Prev</a>
                        @endif

                        @php
                            $sC = $sessions->currentPage(); $sL = $sessions->lastPage();
                            $sS = max(1, $sC - 2); $sE = min($sL, $sS + 4); $sS = max(1, $sE - 4);
                        @endphp
                        @for ($p = $sS; $p <= $sE; $p++)
                            @if ($p === $sC)
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-green-500/20 text-green-400 border border-green-500/30 select-none">{{ $p }}</span>
                            @else
                                <a href="{{ $sessions->appends(request()->query())->url($p) }}"
                                   class="px-2.5 py-1 rounded-md text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white transition">{{ $p }}</a>
                            @endif
                        @endfor

                        @if ($sessions->hasMorePages())
                            <a href="{{ $sessions->appends(request()->query())->nextPageUrl() }}"
                               class="px-2.5 py-1 rounded-md text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white transition">Next →</a>
                        @else
                            <span class="px-2.5 py-1 rounded-md text-xs text-gray-700 cursor-not-allowed select-none">Next →</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- EVENT LOG                                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">

        <div class="px-5 py-3.5 border-b border-gray-800 flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                <h3 class="text-sm font-semibold text-white">Event Log</h3>
            </div>
            <div class="flex items-center gap-3">
                {{-- Rows per page dropdown --}}
                <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-1.5">
                    {{-- Preserve the sessions table's current per-page and page --}}
                    <input type="hidden" name="sessions_per_page" value="{{ $sessionsPerPage }}">
                    <input type="hidden" name="sessions_page"     value="{{ request('sessions_page', 1) }}">
                    <label for="events_per_page" class="text-xs text-gray-500 whitespace-nowrap">Rows:</label>
                    <select id="events_per_page" name="events_per_page" onchange="this.form.submit()"
                            class="bg-gray-800 border border-gray-700 text-gray-300 text-xs rounded-md
                                   px-2 py-1 focus:outline-none focus:border-blue-500/50 cursor-pointer">
                        @foreach ([10, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ $eventsPerPage === $opt ? 'selected' : '' }}>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <span class="text-xs text-gray-600 tabular-nums">
                    {{ $events->total() }} total &middot; page {{ $events->currentPage() }}/{{ $events->lastPage() }}
                </span>
            </div>
        </div>

        @if ($events->isEmpty())
            <div class="py-14 text-center">
                <div class="w-10 h-10 rounded-xl bg-gray-800 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-400">No events logged yet</p>
                <p class="text-xs text-gray-600 mt-1">System events will appear here as the dashboard is used.</p>
            </div>
        @else
            <div class="piezo-table-wrap">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-800">
                            <th class="text-left px-5 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">#</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Event Type</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Meta</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Occurred At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @foreach ($events as $event)
                            <tr class="hover:bg-gray-800/25 transition-colors duration-100 group">

                                <td class="px-5 py-2.5 text-xs font-mono text-gray-600 group-hover:text-gray-500 transition-colors">
                                    {{ $event->id }}
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border
                                                 {{ $event->eventTypeBadgeClass() }}">
                                        {{ $event->eventTypeLabel() }}
                                    </span>
                                </td>

                                <td class="px-4 py-2.5">
                                    <span class="text-xs text-gray-300">{{ $event->description }}</span>
                                </td>

                                <td class="px-4 py-2.5">
                                    @if ($event->meta)
                                        <details>
                                            <summary class="text-xs text-blue-400 hover:text-blue-300 cursor-pointer
                                                           list-none inline-flex items-center gap-1 select-none">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                </svg>
                                                View
                                            </summary>
                                            <pre class="mt-2 text-xs text-gray-400 bg-gray-800/80 border border-gray-700
                                                        rounded-lg p-2.5 max-w-xs overflow-x-auto whitespace-pre-wrap break-all">{{ json_encode($event->meta, JSON_PRETTY_PRINT) }}</pre>
                                        </details>
                                    @else
                                        <span class="text-xs text-gray-700">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <p class="text-xs text-gray-300">{{ $event->occurred_at?->format('M d, Y') }}</p>
                                    <p class="text-xs font-mono text-gray-600 mt-0.5">{{ $event->occurred_at?->format('H:i:s') }}</p>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($events->hasPages())
                <div class="px-5 py-3 border-t border-gray-800 flex items-center justify-between gap-4">
                    <p class="text-xs text-gray-600 tabular-nums">
                        Showing {{ $events->firstItem() }}–{{ $events->lastItem() }} of {{ $events->total() }}
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($events->onFirstPage())
                            <span class="px-2.5 py-1 rounded-md text-xs text-gray-700 cursor-not-allowed select-none">← Prev</span>
                        @else
                            <a href="{{ $events->appends(request()->query())->previousPageUrl() }}"
                               class="px-2.5 py-1 rounded-md text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white transition">← Prev</a>
                        @endif

                        @php
                            $eC = $events->currentPage(); $eL = $events->lastPage();
                            $eS = max(1, $eC - 2); $eE = min($eL, $eS + 4); $eS = max(1, $eE - 4);
                        @endphp
                        @for ($p = $eS; $p <= $eE; $p++)
                            @if ($p === $eC)
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-500/20 text-blue-400 border border-blue-500/30 select-none">{{ $p }}</span>
                            @else
                                <a href="{{ $events->appends(request()->query())->url($p) }}"
                                   class="px-2.5 py-1 rounded-md text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white transition">{{ $p }}</a>
                            @endif
                        @endfor

                        @if ($events->hasMorePages())
                            <a href="{{ $events->appends(request()->query())->nextPageUrl() }}"
                               class="px-2.5 py-1 rounded-md text-xs text-gray-400 bg-gray-800 hover:bg-gray-700 hover:text-white transition">Next →</a>
                        @else
                            <span class="px-2.5 py-1 rounded-md text-xs text-gray-700 cursor-not-allowed select-none">Next →</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
