@extends('layouts.coordinator', ['title' => 'Tabulator dashboard', 'workspaceLabel' => 'Tabulator workspace', 'homeRoute' => 'tabulator.dashboard'])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <section data-ops-view-panel="dashboard-overview" class="ops-view-panel space-y-6">
        <div class="ops-hero p-5 sm:p-7 lg:p-8">
            <p class="ops-eyebrow">Score declaration</p>
            <div class="mt-2 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                <div class="min-w-0">
                    <h1 class="ops-display">Tabulator dashboard</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ $edition?->name ?? 'No active intramurals edition is available yet.' }}</p>
                </div>
                <div class="grid gap-3 sm:min-w-80 sm:grid-cols-2">
                    <a href="{{ route('tabulator.rules.index') }}" class="flex items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-3 text-sm font-semibold text-blue-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 sm:col-span-2">Rules & Guidelines</a>
                    <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Sports</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-950">{{ $editionSports->count() }}</p>
                    </div>
                    <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Pending games</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-950">{{ $matches->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <section class="grid gap-4 lg:grid-cols-4">
            <a href="#possible-dq" data-ops-view-shortcut="possible-dq" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Possible DQ</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $flaggedStudents->count() }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Review students that need correction before scoring.</p>
            </a>
            <a href="#declare-sports" data-ops-view-shortcut="declare-sports" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Declare sports</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $editionSports->count() }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Choose winners using the active point system.</p>
            </a>
            <a href="#declared-results" data-ops-view-shortcut="declared-results" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Declared results</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $editionSports->filter(fn ($editionSport) => $editionSport->sportResult)->count() }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Audit posted placements and the standings table.</p>
            </a>
            <a href="#match-winners" data-ops-view-shortcut="match-winners" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Match winners</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $matches->count() }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Declare winners for generated match brackets.</p>
            </a>
        </section>
    </section>

    <section id="possible-dq" data-ops-view-panel="possible-dq" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Possible DQ watchlist</h2>
                <p class="mt-1 text-sm text-slate-500">Check these registrations before declaring winners.</p>
            </div>
            <span class="inline-flex w-max whitespace-nowrap rounded-full bg-red-600 px-3 py-1 text-xs font-semibold text-white">{{ $flaggedStudents->count() }} flagged</span>
        </div>
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            @forelse ($flaggedStudents as $evaluation)
                <article class="rounded-lg border border-red-400/60 bg-red-950/45 p-4">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $evaluation['student']->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $evaluation['student']->student_number }} / {{ $evaluation['summary'] }}</p>
                        </div>
                        <span class="inline-flex w-max whitespace-nowrap rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-white">Possible DQ</span>
                    </div>
                    <p class="mt-3 text-sm font-medium text-red-200">{{ implode(' ', $evaluation['issues']) }}</p>
                </article>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">No Possible DQ students found.</p>
            @endforelse
        </div>
    </section>

    <section id="declare-sports" data-ops-view-panel="declare-sports" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-100 pb-4 lg:flex-row lg:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Declare sport winners</h2>
                <p class="mt-1 text-sm text-slate-500">Choose a sport, select the placed teams, and standings will update from the admin point system.</p>
            </div>
            @if ($editionSports->isNotEmpty())
                <form method="GET" action="{{ route('tabulator.dashboard') }}#declare-sports" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <select name="edition_sport_id" class="rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()">
                        @foreach ($editionSports as $editionSport)
                            <option value="{{ $editionSport->id }}" @selected($selectedSport?->id === $editionSport->id)>{{ $editionSport->sport?->name }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Open</button>
                </form>
            @endif
        </div>

        @if ($selectedSport)
            @php($currentPlacements = $selectedSport->sportResult?->placements_json ?? [])
            <form method="POST" action="{{ route('tabulator.sport-results.store') }}" class="mt-5">
                @csrf
                <input type="hidden" name="edition_sport_id" value="{{ $selectedSport->id }}">
                <div class="grid gap-3 lg:grid-cols-3">
                    @foreach ($pointRules as $rule)
                        <label class="block rounded-lg border border-slate-200 p-4 text-sm font-medium text-slate-700">
                            <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">#{{ $rule['placement'] }} / {{ number_format((float) $rule['points'], 2) }} pts</span>
                            <span class="mt-1 block font-semibold text-slate-950">{{ $rule['label'] }}</span>
                            <select name="placements[{{ $rule['placement'] }}]" class="mt-3 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                                <option value="">No team selected</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected((int) old('placements.'.$rule['placement'], $currentPlacements[$rule['placement']] ?? 0) === $team->id)>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <label class="block text-sm font-medium text-slate-700">Tabulator password
                        <input name="tabulator_password" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600 sm:w-72">
                    </label>
                    <button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Declare winners</button>
                </div>
            </form>
        @else
            <p class="mt-5 rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No sports are configured for the active edition.</p>
        @endif
    </section>

    <section id="declared-results" data-ops-view-panel="declared-results" hidden class="ops-view-panel grid gap-6 xl:grid-cols-2">
        <div class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-slate-950">Declared results</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($editionSports->filter(fn ($editionSport) => $editionSport->sportResult) as $editionSport)
                    <div class="py-3">
                        <p class="font-semibold text-slate-950">{{ $editionSport->sport?->name }}</p>
                        <div class="mt-2 flex flex-wrap gap-2 text-sm text-slate-600">
                            @foreach ($editionSport->sportResult->placements_json ?? [] as $placement => $teamId)
                                <span class="inline-flex max-w-full whitespace-nowrap rounded-full bg-slate-100 px-3 py-1">#{{ $placement }} {{ $teamsById->get((int) $teamId)?->name ?? 'Removed team' }}</span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No sport winners have been declared yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-slate-950">Overall standings</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-3 py-2">Rank</th><th class="px-3 py-2">Team</th><th class="px-3 py-2 text-right">Points</th><th class="px-3 py-2 text-right">Medals</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($standings as $row)
                            <tr>
                                <td class="px-3 py-3 font-semibold text-slate-700">#{{ $row['rank'] }}</td>
                                <td class="px-3 py-3 font-medium text-slate-950">{{ $row['team']->name }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-slate-950">{{ number_format((float) $row['tally']->points, 2) }}</td>
                                <td class="px-3 py-3 text-right text-slate-600">{{ $row['tally']->gold_count }}G / {{ $row['tally']->silver_count }}S / {{ $row['tally']->bronze_count }}B</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No active teams are available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="match-winners" data-ops-view-panel="match-winners" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="text-base font-semibold text-slate-950">Declare match winners</h2>
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            @forelse ($matches as $match)
                <form method="POST" action="{{ route('tabulator.matches.result', $match) }}" class="rounded-lg border border-slate-200 p-4">
                    @csrf
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $match->editionSport->sport->name }} / {{ ucfirst($match->bracket) }} round {{ $match->round_number }}</p>
                    <p class="mt-2 font-medium text-slate-950">Game {{ $match->match_number }}</p>
                    <select name="winner_competitor_id" required class="mt-3 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Choose winner</option>
                        <option value="{{ $match->competitorOne->id }}">{{ $match->competitorOne->label }}</option>
                        <option value="{{ $match->competitorTwo->id }}">{{ $match->competitorTwo->label }}</option>
                    </select>
                    <label class="mt-3 block text-sm font-medium text-slate-700">Tabulator password
                        <input name="tabulator_password" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                    </label>
                    <button class="mt-3 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">Declare winner</button>
                </form>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">No ready matches need a winner yet.</p>
            @endforelse
        </div>
    </section>
@endsection
