@extends('layouts.app')

@section('title', 'Dashboard — Piezo')
@section('page-title', 'Live Dashboard')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="mt-6 space-y-6" id="dashboard-root">

    {{-- ── Row 1: Session Status + Active Student ──────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Tracking Status --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-4">System Status</p>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-300 mb-1">Tracking Status</p>
                    <span id="tracking-badge"
                          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                 {{ $settings->is_tracking_on ? 'bg-green-500/15 text-green-400' : 'bg-gray-700 text-gray-400' }}">
                        <span class="w-1.5 h-1.5 rounded-full
                                     {{ $settings->is_tracking_on ? 'bg-green-400 animate-pulse' : 'bg-gray-500' }}"></span>
                        {{ $settings->is_tracking_on ? 'TRACKING ON' : 'TRACKING OFF' }}
                    </span>
                </div>
                <div class="text-xs text-gray-600 italic">Auto · QR controlled</div>
            </div>
        </div>

        {{-- Currently Charging --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-4">Currently Charging</p>
            <div id="currently-charging-content">
                @if ($activeSession)
                    <p class="text-sm font-semibold text-white">{{ $activeSession->student_name }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $activeSession->student_email }}</p>
                    <p class="text-xs text-gray-600 mt-1">
                        Started: <span class="text-gray-400">{{ $activeSession->started_at->format('h:i A') }}</span>
                    </p>
                    <form method="POST" action="{{ route('dashboard.stop') }}" class="mt-4">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Force stop this session?')"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-red-500/10 text-red-400
                                       border border-red-500/30 hover:bg-red-500/20 transition">
                            Force Stop Session
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-600 italic">No active session — waiting for QR scan.</p>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Row 2: Session Timer + 4 Metric Cards ────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 sm:gap-6">

        {{-- Session Timer --}}
        <div class="col-span-2 sm:col-span-1 md:col-span-1 bg-gray-900 border border-gray-800 rounded-2xl p-6 flex flex-col justify-between">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Session Time</p>
            <div>
                <p id="session-timer"
                   class="font-mono text-3xl font-bold text-white tracking-tight">
                    {{ $settings->is_tracking_on && $settings->tracking_started_at
                        ? gmdate('i:s', max(0, 1200 - now()->diffInSeconds($settings->tracking_started_at)))
                        : '20:00' }}
                </p>
                <p id="overtime-label" class="text-xs mt-1 {{ $settings->is_tracking_on ? 'text-gray-500' : 'text-gray-600' }}">
                    20:00 limit
                </p>
            </div>
        </div>

        {{-- Steps --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Steps to Full</p>
            <p id="val-steps-to-full" class="font-mono text-2xl font-bold text-white">—</p>
            <p class="text-xs text-gray-600 mt-1">
                est. steps remaining &nbsp;·&nbsp;
                <span id="val-steps" class="font-mono text-gray-500 text-xs"></span> taken
            </p>
        </div>

        {{-- Watts --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Power</p>
            <p id="val-watts" class="font-mono text-2xl font-bold text-white">—</p>
            <p class="text-xs text-gray-600 mt-1">watts output</p>
        </div>

        {{-- Voltage --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Voltage</p>
            <p id="val-voltage" class="font-mono text-2xl font-bold text-white">—</p>
            <p class="text-xs text-gray-600 mt-1">volts</p>
        </div>

        {{-- Last Updated --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Last Update</p>
            <p id="val-updated" class="font-mono text-2xl font-bold text-white">—</p>
            <p class="text-xs text-gray-600 mt-1">seconds ago</p>
        </div>

    </div>

    {{-- ── Row 3: Battery Gauge + Analytics ─────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Battery SVG Arc Gauge --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-4">Battery Level</p>

            <div class="flex flex-col items-center">
                <svg viewBox="0 0 200 120" class="w-56 h-auto" xmlns="http://www.w3.org/2000/svg">
                    <path d="M 20 110 A 80 80 0 0 1 180 110"
                          fill="none" stroke="#1f2937" stroke-width="16"
                          stroke-linecap="round"/>
                    <path id="battery-arc"
                          d="M 20 110 A 80 80 0 0 1 180 110"
                          fill="none" stroke="#4ade80" stroke-width="16"
                          stroke-linecap="round"
                          stroke-dasharray="251.2"
                          stroke-dashoffset="251.2"
                          style="transition: stroke-dashoffset 0.8s ease, stroke 0.4s ease"/>
                    <text id="battery-pct-text"
                          x="100" y="95"
                          text-anchor="middle"
                          font-family="monospace"
                          font-size="26"
                          font-weight="bold"
                          fill="white">—%</text>
                    <path id="battery-arc-pulse"
                          d="M 20 110 A 80 80 0 0 1 180 110"
                          fill="none" stroke="#4ade80" stroke-width="16"
                          stroke-linecap="round"
                          stroke-dasharray="251.2"
                          stroke-dashoffset="251.2"
                          opacity="0"
                          style="transition: stroke-dashoffset 0.4s ease, opacity 0.3s ease"/>
                </svg>

                <span id="battery-health-badge"
                      class="mt-2 px-3 py-1 rounded-full text-xs font-semibold bg-gray-800 text-gray-400">
                    No Data
                </span>

                <span id="charging-label"
                      class="mt-2 px-3 py-1 rounded-full text-xs font-semibold
                             bg-green-500/15 text-green-400 border border-green-500/30
                             flex items-center gap-1.5 opacity-0 transition-opacity duration-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    <span id="charging-source-label">Now Charging</span>
                </span>

                <div class="w-full mt-4">
                    <div class="h-2 rounded-full bg-gray-800 overflow-hidden">
                        <div id="battery-bar"
                             class="h-full rounded-full bg-green-400 transition-all duration-700"
                             style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charging Analytics --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-4">Charging Analytics</p>

            <div class="space-y-4">

                {{-- Total Energy Harvested --}}
                <div class="flex items-center justify-between py-3 border-b border-gray-800">
                    <div>
                        <p class="text-sm text-gray-300 font-medium">Energy Harvested</p>
                        <p class="text-xs text-gray-600">total watt-hours this session</p>
                    </div>
                    <p id="val-energy" class="font-mono text-xl font-bold text-white">— Wh</p>
                </div>

                {{-- Total Sessions Today --}}
                <div class="flex items-center justify-between py-3 border-b border-gray-800">
                    <div>
                        <p class="text-sm text-gray-300 font-medium">Sessions Today</p>
                        <p class="text-xs text-gray-600">charging sessions started</p>
                    </div>
                    <p id="val-sessions-today" class="font-mono text-xl font-bold text-white">—</p>
                </div>

                {{-- ETA to Full --}}
                <div class="flex items-center justify-between py-3 border-b border-gray-800">
                    <div>
                        <p class="text-sm text-gray-300 font-medium">ETA to Full</p>
                        <p id="val-eta-label" class="text-xs text-gray-600">estimated time remaining</p>
                    </div>
                    <p id="val-eta" class="font-mono text-xl font-bold text-white">—</p>
                </div>

                {{-- Active Student --}}
                <div class="flex items-center justify-between py-3">
                    <div>
                        <p class="text-sm text-gray-300 font-medium">Charging For</p>
                        <p class="text-xs text-gray-600">active student</p>
                    </div>
                    <div class="text-right max-w-40">
                        <p id="val-student" class="font-mono text-sm font-bold text-green-400 truncate">—</p>
                        <p id="val-student-email" class="text-xs text-gray-600 truncate">—</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection
@push('scripts')
<script>
(function () {

    // ── Timer state ─────────────────────────────────────────────────
    // Never initialized from $settings — SystemSetting can be out of sync
    // with charging_sessions. The first poll (fires immediately) sets these
    // correctly from the API's started_at_ms field.
    let isTracking           = false;
    let anchoredSessionStart = null; // ms timestamp, set once per session, never drifts

    // ── Session timer ───────────────────────────────────────────────
    const timerEl    = document.getElementById('session-timer');
    const overtimeEl = document.getElementById('overtime-label');

    function updateTimer() {
        if (! isTracking || ! anchoredSessionStart) {
            timerEl.textContent    = '20:00';
            timerEl.classList.remove('text-red-400');
            timerEl.classList.add('text-white');
            overtimeEl.textContent = '20:00 limit';
            overtimeEl.className   = 'text-xs mt-1 text-gray-600';
            return;
        }

        const elapsed   = Math.max(0, Math.floor((Date.now() - anchoredSessionStart) / 1000));
        const remaining = Math.max(0, 1200 - elapsed);
        const mins      = Math.floor(remaining / 60);
        const secs      = remaining % 60;
        timerEl.textContent = `${String(mins).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;

        if (elapsed > 1200) {
            timerEl.classList.add('text-red-400');
            timerEl.classList.remove('text-white');
            overtimeEl.textContent = '⚠ OVERTIME';
            overtimeEl.className   = 'text-xs mt-1 text-red-400 font-semibold animate-pulse';
        } else {
            timerEl.classList.remove('text-red-400');
            timerEl.classList.add('text-white');
            overtimeEl.textContent = '20:00 limit';
            overtimeEl.className   = 'text-xs mt-1 text-gray-500';
        }
    }

    setInterval(updateTimer, 1000);
    updateTimer();

    // Instantly correct the timer when user switches back to this tab
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') updateTimer();
    });

    // ── Charging signal ─────────────────────────────────────────────
    let chargingPulseTimeout = null;

    function setChargingSignal(isCharging, currentPct, chargingSource) {
        const arc         = document.getElementById('battery-arc');
        const pulseArc    = document.getElementById('battery-arc-pulse');
        const label       = document.getElementById('charging-label');
        const sourceLabel = document.getElementById('charging-source-label');

        if (isCharging) {
            sourceLabel.textContent = chargingSource === 'piezo' ? 'Now Charging · Piezoelectric'
                                    : chargingSource === 'ac'    ? 'Now Charging · AC'
                                    :                              'Now Charging';
            label.style.opacity = '1';
            arc.style.filter    = 'drop-shadow(0 0 8px currentColor)';

            const realOffset = 251.2 - (251.2 * currentPct / 100);
            pulseArc.style.stroke     = arc.style.stroke;
            pulseArc.style.opacity    = '0.35';
            pulseArc.style.transition = 'none';
            pulseArc.style.strokeDashoffset = String(realOffset);

            clearTimeout(chargingPulseTimeout);
            chargingPulseTimeout = setTimeout(() => {
                pulseArc.style.transition       = 'stroke-dashoffset 2.5s ease-in-out, opacity 0.5s ease';
                pulseArc.style.strokeDashoffset = '0';
                chargingPulseTimeout = setTimeout(() => {
                    pulseArc.style.opacity = '0';
                }, 2000);
            }, 30);

        } else {
            chargingPulseTimeout = setTimeout(() => {
                label.style.opacity = '0';
                arc.style.filter    = 'none';
            }, 2500);
        }
    }

    // ── Battery arc helpers ─────────────────────────────────────────
    const ARC_LENGTH = 251.2;

    function setBattery(pct, health) {
        const arc   = document.getElementById('battery-arc');
        const text  = document.getElementById('battery-pct-text');
        const bar   = document.getElementById('battery-bar');
        const badge = document.getElementById('battery-health-badge');

        const offset = ARC_LENGTH - (ARC_LENGTH * pct / 100);
        arc.style.strokeDashoffset = offset;

        const colors = {
            Good:     '#4ade80',
            Fair:     '#facc15',
            Low:      '#fb923c',
            Critical: '#f87171',
        };
        const badgeClasses = {
            Good:     'bg-green-500/15 text-green-400',
            Fair:     'bg-yellow-500/15 text-yellow-400',
            Low:      'bg-orange-500/15 text-orange-400',
            Critical: 'bg-red-500/15 text-red-400',
        };

        const color = colors[health] ?? '#4ade80';
        arc.style.stroke          = color;
        bar.style.width           = pct + '%';
        bar.style.backgroundColor = color;
        text.textContent          = pct.toFixed(1) + '%';
        badge.textContent         = health ?? 'Unknown';
        badge.className           = 'mt-2 px-3 py-1 rounded-full text-xs font-semibold '
                                  + (badgeClasses[health] ?? 'bg-gray-800 text-gray-400');
    }

    // ── Update tracking badge ───────────────────────────────────────
    function updateTrackingBadge(trackingOn) {
        const badge = document.getElementById('tracking-badge');
        if (! badge) return;
        badge.className = `inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${
            trackingOn ? 'bg-green-500/15 text-green-400' : 'bg-gray-700 text-gray-400'
        }`;
        badge.innerHTML = `
            <span class="w-1.5 h-1.5 rounded-full ${trackingOn ? 'bg-green-400 animate-pulse' : 'bg-gray-500'}"></span>
            ${trackingOn ? 'TRACKING ON' : 'TRACKING OFF'}
        `;
    }

    // ── Update currently charging card ──────────────────────────────
    function updateChargingCard(activeStudent) {
        const card = document.getElementById('currently-charging-content');
        if (! card) return;

        if (activeStudent) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            card.innerHTML = `
                <p class="text-sm font-semibold text-white">${activeStudent.name}</p>
                <p class="text-xs text-gray-500 mt-1">${activeStudent.email}</p>
                <p class="text-xs text-gray-600 mt-1">Started: <span class="text-gray-400">${activeStudent.started_at ?? ''}</span></p>
                <form method="POST" action="/dashboard/stop" class="mt-4">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <button type="submit"
                            onclick="return confirm('Force stop this session?')"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-red-500/10 text-red-400
                                   border border-red-500/30 hover:bg-red-500/20 transition">
                        Force Stop Session
                    </button>
                </form>
            `;
        } else {
            card.innerHTML = `<p class="text-sm text-gray-600 italic">No active session — waiting for QR scan.</p>`;
        }
    }

    // ── Dashboard data poller ───────────────────────────────────────
    async function poll() {
        try {
            const res  = await fetch('/api/dashboard-data');
            if (! res.ok) return;
            const data = await res.json();

            // ── Sync timer anchor from canonical DB timestamp ─────
            // Use started_at_ms (exact unix ms from charging_sessions).
            // Only set the anchor once per session — never overwrite with
            // poll data to avoid drift. Reset to null when session ends.
            if (data.tracking_on && data.active_student?.started_at_ms) {
                const serverStart = data.active_student.started_at_ms;
                if (! anchoredSessionStart || Math.abs(serverStart - anchoredSessionStart) > 3000) {
                    // New session or anchor not yet set
                    anchoredSessionStart = serverStart;
                }
                isTracking = true;
            } else if (! data.tracking_on) {
                isTracking           = false;
                anchoredSessionStart = null;
            }

            // ── Update badge + charging card ──────────────────────
            updateTrackingBadge(data.tracking_on);
            updateChargingCard(data.active_student ?? null);

            // ── Live sensor readings ──────────────────────────────
            if (data.latest_log) {
                const log = data.latest_log;
                const stepsTaken = log.steps != null ? log.steps : 0;
                // Rough estimate: ~1 step per 0.03W, battery needs ~(100-pct) * someConstant
                // Simpler: steps taken so far / battery gained * remaining battery
                const pct = log.battery_percentage ?? 0;
                const STEPS_PER_PCT = 750;
                const totalNeeded   = Math.round(100 * STEPS_PER_PCT);
                const stepsToFull   = pct < 100
                    ? Math.max(0, totalNeeded - stepsTaken)
                    : 0;
                document.getElementById('val-steps').textContent        = stepsTaken.toLocaleString();
                document.getElementById('val-steps-to-full').textContent = stepsToFull != null ? stepsToFull.toLocaleString() : '—';
                document.getElementById('val-watts').textContent   = log.watts  != null ? log.watts.toFixed(4) + ' W'  : '0.0000 W';
                document.getElementById('val-voltage').textContent = log.voltage != null ? log.voltage.toFixed(3) + ' V' : '—';

                const ageSec = Math.floor((Date.now() - new Date(log.logged_at).getTime()) / 1000);
                document.getElementById('val-updated').textContent = ageSec + 's';

                setBattery(log.battery_percentage, log.battery_health);
                setChargingSignal(log.is_charging ?? false, log.battery_percentage, log.charging_source ?? null);
            }

            // ── Analytics ─────────────────────────────────────────
            const a = data.analytics;
            // ── ETA smart formatting ──────────────────────────────
            function formatEta(minutes) {
                if (minutes == null) return '—';
                const secs  = minutes * 60;
                if (secs < 60)                return Math.round(secs) + ' sec';
                if (minutes < 60)             return Math.round(minutes) + ' min';
                if (minutes < 1440)           return (minutes / 60).toFixed(1) + ' hrs';
                if (minutes < 10080)          return (minutes / 1440).toFixed(1) + ' days';
                if (minutes < 43200)          return (minutes / 10080).toFixed(1) + ' wks';
                return (minutes / 43200).toFixed(1) + ' mos';
            }

            document.getElementById('val-eta').textContent = formatEta(a?.eta_to_full_minutes ?? null);
document.getElementById('val-sessions-today').textContent = data.sessions_today ?? '0';
            // ── Energy harvested: watts × tick interval (3s) in Wh ─
            const watts     = data.latest_log?.watts ?? 0;
            const prevEnergy = parseFloat(document.getElementById('val-energy').dataset.wh ?? '0');
            const newEnergy  = prevEnergy + (watts * (3 / 3600));
            document.getElementById('val-energy').dataset.wh  = newEnergy;
            document.getElementById('val-energy').textContent = newEnergy.toFixed(4) + ' Wh';

            if (data.active_student) {
                document.getElementById('val-student').textContent       = data.active_student.name;
                document.getElementById('val-student-email').textContent = data.active_student.email;
            } else {
                document.getElementById('val-student').textContent       = '—';
                document.getElementById('val-student-email').textContent = '—';
            }

        } catch (e) {
            // silent fail — network blip, try again next tick
        }
    }

    poll();
    setInterval(poll, 3000);

})();
</script>
@endpush