<section aria-label="Overall standings" class="tab-stack">
    <div class="tab-section-heading">
        <div><h3>{{ $scoreGroup === 'employee' ? 'Employee standings' : 'Student standings' }}</h3><span class="tab-muted tab-small">{{ $scoreGroup === 'employee' ? 'Employee scores kept separate from student totals' : 'Student points from declared event placements' }}</span></div>
        <div class="tab-actions" aria-label="Download declared results">
            <a class="tab-button {{ $scoreGroup === 'student' ? '' : 'tab-button-secondary' }}" href="{{ route('tabulator.dashboard', ['score_group' => 'student']) }}#declared-results">Student total</a>
            <a class="tab-button {{ $scoreGroup === 'employee' ? '' : 'tab-button-secondary' }}" href="{{ route('tabulator.dashboard', ['score_group' => 'employee']) }}#declared-results">Employee total</a>
            <a class="tab-button tab-button-secondary" href="{{ route('tabulator.results.export', ['format' => 'xlsx']) }}">Download Excel</a>
            <a class="tab-button tab-button-secondary" href="{{ route('tabulator.results.export', ['format' => 'pdf']) }}">Download PDF</a>
            @if (($pendingScoreUpdates ?? 0) > 0)
                <button type="button" class="tab-button" data-ops-modal-open="tabulator-score-update-modal">Update score ({{ $pendingScoreUpdates }})</button>
            @else
                <span class="tab-state" data-state="declared">Landing score published</span>
            @endif
        </div>
    </div>
    @if (($pendingScoreUpdateEvents ?? collect())->isNotEmpty())
        <section class="tab-panel tab-stack" aria-labelledby="pending-score-updates-title">
            <div class="tab-section-heading">
                <div>
                    <p class="tab-eyebrow">Not yet on the landing page</p>
                    <h3 id="pending-score-updates-title">Pending score updates</h3>
                    <p class="tab-muted tab-small">These saved declarations are visible to tabulators but are still waiting for the protected Update score action.</p>
                </div>
                <span class="tab-state" data-state="pending">{{ $pendingScoreUpdates }} pending</span>
            </div>
            <div class="tab-grid">
                @foreach ($pendingScoreUpdateEvents as $pendingEvent)
                    @php
                        $draftResult = $pendingEvent->sportResult;
                        $draftTeams = collect($draftResult?->placements_json ?? [])
                            ->map(fn ($teamId) => $teamsById->get((int) $teamId)?->name)
                            ->filter()
                            ->values();
                    @endphp
                    <article class="tab-card">
                        <div class="tab-card-top">
                            <div>
                                <p class="tab-eyebrow">{{ str_starts_with(strtoupper($pendingEvent->sport->code), 'CULT-') ? 'Cultural' : $pendingEvent->sport->catalogue_group_label }}</p>
                                <strong>{{ $pendingEvent->sport->name }}</strong>
                            </div>
                            <span class="tab-state" data-state="pending">Draft</span>
                        </div>
                        <p class="tab-muted tab-small">
                            @if ($draftTeams->isNotEmpty())
                                {{ $draftTeams->join(' · ') }}
                            @else
                                Placements saved for review
                            @endif
                        </p>
                        <a class="tab-text-link" href="{{ route('tabulator.dashboard', ['edition_sport_id' => $pendingEvent->id]) }}#declare-sports">Review declaration →</a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
    <div class="tab-grid">
        @forelse ($standings as $row)
            @php
                $publishedRow = ($publishedStandings ?? collect())->firstWhere('team.id', $row['team']->id);
                $workingPoints = (float) $row['tally']->points;
                $publishedPoints = (float) ($publishedRow['tally']->points ?? 0);
                $pendingPoints = $workingPoints - $publishedPoints;
            @endphp
            <article class="tab-card tab-standing">
                <div class="tab-card-top"><span class="tab-rank">#{{ $row['rank'] }}</span><x-team-badge :team="$row['team']" :show-subtitle="false" size="md" /></div>
                <p class="tab-points">{{ number_format($workingPoints, 2) }} <span>working total</span></p>
                <p class="tab-muted tab-small">Landing total: <strong>{{ number_format($publishedPoints, 2) }}</strong></p>
                @if (abs($pendingPoints) > 0.0001)
                    <p class="tab-muted tab-small">Unpublished change: <strong>{{ $pendingPoints > 0 ? '+' : '' }}{{ number_format($pendingPoints, 2) }}</strong></p>
                @endif
                <div class="tab-medals"><span><i class="tab-medal gold"></i>{{ $row['tally']->gold_count }} Gold</span><span><i class="tab-medal silver"></i>{{ $row['tally']->silver_count }} Silver</span><span><i class="tab-medal bronze"></i>{{ $row['tally']->bronze_count }} Bronze</span></div>
            </article>
        @empty
            <p class="tab-empty">No active factions are available for this edition.</p>
        @endforelse
    </div>
</section>
