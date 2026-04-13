<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Piezo Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full bg-gray-950 text-white flex">

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <aside class="w-64 shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col h-screen sticky top-0">

        {{-- Brand --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-800">
            <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-green-500/10 border border-green-500/30">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-white leading-none">Piezo Dashboard</p>
                <p class="text-xs text-gray-500 mt-0.5">Energy Monitor</p>
            </div>
        </div>

        {{-- ESP32 connection indicator --}}
        <div class="px-6 py-3 border-b border-gray-800">
            <div class="flex items-center gap-2">
                <span id="esp-dot" class="w-2 h-2 rounded-full bg-gray-600"></span>
                <span id="esp-label" class="text-xs text-gray-500">ESP32 Disconnected</span>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('dashboard') ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <a href="{{ route('students.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('students.*') ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Students
            </a>

            <a href="{{ route('reports.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('reports.*') ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Reports
            </a>

        </nav>

        {{-- User / Logout --}}
        <div class="px-3 py-4 border-t border-gray-800">
            <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-gray-800/50 mb-2">
                <div class="w-7 h-7 rounded-full bg-green-500/20 flex items-center justify-center shrink-0">
                    <span class="text-xs font-bold text-green-400">
                        {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-white truncate">{{ Auth::user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-gray-400
                               hover:bg-gray-800 hover:text-white transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Sign out
                </button>
            </form>
        </div>

    </aside>

    {{-- ── Main content ─────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-h-screen min-w-0">

        {{-- Top bar --}}
        <header class="h-14 shrink-0 bg-gray-900/80 backdrop-blur border-b border-gray-800
                        flex items-center justify-between px-6 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-white">@yield('page-title', 'Dashboard')</h1>
            <span class="text-xs text-gray-500">{{ now()->format('l, F j Y') }}</span>
        </header>

        {{-- Flash messages --}}
        <div class="px-6 pt-4">
            @if (session('success'))
                <div class="mb-4 p-3 rounded-lg bg-green-500/10 border border-green-500/30 text-green-400 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 px-6 pb-8">
            @yield('content')
        </main>

    </div>

    {{-- ── ESP32 connection dot script ──────────────────────────────── --}}
    <script>
        async function checkEspStatus() {
            try {
                const res  = await fetch('/api/dashboard-data');
                const data = await res.json();
                const dot  = document.getElementById('esp-dot');
                const lbl  = document.getElementById('esp-label');

                if (data.latest_log) {
                    const lastSeen = new Date(data.latest_log.logged_at);
                    const ageSec   = (Date.now() - lastSeen.getTime()) / 1000;

                    if (ageSec < 15) {
                        dot.className = 'w-2 h-2 rounded-full bg-green-400 animate-pulse';
                        lbl.textContent = 'ESP32 Live';
                        lbl.className   = 'text-xs text-green-400';
                    } else {
                        dot.className = 'w-2 h-2 rounded-full bg-yellow-400';
                        lbl.textContent = 'ESP32 Idle';
                        lbl.className   = 'text-xs text-yellow-400';
                    }
                } else {
                    dot.className = 'w-2 h-2 rounded-full bg-gray-600';
                    lbl.textContent = 'No data yet';
                    lbl.className   = 'text-xs text-gray-500';
                }
            } catch (e) {
                // silent fail
            }
        }

        checkEspStatus();
        setInterval(checkEspStatus, 5000);
    </script>

    @stack('scripts')

</body>
</html>
