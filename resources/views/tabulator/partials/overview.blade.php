<div class="tab-kpis">
    @foreach ([['declare-sports', 'Awaiting placements', $pendingCount, 'Open scoring', 'amber'], ['match-winners', 'Ready matches', $matches->count(), 'Choose a match winner', 'blue'], ['declared-results', 'Declared events', $declaredCount, 'Review saved results', 'green']] as [$target, $title, $count, $detail, $tone])
        <a class="tab-card tab-kpi" href="#{{ $target }}" data-ops-view-shortcut="{{ $target }}" data-tone="{{ $tone }}"><span class="tab-muted">{{ $title }}</span><strong>{{ $count }}</strong><span class="tab-small">{{ $detail }} <span aria-hidden="true">→</span></span></a>
    @endforeach
    <a class="tab-card tab-kpi" href="{{ route('tabulator.possible-dq.index') }}" data-tone="red"><span class="tab-muted">Possible DQ</span><strong>{{ $flaggedStudents->count() }}</strong><span class="tab-small">Review eligibility <span aria-hidden="true">↗</span></span></a>
</div>
@if ($flaggedStudents->isNotEmpty())
    <div class="tab-alert"><strong>{{ $flaggedStudents->count() }} {{ Str::plural('registration', $flaggedStudents->count()) }} {{ $flaggedStudents->count() === 1 ? 'needs' : 'need' }} eligibility review.</strong> {{ $flaggedStudents->take(3)->map(fn ($evaluation) => $evaluation['student']->full_name)->implode(', ') }}. <a href="{{ route('tabulator.possible-dq.index') }}" class="tab-text-link">Open Possible DQ watchlist →</a></div>
@endif
<div class="tab-panel tab-start">
    <div><p class="tab-eyebrow">Start here</p><h3>Record event placements</h3><p class="tab-muted">Choose a sport or cultural event, assign factions to places, then review the points before confirming.</p><a href="{{ route('tabulator.dashboard', ['category' => 'cultural']) }}#declare-sports" class="tab-text-link">Score cultural events →</a></div>
    <a href="#declare-sports" data-ops-view-shortcut="declare-sports" class="tab-button">Score an event <span aria-hidden="true">→</span></a>
</div>
@include('tabulator.partials.standings')
<section class="tab-panel tab-stack">
    <div class="tab-section-heading"><div><p class="tab-eyebrow">Latest activity</p><h3>Recently declared results</h3></div><a href="#declared-results" data-ops-view-shortcut="declared-results" class="tab-text-link">View all results →</a></div>
    <div class="tab-grid">
        @forelse ($recentResults as $event)
            <a class="tab-card tab-event" href="{{ route('tabulator.dashboard', ['edition_sport_id' => $event->id]) }}#declare-sports">
                <span class="tab-state" data-state="declared">Declared</span><h4>{{ $event->sport->name }}</h4>
                <p class="tab-muted tab-small">{{ $event->sportResult->submitted_at?->format('M j, g:i A') }} · {{ $event->sportResult->submitter?->name ?? 'Tabulator' }}</p><span class="tab-text-link">Review placements →</span>
            </a>
        @empty
            <div class="tab-empty"><strong>No results declared yet</strong><p>Start with Score events. Saved placements and updated points will appear here.</p></div>
        @endforelse
    </div>
</section>
