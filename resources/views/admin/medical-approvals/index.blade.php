@extends('layouts.admin', ['title' => 'Medical Approvals', 'subtitle' => 'Review medical clearances for registered players and their sports.'])

@section('content')
    <x-ops-feedback />
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div><h2 class="text-xl font-semibold text-slate-950">Medical clearances</h2><p class="mt-1 text-sm text-slate-500">{{ $edition?->name ?? 'No active intramurals edition.' }}</p></div>
        @if ($edition)<a href="{{ route('admin.medical-approvals.create') }}" class="inline-flex w-fit items-center justify-center rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white">Add medical review</a>@endif
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summary as $label => $count)
            <article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ $count }}</p></article>
        @endforeach
    </div>
    <form method="GET" action="{{ route('admin.medical-approvals.index') }}" class="mt-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:grid-cols-[minmax(0,1fr)_200px_200px_auto]">
        <label class="min-w-0 text-sm font-medium">Search players or sports<input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, student ID, faction, course, or sport" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
        <label class="min-w-0 text-sm font-medium">Faction<select name="team_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All factions</option>@foreach ($teams as $team)<option value="{{ $team->id }}" @selected((string)($filters['team_id'] ?? '') === (string)$team->id)>{{ $team->name }}</option>@endforeach</select></label>
        <label class="min-w-0 text-sm font-medium">Event status<select name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All statuses</option>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
        <div class="flex items-end gap-3"><button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Search</button><a href="{{ route('admin.medical-approvals.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold">Clear</a></div>
    </form>
    <section class="mt-6 space-y-3" aria-label="Players requiring medical clearance">
        <p class="text-sm text-slate-500">{{ $players->total() }} matching players · Up to 100 per page</p>
        @forelse ($players as $profile)
            <article data-medical-player="{{ $profile['student']->id }}" class="grid min-w-0 gap-4 rounded-xl border border-slate-200 bg-white p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-center">
                <div class="min-w-0"><h3 class="break-words font-semibold text-slate-950">{{ $profile['student']->full_name }}</h3><p class="mt-1 break-all text-xs text-slate-500">{{ $profile['student']->student_number }} · {{ $profile['student']->course?->code ?? 'No course' }}</p><div class="mt-3 text-sm">@if ($profile['team'])<x-team-badge :team="$profile['team']" />@else<span class="text-slate-500">Unassigned faction</span>@endif</div></div>
                <div class="min-w-0">@include('admin.medical-approvals._status', ['status' => $profile['status']])<p class="mt-2 text-sm text-slate-500">{{ $profile['entries']->count() }} required event{{ $profile['entries']->count() === 1 ? '' : 's' }} · {{ $profile['reviewed'] }} reviewed</p><p class="mt-2 break-words text-sm text-slate-700">{{ $profile['entries']->map(fn ($entry) => $entry->editionSport->sport->name)->implode(' · ') }}</p></div>
                <div class="flex flex-wrap gap-2"><a href="{{ route('admin.medical-approvals.show', $profile['student']) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold">View</a><a href="{{ route('admin.medical-approvals.edit', $profile['student']) }}" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Review / Edit</a></div>
            </article>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white px-5 py-12 text-center"><p class="font-semibold text-slate-950">No matching medical clearances</p><p class="mt-2 text-sm text-slate-500">Registered players appear here when their sports require a medical certificate.</p></div>
        @endforelse
        @if ($players->hasPages())<div class="pt-3">{{ $players->links() }}</div>@endif
    </section>
@endsection
