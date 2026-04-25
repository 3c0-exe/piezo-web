<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piezo Charging — Session Started</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-sm text-center space-y-8">

        {{-- Success Icon --}}
        <div class="flex flex-col items-center gap-3">
            <div class="w-16 h-16 rounded-2xl bg-green-500/10 border border-green-500/30
                        flex items-center justify-center">
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor"
                     stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight">Session Started!</h1>
            <p class="text-sm text-gray-500">
                Your 20-minute charging session is now running.
                You can close this page.
            </p>
        </div>

        {{-- Session Info --}}
        @if (session('student_name'))
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 text-left space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Name</span>
                <span class="text-white font-medium">{{ session('student_name') }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Started</span>
                <span class="text-white font-medium">{{ session('started_at') }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Duration</span>
                <span class="text-green-400 font-medium">20 minutes</span>
            </div>
        </div>
        @endif

        {{-- Countdown --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <p class="text-xs text-gray-500 uppercase tracking-widest mb-2">Time Remaining</p>
            <p id="countdown" class="font-mono text-4xl font-bold text-green-400">20:00</p>
            <p class="text-xs text-gray-600 mt-2">Session ends automatically</p>
        </div>

        <p class="text-xs text-gray-600">You may close this page — your session will continue.</p>

    </div>

<script>
    const startedAt = {{ $activeSession ? $activeSession->started_at->timestamp * 1000 : 'Date.now()' }};
    const DURATION  = 20 * 60 * 1000;
    const el        = document.getElementById('countdown');

    function tick() {
        const remaining = Math.max(0, Math.floor((startedAt + DURATION - Date.now()) / 1000));
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        el.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;

        if (remaining <= 60) {
            el.classList.remove('text-green-400');
            el.classList.add('text-red-400');
        }

        if (remaining > 0) setTimeout(tick, 1000);
        else {
            el.classList.remove('text-green-400', 'text-red-400');
            el.classList.add('text-gray-500');
        }
    }

    tick();
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') tick();
    });
</script>

</body>
</html>