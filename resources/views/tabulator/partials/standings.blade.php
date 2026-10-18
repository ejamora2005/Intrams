<section aria-label="Overall standings" class="tab-stack">
    <div class="tab-section-heading"><h3>Overall standings</h3><span class="tab-muted tab-small">Points from declared event placements</span></div>
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
