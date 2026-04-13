@extends('layouts.app')

@section('title', 'Register Student — Piezo')
@section('page-title', 'Register New Student')

@section('content')
<div class="mt-6 max-w-xl">

    {{-- Back link --}}
    <a href="{{ route('students.index') }}"
       class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Students
    </a>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8">

        <h2 class="text-base font-semibold text-white mb-6">Student Information</h2>

        <form method="POST" action="{{ route('students.store') }}" class="space-y-5">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-1.5">
                    Full Name <span class="text-red-400">*</span>
                </label>
                <input id="name" name="name" type="text"
                       value="{{ old('name') }}"
                       placeholder="e.g. Juan dela Cruz"
                       class="w-full px-4 py-2.5 rounded-xl bg-gray-800 border
                              {{ $errors->has('name') ? 'border-red-500' : 'border-gray-700' }}
                              text-white placeholder-gray-500 text-sm
                              focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                @error('name')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Student ID --}}
            <div>
                <label for="student_id" class="block text-sm font-medium text-gray-300 mb-1.5">
                    Student ID <span class="text-red-400">*</span>
                </label>
                <input id="student_id" name="student_id" type="text"
                       value="{{ old('student_id') }}"
                       placeholder="e.g. 2024-00004"
                       class="w-full px-4 py-2.5 rounded-xl bg-gray-800 border
                              {{ $errors->has('student_id') ? 'border-red-500' : 'border-gray-700' }}
                              text-white placeholder-gray-500 text-sm font-mono
                              focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                @error('student_id')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Section --}}
            <div>
                <label for="section" class="block text-sm font-medium text-gray-300 mb-1.5">
                    Section <span class="text-red-400">*</span>
                </label>
                <input id="section" name="section" type="text"
                       value="{{ old('section') }}"
                       placeholder="e.g. Sec-A"
                       class="w-full px-4 py-2.5 rounded-xl bg-gray-800 border
                              {{ $errors->has('section') ? 'border-red-500' : 'border-gray-700' }}
                              text-white placeholder-gray-500 text-sm
                              focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition" />
                @error('section')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Year Level --}}
            <div>
                <label for="year_level" class="block text-sm font-medium text-gray-300 mb-1.5">
                    Year Level <span class="text-red-400">*</span>
                </label>
                <select id="year_level" name="year_level"
                        class="w-full px-4 py-2.5 rounded-xl bg-gray-800 border
                               {{ $errors->has('year_level') ? 'border-red-500' : 'border-gray-700' }}
                               text-white text-sm
                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                    <option value="">— Select year level —</option>
                    @foreach (['Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'] as $level)
                        <option value="{{ $level }}" {{ old('year_level') === $level ? 'selected' : '' }}>
                            {{ $level }}
                        </option>
                    @endforeach
                </select>
                @error('year_level')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-green-500 hover:bg-green-400
                               text-gray-950 text-sm font-semibold transition">
                    Register Student
                </button>
                <a href="{{ route('students.index') }}"
                   class="px-6 py-2.5 rounded-xl border border-gray-700 text-gray-400
                          hover:text-white hover:border-gray-600 text-sm font-medium transition">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>
@endsection
