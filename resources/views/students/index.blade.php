@extends('layouts.app')

@section('title', 'Students — Piezo')
@section('page-title', 'Student Management')

@section('content')
<div class="mt-6 space-y-6">

    {{-- Header row --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-400">
                {{ $students->count() }} student{{ $students->count() !== 1 ? 's' : '' }} registered
            </p>
        </div>
        <a href="{{ route('students.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-green-500 hover:bg-green-400
                  text-gray-950 text-sm font-semibold transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Register Student
        </a>
    </div>

    {{-- Table card --}}
    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">

        @if ($students->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <svg class="w-12 h-12 text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p class="text-gray-400 font-medium">No students registered yet</p>
                <p class="text-gray-600 text-sm mt-1">Click "Register Student" to add one.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Student ID</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Year Level</th>
                        <th class="text-left px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5"></th>
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
                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <span class="font-medium text-white">{{ $student->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-gray-300">{{ $student->student_id }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ $student->section }}</td>
                            <td class="px-6 py-4 text-gray-400">{{ $student->year_level }}</td>
                            <td class="px-6 py-4">
                                @if ($activeStudentId === $student->id)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                                 text-xs font-semibold bg-green-500/15 text-green-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full
                                                 text-xs font-semibold bg-gray-800 text-gray-500">
                                        Idle
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST"
                                      action="{{ route('students.destroy', $student) }}"
                                      onsubmit="return confirm('Remove {{ addslashes($student->name) }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold
                                                   bg-red-500/10 border border-red-500/20 text-red-400
                                                   hover:bg-red-500/20 transition">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>

</div>
@endsection
