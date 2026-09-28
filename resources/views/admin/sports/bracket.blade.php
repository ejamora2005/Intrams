@extends('layouts.admin', ['title' => $sport->name.' bracket', 'subtitle' => $edition->name.' - '.ucwords(str_replace('_', ' ', $editionSport->game_mechanic))])

@section('content')
    @php
        $winnerRounds = $matches->get('winners', collect())->groupBy('round_number')->sortKeys();
        $loserRounds = $matches->get('losers', collect())->groupBy('round_number')->sortKeys();
        $finalMatches = $matches->get('finals', collect())->sortBy('round_number');
        $roundRobinRounds = $matches->get('round_robin', collect())->groupBy('round_number')->sortKeys();
        $lastWinnerRound = $winnerRounds->keys()->last();
        $maxWinnerMatches = max(1, $winnerRounds->map(fn ($round) => $round->count())->max() ?? 0);
        $canvasWidth = max(1120, ($winnerRounds->count() * 320) + ($finalMatches->isNotEmpty() ? 320 : 260));
        $canvasHeight = max(520, ($maxWinnerMatches * 150) + 180);
        $champion = $finalMatches->where('status', 'completed')->last()?->winnerCompetitor ?? $winnerRounds->get($lastWinnerRound, collect())->whereIn('status', ['completed', 'bye'])->last()?->winnerCompetitor;
    @endphp

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
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
                    <a href="{{ route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $edition->id]) }}" class="w-fit rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Manage participants</a>
                    @if ($matches->isNotEmpty() && in_array($editionSport->game_mechanic, ['single_elimination', 'double_elimination', 'round_robin'], true))
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

        @if (! in_array($editionSport->game_mechanic, ['single_elimination', 'double_elimination', 'round_robin'], true))
            <div class="m-6 rounded-lg border border-blue-100 bg-blue-50 p-5 text-sm text-blue-900">This sport does not use an automated tournament mechanic.</div>
        @elseif ($matches->isEmpty())
            <div class="m-6 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">Register at least two competitors in Manage Participants to generate the tournament. Teams, dual pairs, and individual athletes are supported.</div>
        @elseif ($editionSport->game_mechanic === 'round_robin')
            @php
                $roundRobinWidth = max(1120, ($roundRobinRounds->count() * 320) + 80);
                $roundRobinHeight = max(520, (($roundRobinRounds->map(fn ($round) => $round->count())->max() ?? 1) * 320) + 140);
            @endphp
            <div class="border-t border-slate-100 bg-slate-50 px-5 py-2 text-xs text-slate-500 sm:px-6">Every competitor plays every other competitor once. Scroll horizontally for rounds and vertically for large fields.</div>
            <div class="h-[calc(100vh-19rem)] min-h-[34rem] max-h-[52rem] overflow-auto bg-slate-100 p-4 sm:p-6" aria-label="Round-robin tournament canvas">
                <div class="bracket-canvas relative isolate rounded-lg border border-slate-300 bg-white p-6" data-bracket-canvas style="min-width: {{ $roundRobinWidth }}px; min-height: {{ $roundRobinHeight }}px;">
                    <svg class="bracket-connectors pointer-events-none absolute inset-0 z-0 h-full w-full overflow-visible" data-bracket-connectors aria-hidden="true"></svg>
                    <section class="relative z-10" aria-labelledby="round-robin-heading">
                        <div class="mb-5 flex items-center justify-between border-b-2 border-slate-900 pb-3">
                            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">League schedule</p><h3 id="round-robin-heading" class="mt-1 text-base font-semibold text-slate-900">Round Robin</h3></div>
                            <span class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600">{{ $roundRobinRounds->flatten(1)->count() }} {{ Str::plural('game', $roundRobinRounds->flatten(1)->count()) }}</span>
                        </div>
                        <div class="flex min-w-max items-start gap-12">
                            @foreach ($roundRobinRounds as $roundNumber => $roundMatches)
                                <div class="w-64">
                                    <h4 class="mb-4 border-b border-slate-300 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-600">Round {{ $roundNumber }}</h4>
                                    <div class="space-y-6">
                                        @foreach ($roundMatches as $match)
                                            <div class="bracket-node relative" data-bracket-node data-match-id="{{ $match->id }}">
                                                @include('admin.sports.partials.bracket-match', ['match' => $match])
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
                                                    $loserNext = $roundNumber === 1
                                                        ? $loserRounds->get(1, collect())->firstWhere('match_number', (int) ceil($match->match_number / 2))
                                                        : $loserRounds->get((2 * $roundNumber) - 2, collect())->firstWhere('match_number', $match->match_number);
                                                }
                                            @endphp
                                            <div class="bracket-node relative flex items-center" data-bracket-node data-match-id="{{ $match->id }}" data-winner-next-id="{{ $winnerNext?->id }}" data-loser-next-id="{{ $loserNext?->id }}">
                                                @include('admin.sports.partials.bracket-match', ['match' => $match])
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
                                        <p class="mt-2 truncate text-base font-bold text-slate-900">{{ $champion?->label ?? 'To be decided' }}</p>
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
@endsection
