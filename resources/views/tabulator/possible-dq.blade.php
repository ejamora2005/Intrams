@extends('layouts.coordinator', ['title' => 'Possible DQ Watchlist', 'workspaceLabel' => 'Tabulator workspace', 'homeRoute' => 'tabulator.dashboard'])
@section('content')
<div class="tab-workspace tab-stack" data-tabulator-workspace>
    <header class="tab-heading"><div><p class="tab-eyebrow">Eligibility review</p><h2>Possible DQ Watchlist</h2><p class="tab-muted">{{ $edition?->name ?? 'No active intramurals edition.' }}</p></div><a class="tab-button tab-button-secondary" href="{{ route('tabulator.rules.index') }}">Rules &amp; Guidelines ↗</a></header>
    @include('tabulator.partials.return-nav')
    <div class="tab-section-heading"><div><h3>Students requiring review</h3><p class="tab-muted">Review registration issues with GAM before declaring event placements.</p></div><span class="tab-state" data-state="review">{{ $flaggedStudents->count() }} flagged</span></div>
    <div class="tab-stack" data-tab-filter-list data-tab-count-type="students">
        <div class="tab-toolbar"><label class="tab-search"><span>Find a student</span><input type="search" data-tab-search placeholder="Search student, ID, event, or issue"></label></div><p data-tab-count class="tab-muted tab-small" aria-live="polite"></p>
        <div class="tab-grid">
            @forelse ($flaggedStudents as $evaluation)
                <article class="tab-card tab-stack" data-tab-filter-card data-search="{{ Str::lower($evaluation['student']->full_name.' '.$evaluation['student']->student_number.' '.collect($evaluation['entries'])->pluck('name')->implode(' ').' '.implode(' ', $evaluation['issues'])) }}">
                    <div><span class="tab-state" data-state="review">Possible DQ</span><h3 class="mt-3">{{ $evaluation['student']->full_name }}</h3><p class="tab-muted tab-small">{{ $evaluation['student']->student_number }}</p></div>
                    <div class="tab-placements-list">@foreach ($evaluation['entries'] as $entry)<p>{{ $entry['name'] }} · {{ $entry['slot_label'] }}</p>@endforeach</div>
                    <p class="tab-muted tab-small">{{ $evaluation['summary'] }}</p><p class="tab-alert">{{ implode(' ', $evaluation['issues']) }}</p>
                </article>
            @empty
                <div class="tab-empty"><strong>No Possible DQ students found</strong><p>No registrations are currently flagged for the active edition.</p></div>
            @endforelse
            <p data-tab-filter-empty hidden class="tab-empty">No students match your search.</p>
        </div>
    </div>
</div>
@endsection
