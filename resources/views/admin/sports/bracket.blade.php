@extends('layouts.admin', ['title' => $sport->name.' bracket', 'subtitle' => $edition->name.' - '.ucwords(str_replace('_', ' ', $editionSport->game_mechanic))])

@section('content')
    @php
        $winnerRounds = $matches->get('winners', collect())->groupBy('round_number')->sortKeys();
        $loserRounds = $matches->get('losers', collect())->groupBy('round_number')->sortKeys();
        $finalMatches = $matches->get('finals', collect())->sortBy('round_number');
        $roundRobinRounds = $matches->get('round_robin', collect())->groupBy('round_number')->sortKeys();
        $roundRobinSlotIndexes = $roundRobinRounds->flatten(1)
            ->flatMap(fn ($match) => [$match->competitor_one_id, $match->competitor_two_id])
            ->filter()->unique()->values()->flip()->all();
        $lastWinnerRound = $winnerRounds->keys()->last();
        $maxWinnerMatches = max(1, $winnerRounds->map(fn ($round) => $round->count())->max() ?? 0);
        $canvasWidth = max(1120, ($winnerRounds->count() * 320) + ($finalMatches->isNotEmpty() ? 320 : 260));
        $canvasHeight = max(520, ($maxWinnerMatches * 150) + 180);
        $roundRobinWidth = max(1120, ($roundRobinRounds->count() * 320) + 80);
        $roundRobinHeight = max(520, (($roundRobinRounds->map(fn ($round) => $round->count())->max() ?? 1) * 320) + 140);
        $champion = $finalMatches->where('status', 'completed')->last()?->winnerCompetitor ?? $winnerRounds->get($lastWinnerRound, collect())->whereIn('status', ['completed', 'bye'])->last()?->winnerCompetitor;
    @endphp

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Please review the bracket.</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="bracket-title">
        <header class="flex flex-col justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Game Style / Elimination Style</p>
                <h2 id="bracket-title" class="mt-1 text-lg font-semibold text-slate-900">{{ ucfirst($editionSport->participant_type) }} · {{ ucwords(str_replace('_', ' ', $editionSport->game_mechanic)) }}</h2>
            </div>
            <div class="flex flex-col gap-3 sm:items-end">
                <div class="sm:text-right"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tournament Name</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $edition->name }} — {{ $sport->name }}</p></div>
                <div class="flex flex-wrap gap-2 sm:justify-end">
                    @if (! str_starts_with(strtoupper($sport->code), 'CULT-'))
                        <a href="{{ route('admin.sports.score-sheet', ['sport' => $sport, 'edition_id' => $edition->id]) }}" class="w-fit rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700">Score sheet</a>
                    @endif
                    <a href="{{ route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $edition->id]) }}" class="w-fit rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Manage participants</a>
                    @if ($matches->isNotEmpty() && in_array($editionSport->game_mechanic, ['single_elimination', 'double_elimination', 'round_robin', 'custom'], true))
                        <form method="POST" action="{{ route('admin.sports.bracket.reset', $sport) }}" onsubmit="return confirm('Reset this bracket? All recorded bracket results will be removed and the bracket will be rebuilt from the current registrations. Teams, students, and registrations will not be changed.');">
                            @csrf
                            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                            <input type="hidden" name="confirmation" value="RESET">
                            <button type="submit" class="w-fit rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50">Reset bracket</button>
                        </form>
                    @endif
                </div>
            </div>
        </header>

        @if (in_array($editionSport->game_mechanic, ['single_elimination', 'double_elimination', 'round_robin', 'custom'], true) && ($selectedCompetitors->isNotEmpty() || $availableCompetitors->isNotEmpty()))
            @php
                $lineupLocked = ! $bracketBlank && $matches->flatten(1)->contains(fn ($match) => $match->status !== 'pending');
            @endphp
            <section class="border-b border-slate-200 bg-slate-50 px-5 py-5 sm:px-6" aria-labelledby="lineup-heading">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Manual bracket entry</p>
                        <h3 id="lineup-heading" class="mt-1 text-base font-semibold text-slate-900">Drag competitors onto the bracket</h3>
                        <p class="mt-1 max-w-3xl text-sm text-slate-600">Drag a team, pair, or athlete directly into a bracket slot. Names and logos appear immediately. When finished, save the bracket lineup.</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-600"><span data-lineup-count>{{ $selectedCompetitors->count() }}</span> selected</span>
                </div>
                @if ($lineupLocked)
                    <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Lineup editing is locked after a result or automatic bye. Reset the bracket before changing the field.</p>
                @else
                    <form method="POST" action="{{ route('admin.sports.bracket.order', ['sport' => $sport, 'edition_id' => $edition->id]) }}" class="mt-5" data-bracket-lineup-form>
                        @csrf
                        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                        <div>
                            <div class="mb-2 flex items-center justify-between"><h4 class="text-sm font-semibold text-slate-800">Available competitors</h4><span class="text-xs text-slate-500">Drag directly to an opening bracket slot</span></div>
                            <div class="grid min-h-16 gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-white p-3 sm:grid-cols-3" data-lineup-dropzone="available" aria-label="Available competitors">
                                @foreach ($selectedCompetitors->merge($availableCompetitors)->unique('id')->sortBy('label') as $competitor)
                                    <div role="button" tabindex="0" draggable="true" data-lineup-card data-competitor-id="{{ $competitor->id }}" data-competitor-label="{{ $competitor->label }}" class="flex w-full cursor-grab items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left text-sm font-medium text-slate-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 active:cursor-grabbing" aria-label="Add {{ $competitor->label }} to bracket">
                                        <span data-lineup-content class="flex min-w-0 items-center gap-2">
                                            @if ($competitor->team)
                                                <x-team-badge :team="$competitor->team" size="xs" :logo-only="true" class="shrink-0" />
                                            @endif
                                            <span class="truncate">{{ $competitor->label }}</span>
                                        </span>
                                        <span class="shrink-0 text-xs text-slate-400">Drag or click →</span>
                                    </div>
                                @endforeach
                                <p data-lineup-empty="available" class="{{ $availableCompetitors->isNotEmpty() ? 'hidden ' : '' }}px-2 py-3 text-center text-xs text-slate-400 sm:col-span-3">All registered competitors are selected.</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <div>
                                <p class="text-xs text-slate-500">You can also focus a competitor and press Enter or Space to place it in the next empty slot.</p>
                                <p data-bracket-drag-feedback hidden role="status" aria-live="polite" class="mt-1 text-xs font-semibold text-blue-700">Opening slot updated.</p>
                            </div>
                            <button type="submit" data-lineup-submit class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">Save bracket lineup</button>
                        </div>
                        <div data-lineup-inputs></div>
                    </form>
                @endif
            </section>
        @endif

        @if (! in_array($editionSport->game_mechanic, ['single_elimination', 'double_elimination', 'round_robin', 'custom'], true))
            <div class="m-6 rounded-lg border border-blue-100 bg-blue-50 p-5 text-sm text-blue-900">This sport does not use an automated tournament mechanic.</div>
        @elseif ($matches->isEmpty())
            <div class="m-6 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">Register at least two competitors in Manage Participants to generate the tournament. Teams, dual pairs, and individual athletes are supported.</div>
        @elseif (in_array($editionSport->game_mechanic, ['round_robin', 'custom'], true))
            <div class="border-t border-slate-100 bg-slate-50 px-5 py-2 text-xs text-slate-500 sm:px-6">Every competitor plays every other competitor once. Scroll horizontally for rounds and vertically for large fields.</div>
            <div class="h-[calc(100vh-19rem)] min-h-[34rem] max-h-[52rem] overflow-auto bg-slate-100 p-4 sm:p-6" aria-label="Round-robin tournament canvas">
                <div class="bracket-canvas relative isolate rounded-lg border border-slate-300 bg-white p-6" data-bracket-canvas style="min-width: {{ $roundRobinWidth }}px; min-height: {{ $roundRobinHeight }}px;">
                    <svg class="bracket-connectors pointer-events-none absolute inset-0 z-0 h-full w-full overflow-visible" data-bracket-connectors aria-hidden="true"></svg>
                    <section class="relative z-10" aria-labelledby="round-robin-heading">
                        <div class="mb-5 flex items-center justify-between border-b-2 border-slate-900 pb-3">
                            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">League schedule</p><h3 id="round-robin-heading" class="mt-1 text-base font-semibold text-slate-900">{{ $editionSport->game_mechanic === 'custom' ? 'Custom league schedule' : 'Round Robin' }}</h3></div>
                            <span class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600">{{ $roundRobinRounds->flatten(1)->count() }} {{ Str::plural('game', $roundRobinRounds->flatten(1)->count()) }}</span>
                        </div>
                        <div class="flex min-w-max items-start gap-12">
                            @foreach ($roundRobinRounds as $roundNumber => $roundMatches)
                                <div class="w-64">
                                    <h4 class="mb-4 border-b border-slate-300 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-600">Round {{ $roundNumber }}</h4>
                                    <div class="space-y-6">
                                        @foreach ($roundMatches as $match)
                                            <div class="bracket-node relative" data-bracket-node data-match-id="{{ $match->id }}">
                                                @include('admin.sports.partials.bracket-match', ['match' => $match, 'bracketSlotStart' => null, 'bracketSlotIndexes' => [$roundRobinSlotIndexes[$match->competitor_one_id] ?? null, $roundRobinSlotIndexes[$match->competitor_two_id] ?? null]])
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>
        @else
            <div class="border-t border-slate-100 bg-slate-50 px-5 py-2 text-xs text-slate-500 sm:px-6">Scroll horizontally for additional rounds and vertically for large team fields.</div>
            <div class="h-[calc(100vh-19rem)] min-h-[34rem] max-h-[52rem] overflow-auto bg-slate-100 p-4 sm:p-6" aria-label="Tournament bracket canvas">
                <div class="bracket-canvas relative isolate rounded-lg border border-slate-300 bg-white p-6" data-bracket-canvas style="min-width: {{ $canvasWidth }}px; min-height: {{ $canvasHeight }}px;">
                    <svg class="bracket-connectors pointer-events-none absolute inset-0 z-0 h-full w-full overflow-visible" data-bracket-connectors aria-hidden="true"></svg>
                    <section class="relative z-10" aria-labelledby="winners-bracket-heading">
                        <div class="mb-5 flex items-center justify-between border-b-2 border-slate-900 pb-3">
                            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Primary bracket</p><h3 id="winners-bracket-heading" class="mt-1 text-base font-semibold text-slate-900">{{ $editionSport->game_mechanic === 'double_elimination' ? 'Winners Bracket' : 'Tournament Bracket' }}</h3></div>
                            <span class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600">{{ $winnerRounds->sum(fn ($round) => $round->count()) }} {{ Str::plural('game', $winnerRounds->sum(fn ($round) => $round->count())) }}</span>
                        </div>

                        <div class="flex min-w-max items-stretch gap-12">
                            @foreach ($winnerRounds as $roundNumber => $roundMatches)
                                @php
                                    $isLastWinnerRound = $roundNumber === $lastWinnerRound;
                                @endphp
                                <div class="flex w-64 flex-col">
                                    <h4 class="mb-4 border-b border-slate-300 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $isLastWinnerRound ? ($editionSport->game_mechanic === 'double_elimination' ? 'Winners Final' : 'Finals') : 'Round '.$roundNumber }}</h4>
                                    <div class="flex flex-1 flex-col justify-around gap-8">
                                        @foreach ($roundMatches as $match)
                                            @php
                                                $winnerNext = $roundNumber < $lastWinnerRound
                                                    ? $winnerRounds->get($roundNumber + 1, collect())->firstWhere('match_number', (int) ceil($match->match_number / 2))
                                                    : ($editionSport->game_mechanic === 'double_elimination' ? $finalMatches->firstWhere('round_number', 1) : null);
                                                $loserNext = null;
                                                if ($editionSport->game_mechanic === 'double_elimination') {
                                                    $loserNext = (int) $roundNumber === 1
                                                        ? $loserRounds->get(1, collect())->firstWhere('match_number', (int) ceil($match->match_number / 2))
                                                        : $loserRounds->get((2 * $roundNumber) - 2, collect())->firstWhere('match_number', $match->match_number);
                                                }
                                            @endphp
                                            <div class="bracket-node relative flex items-center" data-bracket-node data-match-id="{{ $match->id }}" data-winner-next-id="{{ $winnerNext?->id }}" data-loser-next-id="{{ $loserNext?->id }}">
                                                @include('admin.sports.partials.bracket-match', ['match' => $match, 'bracketSlotStart' => (int) $roundNumber === 1 ? (($match->match_number - 1) * 2) : null])
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div class="flex w-64 flex-col">
                                <h4 class="mb-4 border-b border-slate-300 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $finalMatches->isNotEmpty() ? 'Grand Finals' : 'Winner' }}</h4>
                                <div class="flex flex-1 flex-col justify-center gap-6">
                                    @foreach ($finalMatches as $match)
                                        @php
                                            $finalNext = null;
                                            if ($match->round_number === 1) {
                                                $finalNext = $finalMatches->firstWhere('round_number', 2);
                                            }
                                        @endphp
                                        <div class="bracket-node relative flex items-center" data-bracket-node data-match-id="{{ $match->id }}" data-winner-next-id="{{ $finalNext?->id }}">
                                            @include('admin.sports.partials.bracket-match', ['match' => $match])
                                        </div>
                                    @endforeach
                                    <article class="rounded-lg border-2 border-slate-900 bg-white p-4 text-center shadow-sm">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Winner</p>
                                        <p class="mt-2 flex min-w-0 items-center justify-center gap-2 text-base font-bold text-slate-900">
                                            @if ($champion?->team)
                                                <x-team-badge :team="$champion->team" size="xs" :logo-only="true" />
                                            @endif
                                            <span class="truncate">{{ $champion?->label ?? 'To be decided' }}</span>
                                        </p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </section>

                    @if ($editionSport->game_mechanic === 'double_elimination')
                        <section class="relative z-10 mt-12 border-t-4 border-slate-900 pt-6" aria-labelledby="losers-bracket-heading">
                            <div class="mb-5 flex items-center justify-between border-b border-slate-300 pb-3"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Double-elimination path</p><h3 id="losers-bracket-heading" class="mt-1 text-base font-semibold text-slate-900">Loser Bracket if needed</h3></div><span class="text-xs text-slate-500">Expands as teams drop from the winners bracket</span></div>
                            <div class="flex min-w-max gap-12">
                                @forelse ($loserRounds as $roundNumber => $roundMatches)
                                    <div class="flex w-64 flex-col">
                                        <h4 class="mb-4 border-b border-slate-300 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-600">Losers Round {{ $roundNumber }}</h4>
                                        <div class="flex flex-1 flex-col justify-around gap-8">
                                            @foreach ($roundMatches as $match)
                                                @php
                                                    $lastLoserRound = $loserRounds->keys()->last();
                                                    $loserNext = null;
                                                    if ($roundNumber === $lastLoserRound) {
                                                        $loserNext = $finalMatches->firstWhere('round_number', 1);
                                                    } elseif ($roundNumber % 2 === 1) {
                                                        $loserNext = $loserRounds->get($roundNumber + 1, collect())->firstWhere('match_number', $match->match_number);
                                                    } else {
                                                        $loserNext = $loserRounds->get($roundNumber + 1, collect())->firstWhere('match_number', (int) ceil($match->match_number / 2));
                                                    }
                                                @endphp
                                                <div class="bracket-node relative flex items-center" data-bracket-node data-match-id="{{ $match->id }}" data-winner-next-id="{{ $loserNext?->id }}">
                                                    @include('admin.sports.partials.bracket-match', ['match' => $match])
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">Loser matches will appear here when the double-elimination flow requires them.</div>
                                @endforelse
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        @endif
    </section>

    <style>
        .bracket-node { transition: opacity 150ms ease; }
        .bracket-node > article { transition: border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease; }
        .bracket-node.is-highlighted > article { border-color: #355872; box-shadow: 0 0 0 3px #9CD5FF, 0 12px 22px -16px #355872; transform: translateY(-1px); }
        .bracket-node.is-competitor-highlighted .bracket-team[data-competitor-id] { background-color: #F7F8F0; color: #355872; }
        .bracket-team { transition: background-color 150ms ease, box-shadow 150ms ease, color 150ms ease; }
        [data-bracket-slot-card].is-bracket-dragging { opacity: .45; }
        [data-bracket-slot-card].is-bracket-drop-target { background-color: #E0F2FE; box-shadow: inset 0 0 0 2px #0284C7; }
        .bracket-slot-empty { min-height: 2.75rem; border: 2px dashed #BAE6FD; background: #F0F9FF; }
        .bracket-slot-empty.has-bracket-assignment { border-style: solid; border-color: #93C5FD; background: #EFF6FF; color: #1E3A8A; font-weight: 600; }
        [data-lineup-card] { user-select: none; }
        [data-lineup-card], [data-bracket-slot-card] { scroll-margin-top: 7rem; }
        .bracket-canvas [id$="-heading"] { scroll-margin-top: 7rem; }
        main:has([data-bracket-lineup-form]) { scroll-padding-top: 7rem; }
        [data-lineup-card][hidden], [data-bracket-drag-feedback][hidden] { display: none !important; }
        .bracket-slot-remove[hidden] { display: none !important; }
        [data-bracket-slot-row] { display: flex; align-items: center; gap: .5rem; }
        [data-bracket-slot-row] > :first-child { flex: 1 1 0%; min-width: 0; }
        [data-bracket-slot-row] > .bracket-team { flex-direction: column; align-items: stretch; gap: .25rem; }
        [data-bracket-slot-row] .bracket-selection-indicator { align-self: flex-start; }
        .bracket-slot-remove { flex: 0 0 auto; margin-right: .5rem; padding: .3rem .4rem; border: 1px solid #FCA5A5; border-radius: .3rem; background: #FEF2F2 !important; color: #991B1B !important; font-size: .7rem; font-weight: 600; line-height: 1.2; }
        .bracket-slot-remove:hover { background: #FEE2E2 !important; }
        .bracket-slot-remove:focus-visible { outline: 2px solid #991B1B; outline-offset: 2px; }
        [data-lineup-content] img, [data-bracket-slot-content] img { pointer-events: none; }
        .bracket-team.is-click-selected { background-color: #9CD5FF; box-shadow: inset 4px 0 0 #355872; color: #355872; }
        .bracket-team.is-click-selected:focus-visible { outline: 2px solid #355872; outline-offset: -2px; }
        .bracket-canvas.has-bracket-focus .bracket-node:not(.is-highlighted):not(.is-competitor-highlighted) { opacity: .42; }
        .bracket-connector { fill: none; stroke: #7AAACE; stroke-linecap: square; stroke-linejoin: miter; stroke-width: 2; transition: stroke 150ms ease, stroke-width 150ms ease, opacity 150ms ease; }
        .bracket-connector--loser { stroke: #9CD5FF; stroke-dasharray: 6 5; }
        .bracket-canvas.has-bracket-focus .bracket-connector:not(.is-highlighted) { opacity: .18; }
        .bracket-connector.is-highlighted { stroke: #355872; stroke-width: 3.5; opacity: 1; }
        .bracket-connector--loser.is-highlighted { stroke: #7AAACE; }
    </style>

    <script>
        let bracketLineupDirty = false;
        const lineupForm = document.querySelector('[data-bracket-lineup-form]');
        if (lineupForm) {
            const availableZone = lineupForm.querySelector('[data-lineup-dropzone="available"]');
            const lineupCount = document.querySelector('[data-lineup-count]');
            const submitLineup = lineupForm.querySelector('[data-lineup-submit]');
            const hiddenInputs = lineupForm.querySelector('[data-lineup-inputs]');
            const bracketDragFeedback = document.querySelector('[data-bracket-drag-feedback]');
            const bracketSlots = [...document.querySelectorAll('[data-bracket-slot-card]')];
            const removeButtons = [...document.querySelectorAll('[data-remove-bracket-slot]')];
            // Keep the client usable if an older cached view omitted the
            // numeric index: opening slots are rendered in bracket order.
            bracketSlots.forEach((slot, position) => {
                if (! slot.dataset.bracketSlotIndex) slot.dataset.bracketSlotIndex = String(position);
            });
            const slotCount = bracketSlots.reduce((count, slot) => Math.max(count, Number.parseInt(slot.dataset.bracketSlotIndex || '-1', 10) + 1), 0);
            let bracketSlotAssignments = Array.from({ length: slotCount }, () => null);
            let draggedCard = null;
            let draggedSource = null;
            let draggedCompetitorId = null;
            let draggedSlotIndex = null;
            let saving = false;

            bracketSlots.forEach((slot) => {
                const index = Number.parseInt(slot.dataset.bracketSlotIndex || '-1', 10);
                const id = slot.dataset.competitorId || null;
                if (index >= 0 && id) bracketSlotAssignments[index] = id;
            });
            const savedAssignments = JSON.stringify(bracketSlotAssignments);

            const labels = new Map([...lineupForm.querySelectorAll('[data-lineup-card]'), ...bracketSlots]
                .filter((item) => item.dataset.competitorId)
                .map((item) => [item.dataset.competitorId, item.dataset.competitorLabel || 'Competitor']));
            const competitorContent = new Map([...lineupForm.querySelectorAll('[data-lineup-card]')]
                .map((card) => [card.dataset.competitorId, card.querySelector('[data-lineup-content]')]));

            const selectedIds = () => bracketSlotAssignments.filter(Boolean);

            const syncHiddenInputs = () => {
                hiddenInputs.replaceChildren();
                selectedIds().forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'competitor_ids[]';
                    input.value = id;
                    hiddenInputs.append(input);
                });
            };

            const syncBracketPreview = () => {
                bracketSlots.forEach((slot) => {
                    const index = Number.parseInt(slot.dataset.bracketSlotIndex || '-1', 10);
                    const id = index >= 0 ? bracketSlotAssignments[index] : null;
                    const content = slot.querySelector('[data-bracket-slot-content]');
                    const text = id ? (labels.get(id) || 'Competitor') : 'Drop team here';
                    if (content) {
                        const template = id ? competitorContent.get(id) : null;
                        content.replaceChildren(...(template
                            ? [...template.childNodes].map((node) => node.cloneNode(true))
                            : [document.createTextNode(text)]));
                        content.querySelectorAll('img').forEach((logo) => { logo.draggable = false; });
                    }
                    slot.dataset.competitorId = id || '';
                    slot.dataset.competitorLabel = id ? (labels.get(id) || 'Competitor') : '';
                    slot.draggable = Boolean(id);
                    slot.classList.toggle('has-bracket-assignment', Boolean(id));
                });
                document.querySelectorAll('[data-bracket-draft]').forEach((match) => {
                    const assigned = [...match.querySelectorAll('[data-bracket-slot-card]')].some((slot) => slot.dataset.competitorId);
                    match.querySelector('[data-bracket-match-status]').textContent = assigned ? 'Unsaved' : 'Unassigned';
                });
            };

            const refreshLineup = () => {
                bracketLineupDirty = JSON.stringify(bracketSlotAssignments) !== savedAssignments;
                document.querySelectorAll('[data-bracket-result-form] button, [data-schedule-toggle], [data-schedule-form] button, [data-delete-schedule]').forEach((button) => {
                    button.disabled = bracketLineupDirty || saving;
                });
                if (bracketLineupDirty) {
                    document.querySelectorAll('.match-actions, [data-schedule-form]').forEach((panel) => panel.classList.add('hidden'));
                    document.querySelectorAll('[data-schedule-toggle]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
                }
                const selected = new Set(selectedIds());
                lineupForm.querySelectorAll('[data-lineup-card]').forEach((card) => {
                    card.hidden = selected.has(card.dataset.competitorId);
                });
                lineupForm.querySelector('[data-lineup-empty="available"]')?.classList.toggle('hidden', [...availableZone.querySelectorAll('[data-lineup-card]:not([hidden])')].length > 0);
                if (lineupCount) lineupCount.textContent = String(selected.size);
                removeButtons.forEach((button) => {
                    const id = bracketSlotAssignments[Number(button.dataset.removeBracketSlot)];
                    button.hidden = ! id;
                    button.disabled = saving;
                    button.setAttribute('aria-label', id ? `Remove ${labels.get(id) || 'competitor'} from bracket` : 'Remove competitor from this slot');
                });
                submitLineup.disabled = selected.size < 2 || saving;
                syncHiddenInputs();
                syncBracketPreview();
            };

            const saveLineup = () => {
                if (saving || selectedIds().length < 2) return;
                saving = true;
                refreshLineup();
                syncHiddenInputs();
                bracketDragFeedback.hidden = false;
                bracketDragFeedback.textContent = 'Saving bracket lineup…';
                const formData = new FormData(lineupForm);
                fetch(lineupForm.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                }).then(async (response) => {
                    const data = await response.json();
                    if (! response.ok || ! data.redirect_url) throw new Error(data.message || 'The lineup could not be saved. Please try again.');
                    const destination = new URL(data.redirect_url, window.location.href);
                    destination.hash = document.getElementById('round-robin-heading') ? 'round-robin-heading' : 'winners-bracket-heading';
                    // Changing only the hash does not load the newly saved
                    // match IDs. Always fetch the saved bracket document.
                    window.history.replaceState(null, '', destination.toString());
                    window.location.reload();
                }).catch((error) => {
                    saving = false;
                    refreshLineup();
                    if (bracketDragFeedback) {
                        bracketDragFeedback.hidden = false;
                        bracketDragFeedback.textContent = error.message;
                        bracketDragFeedback.classList.remove('text-blue-700');
                        bracketDragFeedback.classList.add('text-red-700');
                    }
                });
            };

            const placeCompetitor = (sourceId, targetIndex, sourceIndex = -1) => {
                const id = String(sourceId);
                if (saving || ! Number.isInteger(targetIndex) || targetIndex < 0 || targetIndex >= slotCount || !labels.has(id)) return;
                sourceIndex = bracketSlotAssignments.indexOf(id);
                if (sourceIndex >= 0 && sourceIndex !== targetIndex) {
                    [bracketSlotAssignments[sourceIndex], bracketSlotAssignments[targetIndex]] = [bracketSlotAssignments[targetIndex], bracketSlotAssignments[sourceIndex]];
                } else {
                    bracketSlotAssignments[targetIndex] = id;
                }
                if (bracketDragFeedback) {
                    bracketDragFeedback.hidden = false;
                    bracketDragFeedback.classList.remove('text-red-700');
                    bracketDragFeedback.classList.add('text-blue-700');
                    bracketDragFeedback.textContent = 'Team placed. Save the bracket lineup when finished.';
                }
                refreshLineup();
            };

            removeButtons.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (saving) return;
                    const index = Number(button.dataset.removeBracketSlot);
                    const id = bracketSlotAssignments[index];
                    if (! id) return;
                    bracketSlotAssignments[index] = null;
                    refreshLineup();
                    selectedBracketFocus = null;
                    clearBracketFocus();
                    document.querySelectorAll('.bracket-team').forEach((candidate) => {
                        candidate.classList.remove('is-click-selected');
                        candidate.setAttribute('aria-pressed', 'false');
                        candidate.querySelector('.bracket-selection-indicator')?.classList.add('hidden');
                    });
                    document.querySelectorAll('.match-actions').forEach((panel) => panel.classList.add('hidden'));
                    bracketDragFeedback.hidden = false;
                    bracketDragFeedback.classList.remove('text-red-700');
                    bracketDragFeedback.classList.add('text-blue-700');
                    bracketDragFeedback.textContent = `${labels.get(id) || 'Competitor'} returned to Available. Save the bracket lineup when finished.`;
                    lineupForm.querySelector(`[data-lineup-card][data-competitor-id="${id}"]`)?.focus({ preventScroll: true });
                });
            });

            lineupForm.querySelectorAll('[data-lineup-card]').forEach((card) => {
                card.addEventListener('dragstart', (event) => {
                    draggedCard = card;
                    draggedSource = 'available';
                    draggedCompetitorId = card.dataset.competitorId;
                    card.classList.add('opacity-50');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', draggedCompetitorId);
                });
                card.addEventListener('dragend', () => {
                    draggedCard?.classList.remove('opacity-50');
                    draggedCard = null;
                    draggedSource = null;
                    draggedCompetitorId = null;
                });
                const placeFromKeyboard = (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    const targetIndex = bracketSlotAssignments.findIndex((id) => ! id);
                    if (targetIndex < 0) return;
                    event.preventDefault();
                    placeCompetitor(card.dataset.competitorId, targetIndex);
                };
                card.addEventListener('click', () => {
                    const targetIndex = bracketSlotAssignments.findIndex((id) => ! id);
                    if (targetIndex >= 0) placeCompetitor(card.dataset.competitorId, targetIndex);
                });
                card.addEventListener('keydown', placeFromKeyboard);
            });

            availableZone.addEventListener('dragover', (event) => {
                if (draggedSource === 'bracket') {
                    event.preventDefault();
                    availableZone.classList.add('ring-2', 'ring-blue-300');
                }
            });
            availableZone.addEventListener('dragleave', () => availableZone.classList.remove('ring-2', 'ring-blue-300'));
            availableZone.addEventListener('drop', (event) => {
                event.preventDefault();
                availableZone.classList.remove('ring-2', 'ring-blue-300');
                if (draggedSource !== 'bracket' || draggedSlotIndex === null) return;
                bracketSlotAssignments[draggedSlotIndex] = null;
                refreshLineup();
            });

            bracketSlots.forEach((slot) => {
                slot.addEventListener('dragstart', (event) => {
                    if (! slot.dataset.competitorId) {
                        event.preventDefault();
                        return;
                    }
                    draggedSource = 'bracket';
                    draggedCompetitorId = slot.dataset.competitorId;
                    draggedSlotIndex = Number.parseInt(slot.dataset.bracketSlotIndex || '-1', 10);
                    slot.classList.add('is-bracket-dragging');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', draggedCompetitorId);
                });
                slot.addEventListener('dragend', () => {
                    slot.classList.remove('is-bracket-dragging');
                    draggedSource = null;
                    draggedCompetitorId = null;
                    draggedSlotIndex = null;
                    bracketSlots.forEach((target) => target.classList.remove('is-bracket-drop-target'));
                });
                const allowBracketDrop = (event) => {
                    if (! draggedCompetitorId || (draggedSource === 'bracket' && draggedSlotIndex === null)) return;
                    event.preventDefault();
                    slot.classList.add('is-bracket-drop-target');
                };
                slot.addEventListener('dragenter', allowBracketDrop);
                slot.addEventListener('dragover', allowBracketDrop);
                slot.addEventListener('dragleave', () => slot.classList.remove('is-bracket-drop-target'));
                slot.addEventListener('drop', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    slot.classList.remove('is-bracket-drop-target');
                    const sourceId = draggedCompetitorId || event.dataTransfer.getData('text/plain');
                    const targetIndex = Number.parseInt(slot.dataset.bracketSlotIndex || '-1', 10);
                    if (sourceId && Number.isInteger(targetIndex)) placeCompetitor(sourceId, targetIndex, draggedSource === 'bracket' ? draggedSlotIndex : -1);
                });
            });

            // Keep the drop target working when the browser dispatches the
            // drag event on a child element inside the slot instead of on the
            // slot itself. This is common with Chromium when a logo or label
            // is under the pointer at release time.
            const bracketCanvas = document.querySelector('[data-bracket-canvas]');
            bracketCanvas?.addEventListener('dragover', (event) => {
                if (! draggedCompetitorId) return;
                const slot = event.target.closest?.('[data-bracket-slot-card]');
                if (! slot) return;
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                slot.classList.add('is-bracket-drop-target');
            });
            bracketCanvas?.addEventListener('drop', (event) => {
                const slot = event.target.closest?.('[data-bracket-slot-card]');
                if (! slot) return;
                event.preventDefault();
                const sourceId = draggedCompetitorId || event.dataTransfer.getData('text/plain');
                const targetIndex = Number.parseInt(slot.dataset.bracketSlotIndex || '-1', 10);
                bracketSlots.forEach((target) => target.classList.remove('is-bracket-drop-target'));
                if (sourceId && Number.isInteger(targetIndex)) placeCompetitor(sourceId, targetIndex, draggedSource === 'bracket' ? draggedSlotIndex : -1);
            });

            lineupForm.addEventListener('submit', (event) => {
                event.preventDefault();
                event.stopPropagation();
                syncHiddenInputs();
                saveLineup();
            });
            refreshLineup();
        }

        const bracketCanvas = document.querySelector('[data-bracket-canvas]');
        const connectorLayer = bracketCanvas?.querySelector('[data-bracket-connectors]');
        const bracketNodes = bracketCanvas ? [...bracketCanvas.querySelectorAll('[data-bracket-node]')] : [];
        let selectedBracketFocus = null;

        const drawBracketConnections = () => {
            if (! bracketCanvas || ! connectorLayer) return;

            const canvasBox = bracketCanvas.getBoundingClientRect();
            connectorLayer.replaceChildren();
            connectorLayer.setAttribute('viewBox', `0 0 ${bracketCanvas.scrollWidth} ${bracketCanvas.scrollHeight}`);
            connectorLayer.style.width = `${bracketCanvas.scrollWidth}px`;
            connectorLayer.style.height = `${bracketCanvas.scrollHeight}px`;

            bracketNodes.forEach((node) => {
                [['winnerNextId', 'winner'], ['loserNextId', 'loser']].forEach(([datasetKey, pathType]) => {
                    const targetId = node.dataset[datasetKey];
                    const target = targetId ? bracketCanvas.querySelector(`[data-match-id="${targetId}"]`) : null;
                    if (! target) return;

                    const sourceBox = node.getBoundingClientRect();
                    const targetBox = target.getBoundingClientRect();
                    const startX = sourceBox.right - canvasBox.left;
                    const startY = sourceBox.top + (sourceBox.height / 2) - canvasBox.top;
                    const endX = targetBox.left - canvasBox.left;
                    const endY = targetBox.top + (targetBox.height / 2) - canvasBox.top;
                    const bendX = startX + Math.max(28, (endX - startX) / 2);
                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

                    path.setAttribute('d', `M ${startX} ${startY} H ${bendX} V ${endY} H ${endX}`);
                    path.classList.add('bracket-connector', `bracket-connector--${pathType}`);
                    path.dataset.fromMatchId = node.dataset.matchId;
                    path.dataset.toMatchId = targetId;
                    connectorLayer.appendChild(path);
                });
            });
        };

        const clearBracketFocus = () => {
            bracketCanvas?.classList.remove('has-bracket-focus');
            bracketNodes.forEach((node) => node.classList.remove('is-highlighted', 'is-competitor-highlighted'));
            connectorLayer?.querySelectorAll('.bracket-connector').forEach((path) => path.classList.remove('is-highlighted'));
        };

        const restoreSelectedBracketFocus = () => {
            if (selectedBracketFocus) {
                highlightBracketFocus(selectedBracketFocus.matchId, selectedBracketFocus.competitorId);
                return;
            }

            clearBracketFocus();
        };

        const highlightBracketFocus = (matchId, competitorId = null) => {
            if (! bracketCanvas || ! connectorLayer) return;

            clearBracketFocus();
            bracketCanvas.classList.add('has-bracket-focus');
            const relatedMatchIds = new Set([String(matchId)]);

            connectorLayer.querySelectorAll('.bracket-connector').forEach((path) => {
                if (path.dataset.fromMatchId === String(matchId) || path.dataset.toMatchId === String(matchId)) {
                    path.classList.add('is-highlighted');
                    relatedMatchIds.add(path.dataset.fromMatchId);
                    relatedMatchIds.add(path.dataset.toMatchId);
                }
            });

            bracketNodes.forEach((node) => {
                if (relatedMatchIds.has(node.dataset.matchId)) node.classList.add('is-highlighted');
                if (competitorId && node.querySelector(`[data-competitor-id="${competitorId}"]`)) node.classList.add('is-competitor-highlighted');
            });
        };

        bracketNodes.forEach((node) => {
            node.addEventListener('mouseenter', () => highlightBracketFocus(node.dataset.matchId));
            node.addEventListener('mouseleave', restoreSelectedBracketFocus);
        });

        document.querySelectorAll('.bracket-team').forEach((button) => button.addEventListener('click', () => {
            if (! button.dataset.competitorId) return;
            if (bracketLineupDirty) {
                const feedback = document.querySelector('[data-bracket-drag-feedback]');
                if (feedback) {
                    feedback.hidden = false;
                    feedback.textContent = 'Save the bracket lineup before declaring a winner or scheduling a game.';
                }
                return;
            }
            const match = button.closest('[data-bracket-match]');
            const panel = document.getElementById(`match-actions-${button.dataset.match}`);
            const other = [...match.querySelectorAll('.bracket-team')].find((candidate) => candidate !== button);

            selectedBracketFocus = {
                matchId: button.dataset.match,
                competitorId: button.dataset.competitorId,
            };

            document.querySelectorAll('.bracket-team').forEach((candidate) => {
                const isSelected = candidate === button;
                candidate.classList.toggle('is-click-selected', isSelected);
                candidate.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                candidate.querySelector('.bracket-selection-indicator')?.classList.toggle('hidden', ! isSelected);
            });

            highlightBracketFocus(selectedBracketFocus.matchId, selectedBracketFocus.competitorId);

            document.querySelectorAll('.match-actions').forEach((actions) => actions.classList.toggle('hidden', actions !== panel));

            if (! panel || ! other) return;

            panel.querySelector('.selected-winner').value = button.dataset.competitorId;
            panel.querySelector('.selected-loser-winner').value = other.dataset.competitorId;
            panel.classList.remove('hidden');
        }));

        document.querySelectorAll('[data-bracket-result-form], [data-schedule-form]').forEach((form) => form.addEventListener('submit', (event) => {
            if (bracketLineupDirty) event.preventDefault();
        }));

        document.querySelectorAll('[data-schedule-toggle]').forEach((button) => button.addEventListener('click', () => {
            const form = document.getElementById(button.getAttribute('aria-controls'));
            const opening = form?.classList.contains('hidden');
            form?.classList.toggle('hidden');
            button.setAttribute('aria-expanded', opening ? 'true' : 'false');
            if (opening) form.querySelector('input[type="date"]')?.focus();
        }));

        document.querySelectorAll('.bracket-team').forEach((button) => {
            button.addEventListener('mouseenter', (event) => {
                event.stopPropagation();
                highlightBracketFocus(button.dataset.match, button.dataset.competitorId);
            });
            button.addEventListener('mouseleave', () => {
                const matchNode = button.closest('[data-bracket-node]');
                selectedBracketFocus
                    ? restoreSelectedBracketFocus()
                    : matchNode?.matches(':hover')
                    ? highlightBracketFocus(button.dataset.match)
                    : clearBracketFocus();
            });
        });

        if (bracketCanvas) {
            requestAnimationFrame(drawBracketConnections);
            window.addEventListener('resize', drawBracketConnections);
            if ('ResizeObserver' in window) new ResizeObserver(drawBracketConnections).observe(bracketCanvas);
        }
    </script>
    <x-delete-schedule-dialog />
@endsection
