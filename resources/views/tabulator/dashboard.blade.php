@extends('layouts.coordinator', ['title' => 'Tabulator dashboard', 'workspaceLabel' => 'Tabulator workspace', 'homeRoute' => 'tabulator.dashboard'])

@section('content')
<div class="tab-workspace" data-tabulator-workspace>
    <x-ops-feedback />
    @if (request('modal'))<div data-ops-auto-modal="{{ request('modal') }}" hidden></div>@endif
    @if (session('ops_view'))<div data-ops-auto-view="{{ session('ops_view') }}" hidden></div>@endif
    <header class="tab-heading">
        <div><p class="tab-eyebrow">Tabulator workspace</p><h2>Scoring center</h2><p class="tab-muted">{{ $edition?->name ?? 'An administrator needs to activate an edition before scoring can begin.' }}</p></div>
        <a class="tab-button tab-button-secondary" href="{{ route('tabulator.rules.index') }}">Rules &amp; Guidelines <span aria-hidden="true">↗</span></a>
    </header>
    <nav class="tab-nav" aria-label="Scoring workspace">
        @foreach (['dashboard-overview' => 'Overview', 'declare-sports' => 'Score events', 'match-winners' => 'Match winners', 'declared-results' => 'Results & standings'] as $target => $label)
            <a href="#{{ $target }}" data-ops-view-link data-ops-view-target="{{ $target }}">{{ $label }}</a>
        @endforeach
    </nav>
    <section data-ops-view-panel="dashboard-overview" class="ops-view-panel tab-stack">@include('tabulator.partials.overview')</section>
    <section id="declare-sports" data-ops-view-panel="declare-sports" hidden class="ops-view-panel tab-stack">
        @if ($selectedSport)
            @include('tabulator.partials.score-editor')
        @else
            <div class="tab-section-heading"><div><p class="tab-eyebrow">Sports &amp; cultural results</p><h2>Choose an event to score</h2><p class="tab-muted">Sports and cultural events use the points configured by the administrator.</p></div><span class="tab-pill">{{ $pendingCount }} awaiting results</span></div>
            @include('tabulator.partials.event-cards', ['monitor' => false])
        @endif
    </section>
    <section id="declared-results" data-ops-view-panel="declared-results" hidden class="ops-view-panel tab-stack">
        <div class="tab-section-heading"><div><p class="tab-eyebrow">Placement monitor</p><h2>Results &amp; standings</h2><p class="tab-muted">Review recorded placements or open an event to make a correction.</p></div><span class="tab-state" data-state="declared">{{ $declaredCount }} of {{ $editionSports->count() }} declared</span></div>
        @include('tabulator.partials.standings')
        @include('tabulator.partials.event-cards', ['monitor' => true])
    </section>
    <section id="match-winners" data-ops-view-panel="match-winners" hidden class="ops-view-panel tab-stack">@include('tabulator.partials.matches')</section>
    <dialog id="tabulator-score-update-modal" data-ops-modal class="tab-dialog" aria-labelledby="score-update-title">
        <div class="tab-dialog-heading"><div><p class="tab-eyebrow">Publish landing score</p><h3 id="score-update-title">Update score</h3></div><button type="button" data-ops-modal-close class="tab-close" aria-label="Close score update dialog">×</button></div>
        <form method="POST" action="{{ route('tabulator.score-updates.publish') }}" data-ops-form>
            @csrf
            <div class="tab-dialog-body">
                <p class="tab-muted">This publishes the saved declarations and point changes to the public landing page. The current public totals stay unchanged until you confirm this step.</p>
                <label class="tab-password">Score update password<input type="password" name="score_update_password" required autocomplete="off" autofocus></label>
                @error('score_update_password')<p class="tab-dialog-error">{{ $message }}</p>@enderror
            </div>
            <div class="tab-dialog-footer"><button type="button" data-ops-modal-close class="tab-button tab-button-secondary">Cancel</button><button type="submit" data-loading-label="Publishing…" class="tab-button">Publish update</button></div>
        </form>
    </dialog>
</div>
@endsection
