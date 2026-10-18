<div class="tab-stack" data-tab-filter-list>
    <div class="tab-toolbar">
        <label class="tab-search"><span>Find an event</span><input type="search" data-tab-search placeholder="Search event, category, or faction" autocomplete="off"></label>
        <label><span>Category</span><select data-tab-category><option value="">All categories</option><option value="sports">Sports</option><option value="cultural">Cultural</option><option value="track-field">Track &amp; field</option></select></label>
        <label><span>Result status</span><select data-tab-status><option value="">All results</option><option value="pending">Pending</option><option value="declared">Declared</option></select></label>
    </div>
    <p data-tab-count class="tab-muted tab-small" aria-live="polite"></p>
    <div class="tab-grid">
        @forelse ($editionSports as $event)
            @php
                $result = $event->sportResult;
                $category = str_starts_with(strtoupper($event->sport->code), 'CULT-') ? 'cultural' : ($event->sport->catalogue_group_key === 'track-field' ? 'track-field' : 'sports');
                $placedNames = collect($result?->placements_json ?? [])->map(fn ($teamId) => $teamsById->get((int) $teamId)?->name)->filter()->implode(' ');
            @endphp
            <article class="tab-card tab-event" data-tab-filter-card data-category="{{ $category }}" data-status="{{ $result ? 'declared' : 'pending' }}" data-search="{{ Str::lower($event->sport->name.' '.$event->sport->catalogue_group_label.' '.$placedNames) }}">
                <div class="tab-card-top"><span class="tab-icon"><x-sport-group-icon :group="$event->sport->catalogue_group_key" /></span><span class="tab-state" data-state="{{ $result ? 'declared' : 'pending' }}">{{ $result ? 'Declared' : 'Pending' }}</span></div>
                <div><p class="tab-eyebrow">{{ $category === 'cultural' ? 'Cultural' : $event->sport->catalogue_group_label }}</p><h3>{{ $event->sport->name }}</h3></div>
                @if ($monitor && $result)
                    <div class="tab-placements-list">@foreach ($result->placements_json ?? [] as $place => $teamId)<p><span class="tab-rank-small">#{{ $place }}</span><span>{{ $teamsById->get((int) $teamId)?->name ?? 'Removed faction' }}</span></p>@endforeach</div>
                    <p class="tab-muted tab-small">Saved {{ $result->submitted_at?->format('M j, g:i A') }}</p>
                @else
                    <p class="tab-muted tab-small">{{ count($eventPointRules->get($event->id, [])) }} placements · {{ $eventPointRules->get($event->id)[0]['points'] ?? 0 }} points for first place</p>
                @endif
                <a class="tab-button {{ $result ? 'tab-button-secondary' : '' }}" href="{{ route('tabulator.dashboard', ['edition_sport_id' => $event->id]) }}#declare-sports">{{ $result ? 'Review / edit results' : 'Enter placements' }} <span aria-hidden="true">→</span></a>
            </article>
        @empty
            <div class="tab-empty"><strong>No scoring events configured</strong><p>Ask an administrator to configure the events and their points for the active edition.</p></div>
        @endforelse
        <p data-tab-filter-empty hidden class="tab-empty">No events match these filters. Try another search or choose All categories and All results.</p>
    </div>
</div>
