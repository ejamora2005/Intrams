@props(['results' => collect(), 'unavailable' => false])

<dialog id="event-results" class="public-results-dialog" aria-labelledby="event-results-heading" data-public-event-results>
    <header class="public-results-header">
        <div>
            <p class="public-results-eyebrow">Competition results</p>
            <h2 id="event-results-heading">Declared event results</h2>
            <p>Placed factions and points for every declared event in the active intramurals edition.</p>
        </div>
        <form method="dialog"><button class="public-results-close" aria-label="Close event results" title="Close results">&times;</button></form>
    </header>
    <div class="public-results-toolbar">
        <div class="public-results-filters">
            <label>Search results<input type="search" data-public-results-search placeholder="Search an event or faction" autocomplete="off"></label>
            <label>Category<select data-public-results-category><option value="">All events</option><option value="sports">Sports</option><option value="cultural">Cultural</option><option value="track-field">Track &amp; Field</option></select></label>
            <a class="public-results-refresh" href="{{ url('/') }}#event-results">Refresh results</a>
        </div>
        <div class="public-results-status"><p data-public-results-count aria-live="polite">{{ $results->count() }} declared {{ Str::plural('event', $results->count()) }}</p><span>Newest first &middot; Philippine time (UTC+8)</span></div>
    </div>
    <div class="public-results-content">
        @if ($unavailable)
            <p class="public-results-empty" role="status">Results are temporarily unavailable. Please refresh to try again.</p>
        @elseif ($results->isEmpty())
            <div class="public-results-empty"><h3>No declared results yet</h3><p>Results will appear here once event placements are declared.</p></div>
        @else
            <div class="public-results-grid">
                @foreach ($results as $result)
                    <article class="public-result-card" data-public-result data-category="{{ $result['category'] }}" data-search="{{ Str::lower($result['name'].' '.$result['placements']->pluck('team_name')->implode(' ')) }}" aria-labelledby="public-result-{{ $result['id'] }}">
                        <header>
                            <span class="public-result-category">{{ $result['category_label'] }}</span>
                            <h3 id="public-result-{{ $result['id'] }}">{{ $result['name'] }}</h3>
                            <p class="public-result-time">Declared <x-result-time :value="$result['declared_at']" /></p>
                        </header>
                        <ol class="public-result-placements">
                            @forelse ($result['placements'] as $placement)
                                <li>
                                    <div class="public-result-faction">
                                        @if ($placement['team_logo'])<img src="{{ asset($placement['team_logo']) }}" alt="" width="36" height="36" loading="lazy">@endif
                                        <div><span class="public-result-place">{{ $placement['label'] }}</span><strong>{{ $placement['team_name'] }}</strong></div>
                                    </div>
                                    <span class="public-result-points">{{ number_format($placement['points'], 2) }} <small>pts</small></span>
                                </li>
                            @empty
                                <li>No placements available.</li>
                            @endforelse
                        </ol>
                    </article>
                @endforeach
            </div>
            <p class="public-results-empty" data-public-results-empty hidden>No results match your search. Try another event, faction, or category.</p>
        @endif
    </div>
    <footer class="public-results-footer">Results and standings may change when event declarations are updated.</footer>
</dialog>
