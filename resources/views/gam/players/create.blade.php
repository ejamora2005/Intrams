@extends('layouts.coordinator', ['title' => 'Add player', 'workspaceLabel' => 'GAM workspace', 'homeRoute' => 'gam.dashboard'])

@section('content')
    <x-ops-feedback />

    <section class="ops-hero p-5 sm:p-7 lg:p-8">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div class="min-w-0">
                <p class="ops-eyebrow">Roster management</p>
                <h1 class="ops-display mt-2">Add new player</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Register a player for <span class="font-semibold text-slate-800">{{ $team->name }}</span> in {{ $edition?->name ?? 'the active edition' }}. Every available sport is listed below; gender-matched events can be selected and dual events are shown for separate partner assignment.
                </p>
            </div>
            <a href="{{ route('gam.dashboard', ['team_id' => $team->id]) }}#roster-management" class="inline-flex w-fit items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">Back to roster</a>
        </div>
    </section>

    <form method="POST" action="{{ route('gam.teams.players.store', [$team, 'team_id' => $team->id, 'view' => 'roster-management']) }}" data-ops-form class="mt-6 space-y-6">
        @csrf

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <p class="font-semibold">Please review the player details.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Player details</h2>
                    <p class="mt-1 text-sm text-slate-500">Use the student’s official information so sports are matched to the correct gender category.</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">{{ $sportOptions->count() }} sports available</span>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="text-sm font-medium text-slate-700 sm:col-span-2 lg:col-span-1">Student number <span class="font-normal text-slate-400">(optional)</span>
                    <input name="student_number" value="{{ old('student_number') }}" placeholder="e.g. 2026-00001" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                </label>
                <label class="text-sm font-medium text-slate-700">First name <span class="text-red-600">*</span>
                    <input name="first_name" value="{{ old('first_name') }}" required class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                </label>
                <label class="text-sm font-medium text-slate-700">Middle name <span class="font-normal text-slate-400">(optional)</span>
                    <input name="middle_name" value="{{ old('middle_name') }}" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                </label>
                <label class="text-sm font-medium text-slate-700">Last name <span class="text-red-600">*</span>
                    <input name="last_name" value="{{ old('last_name') }}" required class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                </label>
                <label class="text-sm font-medium text-slate-700">Gender
                    <select name="gender" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Select gender</option>
                        @foreach (['Male', 'Female', 'LGBTQ+'] as $gender)
                            <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">Course
                    <select name="course_id" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Select course</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected(old('course_id', $team->course_id) == $course->id)>{{ $course->code }} — {{ $course->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">Year level
                    <select name="year_level" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Select year</option>
                        @foreach (['1st', '2nd', '3rd', '4th'] as $year)
                            <option value="{{ $year }}" @selected(old('year_level') === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">Section
                    <select name="section" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Select section</option>
                        @foreach (['A', 'B', 'C'] as $section)
                            <option value="{{ $section }}" @selected(old('section') === $section)>{{ $section }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Sports joined</h2>
                    <p class="mt-1 text-sm text-slate-500">All sports in the active edition are shown. Men’s and women’s events are enabled after gender is selected; dual events require partner assignment.</p>
                </div>
                <span class="text-xs font-medium text-slate-500">Faction: {{ $team->name }}</span>
            </div>
            <x-participation-sport-picker :sports="$sportOptions" :student-gender="old('gender')" :team-sport-note="'Team-sport selections are assigned to '.$team->name.'.'" />
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('gam.dashboard', ['team_id' => $team->id]) }}#roster-management" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</a>
            <button type="submit" data-loading-label="Adding player…" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Add player</button>
        </div>
    </form>
@endsection
