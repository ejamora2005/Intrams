@extends('layouts.coordinator', ['title' => 'Rules & Guidelines', 'workspaceLabel' => 'Tabulator workspace', 'homeRoute' => 'tabulator.dashboard'])
@section('content')
<div class="tab-workspace tab-stack" data-tabulator-workspace>
    <header class="tab-heading"><div><p class="tab-eyebrow">Before scoring</p><h2>Rules &amp; Guidelines</h2><p class="tab-muted">{{ $edition?->name ?? 'No active intramurals edition.' }}</p></div><a class="tab-button tab-button-secondary" href="{{ route('tabulator.possible-dq.index') }}">Possible DQ · {{ $flaggedStudents->count() }} flagged ↗</a></header>
    @include('tabulator.partials.return-nav')
    <section class="tab-panel tab-stack"><div><h3>Participation rules</h3><p class="tab-muted">Students with a major or minor event can join up to {{ $maxEvents }} events. Students without a major or minor can join up to {{ $individualDualOnlyMaxEvents }} individual/dual events.</p></div>
        <div class="tab-grid">@foreach ($allowedCombinations as $combination)<article class="tab-card tab-stack"><h4>{{ $combination['label'] }}</h4><p class="tab-muted tab-small">Major {{ $combination['major'] }} · Minor {{ $combination['minor'] }} · Individual / dual {{ $combination['individual_or_dual'] }}</p></article>@endforeach</div>
        <p class="tab-alert">Registrations outside the allowed combinations are flagged as Possible DQ. Ask GAM to review and correct the registration before scoring.</p>
    </section>
    <div class="tab-grid">
        <section class="tab-panel tab-stack"><h3>Scoring checklist</h3><ol class="list-decimal space-y-3 pl-5"><li>Verify the event and review eligibility issues.</li><li>Assign factions to the correct placements.</li><li>Review each faction and its awarded points.</li><li>Confirm with your tabulator password.</li><li>Check Results &amp; standings after saving.</li></ol></section>
        <section class="tab-panel tab-stack"><h3>Medical certificates</h3><p class="tab-muted">GAM reviews medical certificates for required events. These events are exempt:</p><div class="flex flex-wrap gap-2">@foreach ($medicalCertificateExemptions as $code)<span class="tab-pill">{{ Str::headline(strtolower($code)) }}</span>@endforeach</div><p class="tab-muted tab-small">Confirm outstanding certificate requirements with GAM before participation.</p></section>
    </div>
</div>
@endsection
