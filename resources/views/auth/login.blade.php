<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login — Piezo Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-950 flex items-center justify-center px-4">

    <div class="w-full max-w-md">

        {{-- Logo / Title --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-green-500/10 border border-green-500/30 mb-4">
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Piezo Dashboard</h1>
            <p class="text-gray-400 text-sm mt-1">Energy Harvesting Monitor</p>
        </div>

        {{-- Card --}}
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl">

            <h2 class="text-lg font-semibold text-white mb-6">Sign in to your account</h2>

            {{-- Error alert --}}
            @if ($errors->any())
                <div class="mb-5 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">
                        Email address
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="{{ old('email') }}"
                        class="w-full px-4 py-2.5 rounded-lg bg-gray-800 border border-gray-700
                               text-white placeholder-gray-500 text-sm
                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent
                               transition"
                        placeholder="admin@piezo.local"
                    />
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1.5">
                        Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full px-4 py-2.5 rounded-lg bg-gray-800 border border-gray-700
                               text-white placeholder-gray-500 text-sm
                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent
                               transition"
                        placeholder="••••••••"
                    />
                </div>

                {{-- Remember me --}}
                <div class="flex items-center">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-green-500
                               focus:ring-green-500 focus:ring-offset-gray-900"
                    />
                    <label for="remember" class="ml-2 text-sm text-gray-400">
                        Remember me
                    </label>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full py-2.5 px-4 rounded-lg bg-green-500 hover:bg-green-400
                           text-gray-950 font-semibold text-sm transition
                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2
                           focus:ring-offset-gray-900"
                >
                    Sign in
                </button>
            </form>
        </div>

        <p class="text-center text-gray-600 text-xs mt-6">
            Piezoelectric Tile Energy Harvesting Project &copy; {{ date('Y') }}
        </p>
    </div>

</body>
</html>
