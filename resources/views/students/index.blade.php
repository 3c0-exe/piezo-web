@extends('layouts.app')

@section('title', 'Students — Piezo')
@section('page-title', 'Student Records')

@section('content')
<div class="mt-6 space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-sm text-gray-400">
            {{ $students->count() }} student{{ $students->count() !== 1 ? 's' : '' }} have used the system
        </p>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">

        @if ($students->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <svg class="w-12 h-12 text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p class="text-gray-400 font-medium">No students yet</p>
                <p class="text-gray-600 text-sm mt-1">Students appear here after their first QR scan.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[600px]">
                    <thead>
                        <tr class="border-b border-gray-800">
                            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="text-right px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Sessions</th>
                            <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Session</th>
                            <th class="text-center px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach ($students as $student)
                            <tr class="hover:bg-gray-800/40 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-green-500/10 border border-green-500/20
                                                    flex items-center justify-center shrink-0">
                                            <span class="text-xs font-bold text-green-400">
                                                {{ strtoupper(substr($student->student_name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <span class="font-medium text-white">{{ $student->student_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-400">{{ $student->student_email }}</td>
                                <td class="px-6 py-4 text-right font-mono text-gray-300">{{ $student->session_count }}</td>
                                <td class="px-6 py-4 text-gray-400 text-xs">
                                    {{ $student->last_session ? \Carbon\Carbon::parse($student->last_session)->format('M d, Y h:i A') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if ($student->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                                     text-xs font-semibold bg-green-500/15 text-green-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                            Charging
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full
                                                     text-xs font-semibold bg-gray-800 text-gray-500">
                                            Idle
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection