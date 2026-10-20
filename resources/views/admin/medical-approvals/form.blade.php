@extends('layouts.admin', ['title' => $creating ? 'Add medical review' : 'Edit medical review', 'subtitle' => 'Choose the events and the medical decision to save.'])

@section('content')
    <x-ops-feedback />
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-500">{{ $edition->name }}</p><a href="{{ route('admin.medical-approvals.index') }}" class="text-sm font-semibold text-blue-700">Back to Medical Approvals</a></div>
    @if (!$profile)
        <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-950">Choose a player</h2>
            <form method="GET" action="{{ route('admin.medical-approvals.create') }}" class="mt-4 flex flex-col gap-3 sm:flex-row"><label class="min-w-0 flex-1"><span class="sr-only">Search players</span><input type="search" name="search" value="{{ $search }}" placeholder="Search name, student ID, faction, course, or sport" class="w-full rounded-lg border-slate-300 text-sm"></label><button class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">Search</button></form>
            <p class="mt-4 text-sm text-slate-500">{{ $choices->count() }} players with events awaiting a review record</p>
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @forelse ($choices as $choice)
                    <a href="{{ route('admin.medical-approvals.create', ['student_id' => $choice['student']->id]) }}" class="min-w-0 rounded-xl border border-slate-200 p-4 transition hover:border-blue-300"><p class="break-words font-semibold text-slate-950">{{ $choice['student']->full_name }}</p><p class="mt-1 break-all text-xs text-slate-500">{{ $choice['student']->student_number }}</p><div class="mt-3 text-sm">@if ($choice['team'])<x-team-badge :team="$choice['team']" />@else<span>Unassigned faction</span>@endif</div></a>
                @empty
                    <p class="py-8 text-sm text-slate-500">No players match. Existing reviews can be changed from Medical Approvals → Review / Edit.</p>
                @endforelse
            </div>
        </section>
    @else
        @php
            $selectedIds = collect(old('entry_ids', $entries->pluck('id')->all()))->map(fn ($id) => (string)$id);
            $commonNotes = $entries->pluck('medical_certificate_notes')->unique();
            $defaultStatus = $creating || $profile['status'] === 'mixed' ? '' : $profile['status'];
        @endphp
        <section class="max-w-5xl rounded-xl border border-slate-200 bg-white p-5 sm:p-7">
            <div class="flex flex-col justify-between gap-4 sm:flex-row"><div class="min-w-0"><h2 class="break-words text-xl font-semibold text-slate-950">{{ $profile['student']->full_name }}</h2><p class="mt-1 break-all text-sm text-slate-500">{{ $profile['student']->student_number }}</p><div class="mt-3 text-sm">@if ($profile['team'])<x-team-badge :team="$profile['team']" />@endif</div></div><a href="{{ $creating ? route('admin.medical-approvals.create') : route('admin.medical-approvals.show', $profile['student']) }}" class="w-fit text-sm font-semibold text-blue-700">{{ $creating ? 'Choose another player' : 'View current reviews' }}</a></div>
            @if ($entries->isEmpty())
                <p class="mt-6 text-sm text-slate-500">This player already has reviews for every required event.</p><a href="{{ route('admin.medical-approvals.edit', $profile['student']) }}" class="mt-4 inline-block rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white">Edit existing reviews</a>
            @else
                <form method="POST" action="{{ $creating ? route('admin.medical-approvals.store') : route('admin.medical-approvals.update', $profile['student']) }}" data-ops-form data-medical-review-form class="mt-6 space-y-6">
                    @csrf
                    @unless ($creating) @method('PUT') @endunless
                    <input type="hidden" name="student_id" value="{{ $profile['student']->id }}"><input type="hidden" name="edition_id" value="{{ $edition->id }}"><input type="hidden" name="version" value="{{ old('version', $version) }}">
                    <fieldset><legend class="font-semibold text-slate-950">Sports to review</legend><div class="mt-2 flex flex-wrap items-center gap-4 text-sm"><span data-medical-selection-count aria-live="polite"></span><button type="button" data-medical-select="all" class="font-semibold text-blue-700">Select all</button><button type="button" data-medical-select="none" class="font-semibold text-blue-700">Clear selection</button></div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ($entries as $entry)
                                <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4"><input type="checkbox" name="entry_ids[]" value="{{ $entry->id }}" @checked($selectedIds->contains((string)$entry->id)) class="mt-1 rounded border-slate-300 text-blue-700"><span class="min-w-0"><span class="block break-words font-semibold text-slate-950">{{ $entry->editionSport->sport->name }}</span><span class="mt-2 block">@include('admin.medical-approvals._status', ['status' => app(\App\Services\EligibilityService::class)->medicalCertificateStatusFor($entry)])</span>@if ($entry->medical_certificate_notes)<span class="mt-2 block break-words text-sm text-slate-500">{{ $entry->medical_certificate_notes }}</span>@endif</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="grid gap-5 sm:grid-cols-[minmax(0,240px)_minmax(0,1fr)]"><label class="min-w-0 text-sm font-semibold">Medical decision<select name="medical_certificate_status" required class="mt-2 block w-full rounded-lg border-slate-300 text-sm"><option value="">Choose status</option>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(old('medical_certificate_status', $defaultStatus) === $value)>{{ $label }}</option>@endforeach</select></label><label class="min-w-0 text-sm font-semibold">Review notes <span class="font-normal text-slate-500">(optional)</span><textarea name="medical_certificate_notes" rows="4" maxlength="1000" class="mt-2 block w-full rounded-lg border-slate-300 text-sm">{{ old('medical_certificate_notes', !$creating && $commonNotes->count() === 1 ? $commonNotes->first() : '') }}</textarea><span class="mt-2 block text-xs font-normal text-slate-500">The decision and notes apply only to the selected events.</span></label></div>
                    <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5"><button type="submit" data-loading-label="Saving review…" class="rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white">{{ $creating ? 'Create review' : 'Save changes' }}</button><a href="{{ route('admin.medical-approvals.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold">Cancel</a></div>
                </form>
            @endif
        </section>
    @endif
@endsection
