<div class="tab-section-heading"><div><p class="tab-eyebrow">Bracket results</p><h2>Declare match winners</h2><p class="tab-muted">Choose the winning team, pair, or athlete. Event placements are scored separately in Score events.</p></div><span class="tab-pill">{{ $matches->count() }} ready</span></div>
<div class="tab-stack" data-tab-filter-list>
    <div class="tab-toolbar"><label class="tab-search"><span>Find a match</span><input type="search" data-tab-search placeholder="Search sport, team, athlete, or venue"></label><label><span>Schedule</span><select data-tab-status><option value="">All ready matches</option><option value="scheduled">Scheduled</option><option value="unscheduled">Not scheduled</option></select></label></div>
    <p data-tab-count class="tab-muted tab-small" aria-live="polite"></p>
    <div class="tab-grid">
        @forelse ($matches as $match)
            @php $modalId = 'match-winner-modal-'.$match->id; @endphp
            <form method="POST" action="{{ route('tabulator.matches.result', $match) }}" data-ops-form data-tab-match-form data-review-modal="{{ $modalId }}" data-tab-filter-card data-status="{{ $match->schedule ? 'scheduled' : 'unscheduled' }}" data-search="{{ Str::lower($match->editionSport->sport?->name.' '.$match->competitorOne?->label.' '.$match->competitorTwo?->label.' '.$match->schedule?->venue) }}" class="tab-card tab-match tab-stack">
                @csrf
                <input type="hidden" name="match_id" value="{{ $match->id }}">
                <div class="tab-card-top"><span class="tab-eyebrow">Game {{ $match->match_number }} · Round {{ $match->round_number }}</span><span class="tab-state" data-state="ready">Ready</span></div><h3>{{ $match->editionSport->sport?->name }}</h3><p class="tab-muted tab-small">{{ Str::headline($match->bracket) }} · {{ $match->schedule ? $match->schedule->starts_at->format('M j, g:i A').' · '.$match->schedule->venue : 'Schedule not set' }}</p>
                <fieldset class="tab-stack"><legend class="tab-small tab-muted">Select the winning competitor</legend>
                    @foreach ([$match->competitorOne, $match->competitorTwo] as $competitor)
                        <label class="tab-competitor"><input type="radio" name="winner_competitor_id" value="{{ $competitor->id }}" data-competitor-name="{{ $competitor->label }}" required @checked((int) old('match_id') === $match->id && (int) old('winner_competitor_id') === $competitor->id)>@if ($competitor->team)<x-team-badge :team="$competitor->team" :show-subtitle="false" :logo-only="true" size="sm" />@endif<span>{{ $competitor->label }}</span><span class="tab-competitor-check" aria-hidden="true">✓</span></label>
                    @endforeach
                </fieldset>
                <p data-tab-score-error hidden role="alert" class="tab-alert"></p><button type="button" data-tab-review class="tab-button tab-button-secondary">Review winner →</button>
                <dialog id="{{ $modalId }}" data-ops-modal class="tab-dialog" aria-labelledby="match-review-title-{{ $match->id }}">
                    <div class="tab-dialog-heading"><div><p class="tab-eyebrow">{{ Str::headline($match->bracket) }} · Round {{ $match->round_number }} · Game {{ $match->match_number }}</p><h3 id="match-review-title-{{ $match->id }}">{{ $match->editionSport->sport?->name }}</h3></div><button type="button" data-ops-modal-close class="tab-close" aria-label="Close match result dialog">×</button></div>
                    <div class="tab-dialog-body"><div data-tab-review-summary class="tab-review-summary"></div><p class="tab-muted tab-small">Confirm the winning competitor before submitting. This records the bracket result.</p><label class="tab-password">Your tabulator password<input name="tabulator_password" type="password" required autocomplete="current-password" autofocus></label>@error('tabulator_password')<p class="tab-dialog-error">{{ $message }}</p>@enderror</div>
                    <div class="tab-dialog-footer"><button type="button" data-ops-modal-close class="tab-button tab-button-secondary">Back to match</button><button type="submit" data-loading-label="Recording winner…" class="tab-button">Confirm match winner</button></div>
                </dialog>
            </form>
        @empty
            <div class="tab-empty"><strong>No ready matches to score</strong><p>Matches appear after the administrator saves a bracket lineup. Completed games leave this list automatically.</p></div>
        @endforelse
        <p data-tab-filter-empty hidden class="tab-empty">No matches fit these filters. Try another search or select All ready matches.</p>
    </div>
</div>
