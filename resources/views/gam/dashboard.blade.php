@extends('layouts.coordinator', ['title' => 'GAM dashboard', 'workspaceLabel' => 'GAM workspace', 'homeRoute' => 'gam.dashboard'])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
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
                    <a href="{{ route('gam.rules.index') }}" class="inline-flex w-fit items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">Rules & Guidelines</a>
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
                    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-3">Player</th><th class="px-4 py-3">Student no.</th><th class="px-4 py-3">Course</th><th class="px-4 py-3">Year / Section</th><th class="px-4 py-3">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($selectedTeam->members as $member)
                                    @php($eligibility = $member->student ? $eligibilityByStudentId->get($member->student->id) : null)
                                    <tr @class(['border-l-4 border-red-400 bg-red-950/45' => $eligibility['possible_dq'] ?? false])>
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
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No players assigned yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-5">
                    <form method="POST" action="{{ route('gam.teams.players.assign', $selectedTeam) }}" class="rounded-lg border border-slate-200 p-4">
                        @csrf
                        <h3 class="font-semibold text-slate-950">Assign existing player</h3>
                        <select name="student_id" required class="mt-3 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <option value="">Choose available student</option>
                            @foreach ($availableStudents as $student)
                                <option value="{{ $student->id }}">{{ $student->full_name }} / {{ $student->student_number }}</option>
                            @endforeach
                        </select>
                        <button class="mt-3 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Assign player</button>
                    </form>

                    <form method="POST" action="{{ route('gam.teams.players.store', $selectedTeam) }}" class="rounded-lg border border-slate-200 p-4">
                        @csrf
                        <h3 class="font-semibold text-slate-950">Add new player</h3>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <input name="student_number" value="{{ old('student_number') }}" placeholder="Student no." required class="rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <select name="course_id" class="rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                                <option value="">Course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->code }}</option>
                                @endforeach
                            </select>
                            <input name="first_name" value="{{ old('first_name') }}" placeholder="First name" required class="rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <input name="last_name" value="{{ old('last_name') }}" placeholder="Last name" required class="rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <input name="middle_name" value="{{ old('middle_name') }}" placeholder="Middle name" class="rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <input name="gender" value="{{ old('gender') }}" placeholder="Gender" class="rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <input name="year_level" value="{{ old('year_level') }}" placeholder="Year level" class="rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <input name="section" value="{{ old('section') }}" placeholder="Section" class="rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        </div>
                        <button class="mt-4 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Add player</button>
                    </form>
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
            <span class="inline-flex w-max whitespace-nowrap rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">{{ $medicalCertificateEntries->where('effective_medical_certificate_status', 'pending')->count() }} pending</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">Event</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Review</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($medicalCertificateEntries as $entry)
                        @php($status = $entry->effective_medical_certificate_status)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-slate-950">{{ $entry->student?->full_name ?? 'Removed student' }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                                    @if ($entry->team)
                                        <x-team-badge :team="$entry->team" size="xs" class="font-medium text-slate-600" />
                                    @else
                                        <span>No team</span>
                                    @endif
                                    <span>/ {{ $entry->student?->student_number ?? '-' }}</span>
                                </p>
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">{{ $entry->editionSport->sport?->name }}</td>
                            <td class="px-4 py-3 align-top">
                                <span class="{{ $status === 'pending' ? 'bg-amber-50 text-amber-800' : ($status === 'verified' ? 'bg-green-50 text-green-700' : 'bg-red-600 text-white') }} inline-flex w-max whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold">{{ Str::headline($status) }}</span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <form method="POST" action="{{ route('gam.medical-certificates.update', $entry) }}" class="grid gap-2 sm:grid-cols-[9rem_minmax(12rem,1fr)_auto]">
                                    @csrf
                                    <select name="medical_certificate_status" class="rounded-lg border-slate-300 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                                        <option value="pending" @selected($status === 'pending')>Pending</option>
                                        <option value="verified" @selected($status === 'verified')>Verified</option>
                                        <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                                    </select>
                                    <input name="medical_certificate_notes" value="{{ old('medical_certificate_notes', $entry->medical_certificate_notes) }}" placeholder="Notes" class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                                    <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No medical certificate reviews are required yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section id="team-standings" data-ops-view-panel="team-standings" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Team standings</h2>
                <p class="mt-1 text-sm text-slate-500">Current standings from declared sport results.</p>
            </div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-3 py-2">Rank</th><th class="px-3 py-2">Team</th><th class="px-3 py-2">Department</th><th class="px-3 py-2 text-right">Points</th><th class="px-3 py-2 text-right">Medals</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($standings as $row)
                        <tr>
                            <td class="px-3 py-3 font-semibold text-slate-700">#{{ $row['rank'] }}</td>
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
                </tbody>
            </table>
        </div>
    </section>

    <section id="ready-matches" data-ops-view-panel="ready-matches" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="text-base font-semibold text-slate-950">Ready matches</h2>
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            @forelse ($upcomingMatches as $match)
                <article class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $match->editionSport->sport->name }} / {{ ucfirst($match->bracket) }} round {{ $match->round_number }}</p>
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
        </div>
    </section>
@endsection
