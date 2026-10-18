@php
    $currentPlacements = $selectedSport->sportResult?->placements_json ?? [];
    $hasResult = $selectedSport->sportResult !== null;
    $restoreSelection = (int) old('edition_sport_id') === $selectedSport->id;
@endphp
<div class="tab-section-heading"><a class="tab-text-link" href="{{ route('tabulator.dashboard') }}#declare-sports">← All scoring events</a>@if ($nextPendingSport)<a class="tab-text-link" href="{{ route('tabulator.dashboard', ['edition_sport_id' => $nextPendingSport->id]) }}#declare-sports">Next pending event →</a>@endif</div>
<section class="tab-panel tab-stack">
    <div class="tab-section-heading"><div><p class="tab-eyebrow">{{ $selectedSport->sport->catalogue_group_label }}</p><h2>{{ $selectedSport->sport->name }}</h2><p class="tab-muted">Assign the placed factions, then review your declaration.</p></div><span class="tab-state" data-state="{{ $hasResult ? 'declared' : 'pending' }}">{{ $hasResult ? 'Editing declared results' : 'Not yet declared' }}</span></div>
    @if ($hasResult)<p class="tab-alert">These results were saved by {{ $selectedSport->sportResult->submitter?->name ?? 'a tabulator' }} on {{ $selectedSport->sportResult->submitted_at?->format('M j, Y g:i A') }}. Saving will replace this event’s placements and recalculate the standings.</p>@endif
    @if ($teams->isEmpty())<p class="tab-empty">No active factions are available. Ask an administrator to add factions before scoring.</p>@else
    <form method="POST" action="{{ route('tabulator.sport-results.store') }}" data-ops-form data-tab-score-form data-declare-winners data-allow-repeated-winners="{{ count($pointRules) > 3 ? 'true' : 'false' }}" data-review-modal="tabulator-declare-sports-modal" class="tab-stack">
        @csrf
        <input type="hidden" name="edition_sport_id" value="{{ $selectedSport->id }}">
        <div class="tab-placement-grid">
            @foreach ($pointRules as $rule)
                <label class="tab-placement-card">
                    <span class="tab-card-top"><span class="tab-rank-small">#{{ $rule['placement'] }}</span><span class="tab-point-chip">{{ number_format($rule['points'], 2) }} pts</span></span><strong>{{ $rule['label'] }}</strong><span class="tab-muted tab-small">Choose a faction</span>
                    <select name="placements[{{ $rule['placement'] }}]" data-placement-select data-placement-label="{{ $rule['label'] }}" data-placement-points="{{ $rule['points'] }}">
                        <option value="">Not assigned</option>
                        @foreach ($teams as $team)<option value="{{ $team->id }}" @selected((int) ($restoreSelection ? old('placements.'.$rule['placement'], $currentPlacements[$rule['placement']] ?? 0) : ($currentPlacements[$rule['placement']] ?? 0)) === $team->id)>{{ $team->name }}</option>@endforeach
                    </select>
                </label>
            @endforeach
        </div>
        <p class="tab-muted tab-small">{{ count($pointRules) > 3 ? 'This event allows a faction to receive more than one placement.' : 'Each faction can receive one placement in this event.' }} Only selected placements receive points.</p>
        <p data-tab-score-error hidden role="alert" class="tab-alert"></p>
        <div class="tab-review-bar"><span data-tab-selected-count class="tab-muted">Choose at least one placement.</span><button type="button" data-tab-review class="tab-button">Review placements →</button></div>
        <dialog id="tabulator-declare-sports-modal" data-ops-modal class="tab-dialog" aria-labelledby="placement-review-title">
            <div class="tab-dialog-heading"><div><p class="tab-eyebrow">Confirm event results</p><h3 id="placement-review-title">{{ $selectedSport->sport->name }}</h3></div><button type="button" data-ops-modal-close class="tab-close" aria-label="Close declaration dialog">×</button></div>
            <div class="tab-dialog-body"><div data-tab-review-summary class="tab-review-summary"></div><p class="tab-muted tab-small">{{ $hasResult ? 'This replaces the saved placements.' : 'The selected placements will be saved.' }} Overall points update immediately.</p><label class="tab-password">Your tabulator password<input type="password" name="tabulator_password" required autocomplete="current-password" autofocus></label>@error('tabulator_password')<p class="tab-dialog-error">{{ $message }}</p>@enderror</div>
            <div class="tab-dialog-footer"><button type="button" data-ops-modal-close class="tab-button tab-button-secondary">Back to editing</button><button type="submit" data-loading-label="Saving results…" class="tab-button">{{ $hasResult ? 'Confirm updated results' : 'Confirm and save results' }}</button></div>
        </dialog>
    </form>
    @endif
</section>
