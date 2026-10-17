@extends('layouts.coordinator', ['title' => 'Tabulator dashboard', 'workspaceLabel' => 'Tabulator workspace', 'homeRoute' => 'tabulator.dashboard'])

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
            <p class="ops-eyebrow">Score declaration</p>
            <div class="mt-2 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                <div class="min-w-0">
                    <h1 class="ops-display">Tabulator dashboard</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ $edition?->name ?? 'No active intramurals edition is available yet.' }}</p>
                </div>
                <div class="grid gap-3 sm:min-w-80 sm:grid-cols-2">
                    <a href="{{ route('tabulator.rules.index') }}" class="flex items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-3 text-sm font-semibold text-blue-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">Rules & Guidelines</a>
                    <a href="{{ route('tabulator.possible-dq.index') }}" class="flex items-center justify-center rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">Possible DQ Watchlist</a>
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
            <a href="{{ route('tabulator.possible-dq.index') }}" class="ops-dashboard-card block rounded-lg border border-red-200 p-4 transition hover:border-red-300">
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
                <p class="text-sm font-semibold text-slate-950">Placement monitor</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $editionSports->filter(fn ($editionSport) => $editionSport->sportResult)->count() }} / {{ $editionSports->count() }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Track declared and pending placements in one view.</p>
            </a>
            <a href="#match-winners" data-ops-view-shortcut="match-winners" class="ops-dashboard-card block rounded-lg border p-4 transition">
                <p class="text-sm font-semibold text-slate-950">Match winners</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $matches->count() }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">Declare winners for generated match brackets.</p>
            </a>
        </section>
    </section>

    <section id="possible-dq" data-ops-view-panel="possible-dq" data-list-search-container hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Possible DQ watchlist</h2>
                <p class="mt-1 text-sm text-slate-500">Check these registrations before declaring winners.</p>
            </div>
            <div class="flex flex-col gap-2 sm:items-end">
                <label class="relative block min-w-0 sm:w-72">
                    <span class="sr-only">Search Possible DQ students</span>
                    <input type="search" data-list-search-input placeholder="Search student or sport" class="block w-full rounded-lg border-red-200 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-red-500 focus:ring-red-500">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
                </label>
                <div class="flex items-center gap-2"><span data-list-search-count class="text-xs font-medium text-red-900/70" aria-live="polite"></span><span class="inline-flex w-max whitespace-nowrap rounded-full bg-red-600 px-3 py-1 text-xs font-semibold text-white">{{ $flaggedStudents->count() }} flagged</span></div>
            </div>
        </div>
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            @forelse ($flaggedStudents as $evaluation)
                <article data-list-search-row data-search-text="{{ Str::lower($evaluation['student']->full_name.' '.$evaluation['student']->student_number.' '.$evaluation['summary'].' '.implode(' ', $evaluation['issues'])) }}" class="rounded-lg border border-red-400/60 bg-red-950/45 p-4">
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
            <p data-list-search-empty hidden class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">No Possible DQ students match your search.</p>
        </div>
    </section>

    <section id="declare-sports" data-ops-view-panel="declare-sports" hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-100 pb-4 lg:flex-row lg:items-end">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Declare sport winners</h2>
                <p class="mt-1 text-sm text-slate-500">Choose a sport, select the placed teams, and standings will update from the admin point system.</p>
            </div>
            @if ($editionSports->isNotEmpty())
                @php
                    $groupedEditionSports = $editionSports->groupBy(fn ($editionSport) => $editionSport->sport?->catalogue_group_key ?? 'other');
                @endphp
                <form method="GET" action="{{ route('tabulator.dashboard') }}#declare-sports" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <select name="edition_sport_id" class="rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()">
                        @foreach (\App\Models\Sport::CATALOGUE_GROUPS as $groupKey => $groupDefinition)
                            @if ($groupedEditionSports->has($groupKey))
                                <optgroup label="{{ $groupDefinition['label'] }}">
                                    @foreach ($groupedEditionSports->get($groupKey) as $editionSport)
                                        <option value="{{ $editionSport->id }}" @selected($selectedSport?->id === $editionSport->id)>{{ $editionSport->sport?->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    <button class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Open</button>
                </form>
            @endif
        </div>

        @if ($selectedSport)
            @php
                $currentPlacements = $selectedSport->sportResult?->placements_json ?? [];
            @endphp
            <div class="mt-5 flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <span class="ops-sport-inline-icon"><x-sport-group-icon :group="$selectedSport->sport?->catalogue_group_key ?? 'other'" /></span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $selectedSport->sport?->catalogue_group_label ?? 'Other Sports' }}</p>
                    <p class="font-semibold text-slate-950">{{ $selectedSport->sport?->name }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('tabulator.sport-results.store', ['edition_sport_id' => $selectedSport->id, 'view' => 'declare-sports', 'modal' => 'tabulator-declare-sports-modal']) }}" data-ops-form class="mt-5" data-declare-winners data-allow-repeated-winners="{{ count($pointRules) > 3 ? 'true' : 'false' }}">
                @csrf
                <input type="hidden" name="edition_sport_id" value="{{ $selectedSport->id }}">
                @if ($teams->isNotEmpty())
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach ($teams as $team)
                            <x-team-badge :team="$team" size="xs" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700" />
                        @endforeach
                    </div>
                @endif
                <div class="grid gap-3 lg:grid-cols-3">
                    @foreach ($pointRules as $rule)
                        <label class="block rounded-lg border border-slate-200 p-4 text-sm font-medium text-slate-700">
                            <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">#{{ $rule['placement'] }} / {{ number_format((float) $rule['points'], 2) }} pts</span>
                            <span class="mt-1 block font-semibold text-slate-950">{{ $rule['label'] }}</span>
                            <select name="placements[{{ $rule['placement'] }}]" data-placement-select class="mt-3 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                                <option value="">No team selected</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected((int) old('placements.'.$rule['placement'], $currentPlacements[$rule['placement']] ?? 0) === $team->id)>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
                @error('placements')
                    <p class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
                @enderror
                @if (count($pointRules) > 3)
                    <p class="mt-3 text-xs leading-5 text-slate-500">This event has more than three winner placements, so a faction may be selected more than once.</p>
                @endif
                <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Review the placements above, then confirm the declaration with your tabulator password.</p>
                    <button type="button" data-ops-modal-open="tabulator-declare-sports-modal" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Review and declare</button>
                </div>
                <dialog id="tabulator-declare-sports-modal" data-ops-modal class="w-[min(30rem,calc(100vw-2rem))] rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Final check</p>
                            <h3 class="mt-1 text-lg font-semibold">Declare {{ $selectedSport->sport?->name }} winners?</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">This updates standings immediately. Confirm the placements before saving.</p>
                        </div>
                        <button type="button" data-ops-modal-close class="rounded-lg p-2 text-xl leading-none text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close declaration dialog">&times;</button>
                    </div>
                    <div class="space-y-3 px-5 py-4">
                        <label class="block text-sm font-medium text-slate-700">Tabulator password
                            <input name="tabulator_password" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        </label>
                        @error('tabulator_password')
                            <p class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                        <button type="button" data-ops-modal-close class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</button>
                        <button type="submit" data-loading-label="Declaring…" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Declare winners</button>
                    </div>
                </dialog>
            </form>
        @else
            <p class="mt-5 rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No sports are configured for the active edition.</p>
        @endif
    </section>

    <section id="declared-results" data-ops-view-panel="declared-results" hidden class="ops-view-panel grid gap-6 xl:grid-cols-2">
        <div class="scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6 xl:col-span-2" data-list-search-container>
            <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Placement monitor</h2>
                    <p class="mt-1 text-sm text-slate-500">Review every configured sport and open the declaration form for anything still pending.</p>
                </div>
                <div class="flex flex-col gap-2 sm:items-end">
                    <label class="relative block min-w-0 sm:w-72"><span class="sr-only">Search placement monitor</span><input type="search" data-list-search-input placeholder="Search sport or team" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg></label>
                    <span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span>
                    <span class="inline-flex w-max rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">{{ $editionSports->filter(fn ($editionSport) => $editionSport->sportResult)->count() }} of {{ $editionSports->count() }} declared</span>
                </div>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-3 py-2">Sport</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Placements</th><th class="px-3 py-2 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($editionSports as $editionSport)
                            @php
                                $sportResult = $editionSport->sportResult;
                            @endphp
                            <tr data-list-search-row data-search-text="{{ Str::lower(($editionSport->sport?->name ?? '').' '.($editionSport->sport?->catalogue_group_label ?? '').' '.($sportResult?->placements_json ? collect($sportResult->placements_json)->map(fn ($teamId) => $teamsById->get((int) $teamId)?->name)->filter()->implode(' ') : '')) }}">
                                <td class="px-3 py-3 font-semibold text-slate-950">
                                    <span class="flex items-center gap-2">
                                        <span class="ops-sport-inline-icon"><x-sport-group-icon :group="$editionSport->sport?->catalogue_group_key ?? 'other'" /></span>
                                        <span><span class="block">{{ $editionSport->sport?->name }}</span><span class="text-xs font-normal text-slate-500">{{ $editionSport->sport?->catalogue_group_label ?? 'Other Sports' }}</span></span>
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($sportResult)
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">Declared</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Pending</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-slate-600">
                                    @if ($sportResult)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($sportResult->placements_json ?? [] as $placement => $teamId)
                                                @php
                                                    $placedTeam = $teamsById->get((int) $teamId);
                                                @endphp
                                                <span class="inline-flex max-w-full items-center gap-2 rounded-full bg-slate-100 px-3 py-1">
                                                    <x-landing.ranking-medal :rank="$placement" />
                                                    <span>{{ $placedTeam?->name ?? 'Removed team' }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400">No placements recorded</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <a href="{{ route('tabulator.dashboard', ['edition_sport_id' => $editionSport->id]) }}#declare-sports" class="inline-flex rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">{{ $sportResult ? 'Edit' : 'Record' }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No sports are configured for the active edition.</td></tr>
                        @endforelse
                    <tr data-list-search-empty hidden><td colspan="4" class="px-3 py-8 text-center text-slate-500">No placement entries match your search.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-list-search-container>
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end"><div><h2 class="text-base font-semibold text-slate-950">Overall standings</h2></div><label class="relative block min-w-0 sm:w-64"><span class="sr-only">Search overall standings</span><input type="search" data-list-search-input placeholder="Search team" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg></label></div>
            <div class="mt-2 flex justify-end"><span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span></div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                        <tr><th class="px-3 py-2">Rank</th><th class="px-3 py-2">Team</th><th class="px-3 py-2 text-right">Points</th><th class="px-3 py-2 text-right">Medals</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($standings as $row)
                            <tr data-list-search-row data-search-text="{{ Str::lower($row['team']->name ?? '') }}">
                                <td class="px-3 py-3 font-semibold text-slate-700"><x-landing.ranking-medal :rank="$row['rank']" /></td>
                                <td class="px-3 py-3 font-medium text-slate-950">
                                    <x-team-badge :team="$row['team']" />
                                </td>
                                <td class="px-3 py-3 text-right font-semibold text-slate-950">{{ number_format((float) $row['tally']->points, 2) }}</td>
                                <td class="px-3 py-3 text-right text-slate-600">{{ $row['tally']->gold_count }}G / {{ $row['tally']->silver_count }}S / {{ $row['tally']->bronze_count }}B</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No active teams are available.</td></tr>
                        @endforelse
                    <tr data-list-search-empty hidden><td colspan="4" class="px-3 py-10 text-center text-slate-500">No teams match your search.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="match-winners" data-ops-view-panel="match-winners" data-list-search-container hidden class="ops-view-panel scroll-mt-24 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-end"><div><h2 class="text-base font-semibold text-slate-950">Declare match winners</h2></div><label class="relative block min-w-0 sm:w-72"><span class="sr-only">Search match winners</span><input type="search" data-list-search-input placeholder="Search sport or team" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg></label></div>
        <div class="mt-2 flex justify-end"><span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span></div>
        <div class="mt-2 grid gap-3 lg:grid-cols-2">
            @forelse ($matches as $match)
                @php
                    $matchModalId = 'match-winner-modal-'.$match->id;
                @endphp
                <form method="POST" action="{{ route('tabulator.matches.result', [$match, 'view' => 'match-winners', 'modal' => $matchModalId]) }}" data-list-search-row data-search-text="{{ Str::lower(($match->editionSport->sport->name ?? '').' '.($match->competitorOne?->label ?? '').' '.($match->competitorTwo?->label ?? '')) }}" data-ops-form class="rounded-lg border border-slate-200 p-4">
                    @csrf
                    <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500"><span class="ops-sport-inline-icon"><x-sport-group-icon :group="$match->editionSport->sport?->catalogue_group_key ?? 'other'" /></span><span>{{ $match->editionSport->sport->name }} · {{ $match->editionSport->sport?->catalogue_group_label ?? 'Other Sports' }} / {{ ucfirst($match->bracket) }} round {{ $match->round_number }}</span></p>
                    <p class="mt-2 font-medium text-slate-950">Game {{ $match->match_number }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
                        @if ($match->competitorOne?->team)
                            <x-team-badge :team="$match->competitorOne->team" />
                        @else
                            <span>{{ $match->competitorOne?->label ?? 'TBD' }}</span>
                        @endif
                        <span class="text-xs uppercase text-slate-400">vs</span>
                        @if ($match->competitorTwo?->team)
                            <x-team-badge :team="$match->competitorTwo->team" />
                        @else
                            <span>{{ $match->competitorTwo?->label ?? 'TBD' }}</span>
                        @endif
                    </div>
                    <select name="winner_competitor_id" required class="mt-3 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Choose winner</option>
                        <option value="{{ $match->competitorOne->id }}">{{ $match->competitorOne->label }}</option>
                        <option value="{{ $match->competitorTwo->id }}">{{ $match->competitorTwo->label }}</option>
                    </select>
                    <button type="button" data-ops-modal-open="{{ $matchModalId }}" class="mt-3 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">Review and declare</button>
                    <dialog id="{{ $matchModalId }}" data-ops-modal class="w-[min(30rem,calc(100vw-2rem))] rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60">
                        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Final check</p>
                                <h3 class="mt-1 text-lg font-semibold">Declare Game {{ $match->match_number }} winner?</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Choose the winner above, then confirm with your tabulator password.</p>
                            </div>
                            <button type="button" data-ops-modal-close class="rounded-lg p-2 text-xl leading-none text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close match result dialog">&times;</button>
                        </div>
                        <div class="px-5 py-4">
                            <label class="block text-sm font-medium text-slate-700">Tabulator password
                                <input name="tabulator_password" type="password" required autocomplete="current-password" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            </label>
                            @error('tabulator_password')
                                <p class="text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                            <button type="button" data-ops-modal-close class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</button>
                            <button type="submit" data-loading-label="Saving result…" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">Declare winner</button>
                        </div>
                    </dialog>
                </form>
            @empty
                <p class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">No ready matches need a winner yet.</p>
            @endforelse
            <p data-list-search-empty hidden class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">No matches match your search.</p>
        </div>
    </section>
@endsection
