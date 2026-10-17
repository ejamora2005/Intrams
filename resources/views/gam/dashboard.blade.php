@extends('layouts.coordinator', ['title' => 'GAM dashboard', 'workspaceLabel' => 'GAM workspace', 'homeRoute' => 'gam.dashboard'])

@section('content')
    <x-ops-feedback />
    @if (request('modal'))
        <div data-ops-auto-modal="{{ request('modal') }}" hidden></div>
    @endif
    @if (session('ops_view'))
        <div data-ops-auto-view="{{ session('ops_view') }}" hidden></div>
    @endif

    <section data-ops-view-panel="dashboard-overview" class="ops-view-panel space-y-6">
        <div class="ops-hero p-5 sm:p-7 lg:p-8">
            <p class="ops-eyebrow">General Athletics Manager</p>
            <div class="mt-2 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                <div class="min-w-0">
                    <h1 class="ops-display">Teams and rosters</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ $edition?->name ?? 'No active intramurals edition is available yet.' }}</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('gam.rules.index') }}" class="inline-flex w-fit items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">Rules & Guidelines</a>
                        <a href="{{ route('gam.possible-dq.index') }}" class="inline-flex w-fit items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">Possible DQ Watchlist</a>
                    </div>
                    @if ($selectedTeam)
                        <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3 sm:min-w-72">
                            <p class="text-xs font-semibold uppercase text-slate-500">Current team</p>
                            <p class="mt-1 text-lg font-semibold text-slate-950">
                                <x-team-badge :team="$selectedTeam" size="md" />
                            </p>
                            <p class="mt-1 text-sm text-slate-500">{{ $selectedTeam->members_count ?? $selectedTeam->members->count() }} players</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <article class="ops-stat-card rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-4 lg:grid-cols-4">
            <a href="#roster-management" data-ops-view-shortcut="roster-management" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Roster management</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Assign players, add new students, and review Possible DQ roster flags.</p>
            </a>
            <a href="{{ route('gam.possible-dq.index') }}" class="ops-dashboard-card block rounded-lg border border-red-200 p-4 transition hover:border-red-300">
                <p class="text-sm font-semibold text-slate-950">Possible DQ watchlist</p>
                <p class="mt-2 text-2xl font-semibold text-red-700">{{ $flaggedStudents->count() }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Open the full list of players requiring registration review.</p>
            </a>
            <a href="#medical-certificates" data-ops-view-shortcut="medical-certificates" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Medical certificates</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Verify clearances for physical events before tabulation.</p>
            </a>
            <a href="#team-standings" data-ops-view-shortcut="team-standings" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Team standings</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Monitor current points, medals, and ranking position.</p>
            </a>
            <a href="#ready-matches" data-ops-view-shortcut="ready-matches" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Ready matches</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Check scheduled matchups that are ready for play.</p>
            </a>
        </section>
    </section>

    <section id="roster-management" data-ops-view-panel="roster-management" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-100 pb-4 lg:flex-row lg:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Roster management</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $hasAllFactionAccess ? 'This GAM account can manage all active factions.' : 'This GAM account can manage only its assigned faction.' }}
                </p>
            </div>
            @if ($teams->isNotEmpty())
                <form method="GET" action="{{ route('gam.dashboard') }}#roster-management" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <select name="team_id" class="rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()">
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected($selectedTeam?->id === $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Open</button>
                </form>
            @endif
        </div>

        @if ($selectedTeam)
            <div class="mt-5 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
                <div class="min-w-0">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                        <div>
                            <h3 class="font-semibold text-slate-950">
                                <x-team-badge :team="$selectedTeam" size="md" />
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $selectedTeam->course?->name ?? 'Unassigned department' }} / {{ $selectedTeam->members->count() }} players</p>
                        </div>
                    </div>
                    <div class="mt-4" data-list-search-container>
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <label class="relative block min-w-0 flex-1 sm:max-w-md">
                                <span class="sr-only">Search roster players</span>
                                <input type="search" data-list-search-input placeholder="Search player, ID, course, or sport" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
                            </label>
                            <span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-3">Player</th><th class="px-4 py-3">Student ID</th><th class="px-4 py-3">Course</th><th class="px-4 py-3">Year / Section</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Sports joined</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($selectedTeam->members as $member)
                                    @php
                                        $eligibility = $member->student ? $eligibilityByStudentId->get($member->student->id) : null;
                                    @endphp
                                    @php
                                        $joinedSportNames = $member->student?->athleteEntries
                                            ?->where('status', 'active')
                                            ?->filter(fn ($entry) => (int) $entry->editionSport?->edition_id === (int) $selectedTeam->edition_id)
                                            ?->map(fn ($entry) => $entry->editionSport?->sport?->name)
                                            ?->filter()
                                            ?->implode(' ') ?? '';
                                        $rosterSearchText = implode(' ', [
                                            $member->student?->full_name ?? 'Removed student',
                                            $member->student?->student_number ?? '',
                                            $member->student?->course?->code ?? '',
                                            trim(($member->student?->year_level ?? '').' '.($member->student?->section ?? '')),
                                            $joinedSportNames,
                                        ]);
                                    @endphp
                                    <tr data-list-search-row data-search-text="{{ Str::lower($rosterSearchText) }}" @class(['border-l-4 border-red-400 bg-red-950/45' => $eligibility['possible_dq'] ?? false])>
                                        <td class="px-4 py-3">
                                            <span class="block font-medium text-slate-900">{{ $member->student?->full_name ?? 'Removed student' }}</span>
                                            @if ($eligibility['possible_dq'] ?? false)
                                                <span class="mt-1 block text-xs font-medium text-red-200">{{ implode(' ', $eligibility['issues']) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $member->student?->student_number ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $member->student?->course?->code ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ trim(($member->student?->year_level ?? '').' '.($member->student?->section ?? '')) ?: '-' }}</td>
                                        <td class="px-4 py-3 min-w-32">
                                            @if ($eligibility['possible_dq'] ?? false)
                                                <span class="inline-flex w-max whitespace-nowrap rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-white">Possible DQ</span>
                                            @else
                                                <span class="inline-flex w-max whitespace-nowrap rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">Clear</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            @if ($member->student)
                                                @php
                                                    $selectedIds = $member->student->athleteEntries
                                                        ->where('status', 'active')
                                                        ->filter(fn ($entry) => (int) $entry->editionSport?->edition_id === (int) $selectedTeam->edition_id)
                                                        ->pluck('edition_sport_id')
                                                        ->map(fn ($id) => (string) $id);
                                                @endphp
                                                @php($editModalId = 'edit-sports-'.$member->student->id)
                                                @php
                                                    $editSportIds = collect(old('edition_sport_ids', $selectedIds->all()));
                                                @endphp
                                                <button type="button" data-ops-modal-open="{{ $editModalId }}" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">
                                                    <span>Edit sports</span>
                                                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px]">{{ $selectedIds->count() }}</span>
                                                </button>
                                                <dialog id="{{ $editModalId }}" data-ops-modal class="w-[min(42rem,calc(100vw-2rem))] rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60">
                                                    <form method="POST" action="{{ route('gam.teams.players.sports.update', [$selectedTeam, $member->student, 'team_id' => $selectedTeam->id, 'view' => 'roster-management', 'modal' => $editModalId]) }}" data-ops-form>
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                                                            <div>
                                                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Roster update</p>
                                                                <h3 class="mt-1 text-lg font-semibold">Edit sports for {{ $member->student->full_name }}</h3>
                                                                <p class="mt-1 text-sm text-slate-500">Choose the sports this player has joined. Gender-matched events are shown automatically.</p>
                                                            </div>
                                                            <button type="button" data-ops-modal-close class="rounded-lg p-2 text-xl leading-none text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close edit sports dialog">&times;</button>
                                                        </div>
                                                        <div class="max-h-[55vh] overflow-y-auto px-5 py-4" data-list-search-container>
                                                            <label class="relative block">
                                                                <span class="sr-only">Search sports for {{ $member->student->full_name }}</span>
                                                                <input type="search" data-list-search-input placeholder="Search sports" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                                                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
                                                            </label>
                                                            <p data-list-search-count class="mt-2 text-xs font-medium text-slate-500" aria-live="polite"></p>
                                                            <div class="mt-3 space-y-2">
                                                            @error('edition_sport_ids')
                                                                <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
                                                            @enderror
                                                            @forelse ($sportOptions->filter(fn ($option) => $option->sport?->acceptsStudentGender($member->student->gender)) as $option)
                                                                <label data-list-search-row data-search-text="{{ Str::lower($option->sport?->name ?? '') }}" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700 transition hover:border-blue-300 hover:bg-blue-50">
                                                                    <input type="checkbox" name="edition_sport_ids[]" value="{{ $option->id }}" @checked($editSportIds->contains((string) $option->id) || $editSportIds->contains((int) $option->id)) class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                                                    <span class="font-medium">{{ $option->sport?->name }}</span>
                                                                </label>
                                                            @empty
                                                                <p class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No gender-matched sports are available for this edition.</p>
                                                            @endforelse
                                                            <p data-list-search-empty hidden class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No sports match your search.</p>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                                                            <button type="button" data-ops-modal-close class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</button>
                                                            <button type="submit" data-loading-label="Saving sports…" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Save sports</button>
                                                        </div>
                                                    </form>
                                                </dialog>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No players assigned yet.</td></tr>
                                @endforelse
                                <tr data-list-search-empty hidden><td colspan="6" class="px-4 py-10 text-center text-slate-500">No roster players match your search.</td></tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <form method="POST" action="{{ route('gam.teams.players.assign', [$selectedTeam, 'team_id' => $selectedTeam->id, 'view' => 'roster-management']) }}" data-ops-form class="rounded-lg border border-slate-200 p-4">
                        @csrf
                        <h3 class="font-semibold text-slate-950">Assign existing player</h3>
                        <label class="mt-3 block text-xs font-medium text-slate-500">Filter available students
                            <input type="search" data-select-search="available-student-select" placeholder="Search by name or student ID" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                        </label>
                        <select id="available-student-select" name="student_id" required class="mt-2 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <option value="">Choose available student</option>
                            @foreach ($availableStudents as $student)
                                <option value="{{ $student->id }}">{{ $student->full_name }} / {{ $student->student_number }}</option>
                            @endforeach
                        </select>
                        <button class="mt-3 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Assign player</button>
                    </form>

                    <div class="rounded-lg border border-slate-200 p-4">
                        <h3 class="font-semibold text-slate-950">Add new player</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Use the full-page form to review every sport in this edition, select the player’s gender, and register the joined sports in one step.</p>
                        <a href="{{ route('gam.teams.players.create', [$selectedTeam, 'team_id' => $selectedTeam->id]) }}" class="mt-4 inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Open full player form</a>
                    </div>
                </div>
            </div>
        @else
            <p class="mt-5 rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                {{ $managedTeamId ? 'The assigned faction is not active in the current edition.' : 'No active factions are available for the current edition.' }}
            </p>
        @endif
    </section>

    <section id="medical-certificates" data-ops-view-panel="medical-certificates" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Medical certificate verification</h2>
                <p class="mt-1 text-sm text-slate-500">Review physical sports entries that require clearance. Chess and E-Sport (ML) are excluded.</p>
            </div>
            @php($pendingStudents = $medicalCertificateEntries->where('effective_medical_certificate_status', 'pending')->groupBy('student_id')->count())
            <span class="inline-flex w-max whitespace-nowrap rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">{{ $pendingStudents }} {{ Str::plural('student', $pendingStudents) }} pending</span>
        </div>
        <div class="mt-4" data-list-search-container>
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <label class="relative block min-w-0 flex-1 sm:max-w-md">
                    <span class="sr-only">Search medical certificate reviews</span>
                    <input type="search" data-list-search-input placeholder="Search student, ID, team, or sport" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
                </label>
                <span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">Events requiring clearance</th><th class="px-4 py-3">Review</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($medicalCertificateGroups as $studentEntries)
                        @php($entry = $studentEntries->first())
                        @php($rosterTeam = $entry->student?->teamMembers?->first()?->team ?? $entry->team)
                        @php
                            $medicalSearchText = implode(' ', [
                                $entry->student?->full_name ?? 'Removed student',
                                $entry->student?->student_number ?? '',
                                $rosterTeam?->name ?? '',
                                $studentEntries->map(fn ($studentEntry) => $studentEntry->editionSport->sport?->name)->filter()->implode(' '),
                            ]);
                        @endphp
                        <tr data-list-search-row data-search-text="{{ Str::lower($medicalSearchText) }}">
                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-slate-950">{{ $entry->student?->full_name ?? 'Removed student' }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                                    @if ($rosterTeam)
                                        <x-team-badge :team="$rosterTeam" size="xs" class="font-medium text-slate-600" />
                                    @else
                                        <span>No team</span>
                                    @endif
                                    <span>/ {{ $entry->student?->student_number ?? '-' }}</span>
                                </p>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <ul class="space-y-2">
                                    @foreach ($studentEntries as $studentEntry)
                                        @php($status = $studentEntry->effective_medical_certificate_status)
                                        <li class="flex flex-wrap items-center gap-2 text-slate-700">
                                            <span class="flex items-center gap-2">
                                                <span class="ops-sport-inline-icon"><x-sport-group-icon :group="$studentEntry->editionSport->sport?->catalogue_group_key ?? 'other'" /></span>
                                                <span><span class="block">{{ $studentEntry->editionSport->sport?->name }}</span><span class="text-xs text-slate-500">{{ $studentEntry->editionSport->sport?->catalogue_group_label ?? 'Other Sports' }}</span></span>
                                            </span>
                                            <span class="{{ $status === 'pending' ? 'bg-amber-50 text-amber-800' : ($status === 'verified' ? 'bg-green-50 text-green-700' : 'bg-red-600 text-white') }} inline-flex w-max whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold">{{ Str::headline($status) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <form method="POST" action="{{ route('gam.medical-certificates.students.update', [$entry->student, 'team_id' => $rosterTeam?->id, 'view' => 'medical-certificates']) }}" data-ops-form class="grid min-w-[15rem] gap-2">
                                    @csrf
                                    <select name="medical_certificate_status" required aria-label="Medical certificate status for {{ $entry->student?->full_name }}" class="rounded-lg border-slate-300 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                                        <option value="">Choose status</option>
                                        <option value="pending">Pending</option>
                                        <option value="verified">Verified</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                    <input name="medical_certificate_notes" placeholder="Notes" aria-label="Medical certificate notes for {{ $entry->student?->full_name }}" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                                    <button type="submit" data-loading-label="Saving review…" class="w-fit rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">No medical certificate reviews are required yet.</td></tr>
                    @endforelse
                    <tr data-list-search-empty hidden><td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">No medical certificate reviews match your search.</td></tr>
                </tbody>
            </table>
            </div>
        </div>
    </section>

    <section id="team-standings" data-ops-view-panel="team-standings" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Team standings</h2>
                <p class="mt-1 text-sm text-slate-500">Current standings from declared sport results.</p>
            </div>
        </div>
        <div class="mt-4" data-list-search-container>
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <label class="relative block min-w-0 flex-1 sm:max-w-md">
                    <span class="sr-only">Search team standings</span>
                    <input type="search" data-list-search-input placeholder="Search team or department" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
                </label>
                <span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-3 py-2">Rank</th><th class="px-3 py-2">Team</th><th class="px-3 py-2">Department</th><th class="px-3 py-2 text-right">Points</th><th class="px-3 py-2 text-right">Medals</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($standings as $row)
                        <tr data-list-search-row data-search-text="{{ Str::lower(($row['team']->name ?? '').' '.($row['team']->course?->name ?? 'Unassigned')) }}">
                            <td class="px-3 py-3 font-semibold text-slate-700"><x-landing.ranking-medal :rank="$row['rank']" /></td>
                            <td class="px-3 py-3 font-medium text-slate-950">
                                <x-team-badge :team="$row['team']" />
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $row['team']->course?->name ?? 'Unassigned' }}</td>
                            <td class="px-3 py-3 text-right font-semibold text-slate-950">{{ number_format((float) $row['tally']->points, 2) }}</td>
                            <td class="px-3 py-3 text-right text-slate-600">{{ $row['tally']->gold_count }}G / {{ $row['tally']->silver_count }}S / {{ $row['tally']->bronze_count }}B</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-10 text-center text-slate-500">No default teams have been seeded for the active edition.</td></tr>
                    @endforelse
                    <tr data-list-search-empty hidden><td colspan="5" class="px-3 py-10 text-center text-slate-500">No teams match your search.</td></tr>
                </tbody>
            </table>
            </div>
        </div>
    </section>

    <section id="ready-matches" data-ops-view-panel="ready-matches" data-list-search-container hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Ready matches</h2>
                <p class="mt-1 text-sm text-slate-500">Check scheduled matchups that are ready for play.</p>
            </div>
            <label class="relative block min-w-0 sm:w-80">
                <span class="sr-only">Search ready matches</span>
                <input type="search" data-list-search-input placeholder="Search sport or team" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
            </label>
        </div>
        <div class="mt-2 flex justify-end">
            <span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span>
        </div>
        <div class="mt-2 grid gap-3 lg:grid-cols-2">
            @forelse ($upcomingMatches as $match)
                <article data-list-search-row data-search-text="{{ Str::lower(($match->editionSport->sport->name ?? '').' '.($match->competitorOne?->label ?? '').' '.($match->competitorTwo?->label ?? '')) }}" class="rounded-lg border border-slate-200 p-4">
                    <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500"><span class="ops-sport-inline-icon"><x-sport-group-icon :group="$match->editionSport->sport?->catalogue_group_key ?? 'other'" /></span><span>{{ $match->editionSport->sport->name }} · {{ $match->editionSport->sport?->catalogue_group_label ?? 'Other Sports' }} / {{ ucfirst($match->bracket) }} round {{ $match->round_number }}</span></p>
                    <p class="mt-2 flex flex-wrap items-center gap-2 font-medium text-slate-950">
                        @if ($match->competitorOne?->team)
                            <x-team-badge :team="$match->competitorOne->team" />
                        @else
                            <span>{{ $match->competitorOne?->label ?? 'TBD' }}</span>
                        @endif
                        <span class="text-xs font-semibold uppercase text-slate-400">vs</span>
                        @if ($match->competitorTwo?->team)
                            <x-team-badge :team="$match->competitorTwo->team" />
                        @else
                            <span>{{ $match->competitorTwo?->label ?? 'TBD' }}</span>
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-slate-500">{{ $match->schedule?->starts_at?->format('M j, Y g:i A') ?? 'Not scheduled' }}</p>
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">No ready matches yet.</p>
            @endforelse
            <p data-list-search-empty hidden class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">No ready matches match your search.</p>
        </div>
    </section>
@endsection
