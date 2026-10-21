<section aria-label="Overall standings" class="tab-stack">
    <div class="tab-section-heading">
        <div><h3>{{ $scoreGroup === 'employee' ? 'Employee standings' : 'Student standings' }}</h3><span class="tab-muted tab-small">{{ $scoreGroup === 'employee' ? 'Employee scores kept separate from student totals' : 'Student points from declared event placements' }}</span></div>
        <div class="tab-actions" aria-label="Download declared results">
            <a class="tab-button {{ $scoreGroup === 'student' ? '' : 'tab-button-secondary' }}" href="{{ route('tabulator.dashboard', ['score_group' => 'student']) }}#declared-results">Student total</a>
            <a class="tab-button {{ $scoreGroup === 'employee' ? '' : 'tab-button-secondary' }}" href="{{ route('tabulator.dashboard', ['score_group' => 'employee']) }}#declared-results">Employee total</a>
            <a class="tab-button tab-button-secondary" href="{{ route('tabulator.results.export', ['format' => 'xlsx']) }}">Download Excel</a>
            <a class="tab-button tab-button-secondary" href="{{ route('tabulator.results.export', ['format' => 'pdf']) }}">Download PDF</a>
        </div>
    </div>
    <div class="tab-grid">
        @forelse ($standings as $row)
            <article class="tab-card tab-standing">
                <div class="tab-card-top"><span class="tab-rank">#{{ $row['rank'] }}</span><x-team-badge :team="$row['team']" :show-subtitle="false" size="md" /></div>
                <p class="tab-points">{{ number_format((float) $row['tally']->points, 2) }} <span>points</span></p>
                <div class="tab-medals"><span><i class="tab-medal gold"></i>{{ $row['tally']->gold_count }} Gold</span><span><i class="tab-medal silver"></i>{{ $row['tally']->silver_count }} Silver</span><span><i class="tab-medal bronze"></i>{{ $row['tally']->bronze_count }} Bronze</span></div>
            </article>
        @empty
            <p class="tab-empty">No active factions are available for this edition.</p>
        @endforelse
    </div>
</section>
