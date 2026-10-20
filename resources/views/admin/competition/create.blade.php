@extends('layouts.admin', ['title' => 'Add schedule', 'subtitle' => 'Schedule a sport, cultural event, or school activity in Schedules.'])

@section('content')
    <section class="ops-hero p-5 sm:p-7">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="ops-eyebrow">Schedules</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-950">Add a schedule</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Choose the event, set its exact time, and add the participants.</p>
            </div>
            <a href="{{ route('admin.schedules.matches.index') }}" class="inline-flex w-fit shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Back to Schedules</a>
        </div>
        @if ($editions->isNotEmpty())
            <form method="GET" action="{{ route('admin.schedules.matches.create') }}" class="mt-5 flex flex-wrap items-end gap-3">
                <label for="schedule-edition" class="min-w-0 flex-1 text-sm font-medium text-slate-700 sm:max-w-lg">Edition
                    <select id="schedule-edition" name="edition_id" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm">
                        @foreach ($editions as $item)
                            <option value="{{ $item->id }}" @selected($item->id === $edition?->id)>{{ $item->name }}{{ $item->status === 'active' ? ' (Active)' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Open edition</button>
            </form>
            <p class="mt-3 text-sm text-slate-500">Schedule dates: {{ $edition->starts_on->format('M j, Y') }} – {{ $edition->ends_on->format('M j, Y') }}.</p>
        @endif
    </section>

    @if (! $edition || $events->isEmpty())
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
            <p>{{ $edition ? 'No available events are configured for this edition.' : 'Create a draft or active edition before adding a schedule.' }}</p>
            <a href="{{ route('admin.editions.index') }}" class="mt-3 inline-block font-semibold text-blue-700">Manage editions</a>
        </section>
    @else
        @php
            $defaultDate = now()->toDateString();
            if ($defaultDate < $edition->starts_on->toDateString() || $defaultDate > $edition->ends_on->toDateString()) {
                $defaultDate = $edition->starts_on->toDateString();
            }
            $selectedEvent = (string) old('edition_sport_id', request('edition_sport_id', ''));
        @endphp
        <form method="POST" action="{{ route('admin.schedules.matches.store') }}" data-create-schedule data-ops-form class="mt-6 space-y-6">
            @csrf
            <input type="hidden" name="edition_id" value="{{ $edition->id }}">

            @if ($errors->any())
                <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">The schedule was not added. Please review these details:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-semibold text-slate-950">1. Event</h2>
                <div class="mt-4 grid min-w-0 gap-4 sm:grid-cols-2">
                    <label class="min-w-0 text-sm font-medium text-slate-700">Find an event
                        <input type="search" data-select-search="schedule-event" placeholder="Search basketball, chess, painting…" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm">
                    </label>
                    <label class="min-w-0 text-sm font-medium text-slate-700">Sport / cultural event / activity <span aria-hidden="true">*</span>
                        <select id="schedule-event" name="edition_sport_id" required class="mt-1 block w-full min-w-0 rounded-lg border-slate-300 py-2.5 text-sm" aria-describedby="schedule-event-help">
                            <option value="">Choose an event</option>
                            @foreach (['sports' => 'Sports', 'cultural' => 'Cultural events', 'activity' => 'School activities'] as $kind => $label)
                                <optgroup label="{{ $label }}">
                                    @foreach ($events->filter(fn ($event) => $eventDetails[$event->id]['kind'] === $kind) as $event)
                                        <option value="{{ $event->id }}" @selected($selectedEvent === (string) $event->id)>{{ $kind === 'activity' ? 'School activity (enter its name below)' : preg_replace('/^Cultural:\s*/i', '', $event->sport->name) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </label>
                    <label class="min-w-0 text-sm font-medium text-slate-700 sm:col-span-2">Schedule title <span data-schedule-title-optional class="font-normal text-slate-500">(optional)</span>
                        <input name="title" value="{{ old('title') }}" maxlength="160" placeholder="e.g. Finals, Heat 1, or the school activity name" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm">
                    </label>
                    <label class="min-w-0 text-sm font-medium text-slate-700 sm:col-span-2">Bracket match (optional)
                        <select name="bracket_match_id" class="mt-1 block w-full min-w-0 rounded-lg border-slate-300 text-sm">
                            <option value="">Standalone event schedule</option>
                            @foreach ($bracketOptions as $id => $option)<option value="{{ $id }}" @selected((string)old('bracket_match_id', request('bracket_match_id')) === (string)$id)>{{ $option['label'] }}</option>@endforeach
                        </select>
                        <span class="mt-1 block text-xs text-slate-500">Choose the matching event above. Linked schedules appear directly on its bracket.</span>
                    </label>
                </div>
                <p id="schedule-event-help" class="mt-3 text-sm text-slate-500">Choose a school activity for non-scoring program items such as Ruperto Run, the parade, or awarding.</p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-semibold text-slate-950">2. Date, time &amp; venue</h2>
                <div class="mt-4 grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="min-w-0 text-sm font-medium text-slate-700">Starts <span aria-hidden="true">*</span>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $defaultDate.'T08:00') }}" min="{{ $edition->starts_on->format('Y-m-d') }}T00:00" max="{{ $edition->ends_on->format('Y-m-d') }}T23:59" required class="mt-1 block w-full min-w-0 rounded-lg border-slate-300 px-3 py-2.5 text-sm">
                    </label>
                    <label class="min-w-0 text-sm font-medium text-slate-700">Ends <span aria-hidden="true">*</span>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $defaultDate.'T10:00') }}" min="{{ $edition->starts_on->format('Y-m-d') }}T00:00" max="{{ $edition->ends_on->format('Y-m-d') }}T23:59" required class="mt-1 block w-full min-w-0 rounded-lg border-slate-300 px-3 py-2.5 text-sm">
                    </label>
                    <label class="min-w-0 text-sm font-medium text-slate-700">Venue
                        <select name="venue" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm">
                            <option value="">Use event default</option>
                            @foreach ($venueOptions as $venue)
                                <option value="{{ $venue }}" @selected(old('venue') === $venue)>{{ $venue }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="min-w-0 text-sm font-medium text-slate-700">Status
                        <select name="status" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm">
                            @foreach ($scheduleStatuses as $status)
                                <option value="{{ $status }}" @selected(old('status', 'scheduled') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="min-w-0 text-sm font-medium text-slate-700 sm:col-span-2 lg:col-span-2">Facilitator
                        <select name="coordinator_id" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm">
                            <option value="">Unassigned</option>
                            @foreach ($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}" @selected((string) old('coordinator_id') === (string) $coordinator->id)>{{ $coordinator->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <p class="mt-3 text-sm text-slate-500">Use the local school date and time. The venue fills automatically when you change the event.</p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="schedule-participants-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="schedule-participants-title" class="text-lg font-semibold text-slate-950">3. Participants</h2>
                    <span data-schedule-participant-count class="text-sm text-slate-500" aria-live="polite"></span>
                </div>
                <p data-schedule-participant-help class="mt-2 text-sm leading-6 text-slate-500">Choose an event to see its registered teams, pairs, or athletes. You can leave sports participants unselected to announce them later.</p>
                <div data-schedule-participant-tools hidden class="mt-4 flex flex-wrap items-center gap-3">
                    <label class="min-w-0 flex-1"><span class="sr-only">Search participants</span><input type="search" data-schedule-participant-search placeholder="Search participants…" class="block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm"></label>
                    <button type="button" data-schedule-select-all class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">Select all</button>
                    <button type="button" data-schedule-clear class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700">Clear</button>
                </div>
                <div data-schedule-participants class="mt-4 grid max-h-80 gap-3 overflow-y-auto sm:grid-cols-2"></div>
                <p data-schedule-participant-empty hidden class="mt-3 text-sm text-slate-500">No participants match your search.</p>
                <noscript><p class="mt-3 text-sm text-red-700">Enable JavaScript to select participants. You can still create a sports schedule with participants to be announced; cultural events include all active factions automatically.</p></noscript>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-3 pb-3">
                <a href="{{ route('admin.schedules.matches.index') }}" class="rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700">Cancel</a>
                <button type="submit" data-loading-label="Adding schedule…" class="rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">Add schedule</button>
            </div>
        </form>
        <script type="application/json" id="schedule-create-options">@json(['events' => $eventDetails, 'selected' => old('participants', [])])</script>
    @endif
@endsection
