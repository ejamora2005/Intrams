@extends('layouts.admin', ['title' => 'Cultural events', 'subtitle' => 'Manage cultural competitions and their linked point systems.'])

@section('content')
    <x-ops-feedback />
    @php($canManage = $edition && in_array($edition->status, ['draft', 'active'], true))
    <section class="ops-hero p-5 sm:p-7">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="ops-eyebrow">Cultural management</p>
                <h2 class="mt-2 text-2xl font-semibold">Cultural events</h2>
                <p class="mt-2 text-sm text-slate-500">Add competitions, choose their points, and make them available for Tabulator scoring.</p>
                <p class="mt-2 text-sm text-slate-500">When declaring cultural winners, a faction may receive multiple placements.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.sports-points.index') }}" class="rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700">Manage point systems</a>
                @if ($canManage)
                    <a href="{{ route('admin.cultural.create', ['edition_id' => $edition->id]) }}" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Add cultural event</a>
                @endif
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('admin.cultural.index') }}" class="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-[minmax(12rem,1fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_auto_auto]">
        <label class="text-xs font-semibold text-slate-500">Search cultural events
            <input name="search" type="search" value="{{ $search }}" placeholder="Event name or description" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="text-xs font-semibold text-slate-500">Edition
            <select name="edition_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                @foreach ($editions as $option)
                    <option value="{{ $option->id }}" @selected($edition?->id === $option->id)>{{ $option->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-500">Point system
            <select name="point_system" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                <option value="">All point systems</option>
                @foreach ($pointSystems as $option)
                    <option value="{{ $option['key'] }}" @selected($system === $option['key'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-500">Status
            <select name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                <option value="active" @selected($status === 'active')>Current events</option>
                <option value="archived" @selected($status === 'archived')>Archived events</option>
            </select>
        </label>
        <button class="self-end rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700">Search</button>
    </form>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-2 text-sm text-slate-500">
        <p>{{ $events->count() }} {{ Str::plural('cultural event', $events->count()) }} found.</p>
        @if ($search || $system || $status === 'archived')
            <a href="{{ route('admin.cultural.index', ['edition_id' => $edition?->id]) }}" class="font-semibold text-blue-700">Clear filters</a>
        @endif
    </div>

    <section id="sports-list" class="mt-4" aria-label="Cultural events list">
        <div id="sport-group-other" class="grid items-start gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($events as $event)
                @php($pointSystem = $pointSystems->get($eventSystems[$event->id]))
                <article data-cultural-event="{{ $event->id }}" class="flex h-full min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="min-w-0 break-words text-lg font-semibold text-slate-900">{{ $event->sport->name }}</h3>
                        <span class="shrink-0 rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">{{ ucfirst($event->status) }}</span>
                    </div>
                    @if ($event->sport->description)
                        <p class="mt-2 text-sm text-slate-500">{{ $event->sport->description }}</p>
                    @endif
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Linked point system</p>
                    <p class="mt-1 text-sm font-semibold text-blue-700">{{ $pointSystem['label'] ?? 'No point system selected' }}</p>
                    <dl class="mt-3 space-y-2 border-y border-slate-100 py-3 text-sm">
                        @forelse ($eventRules[$event->id] as $rule)
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $rule['label'] }}</dt><dd class="shrink-0 font-semibold text-slate-900">{{ number_format($rule['points'], 2) }} pts</dd></div>
                        @empty
                            <p class="text-slate-500">No scoring placements configured.</p>
                        @endforelse
                    </dl>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span>{{ ucfirst($event->participant_type) }} participation</span>
                        @if ($event->sportResult)<span class="rounded-full bg-green-50 px-2 py-1 font-semibold text-green-700">Results declared</span>@endif
                    </div>
                    <div class="mt-auto flex flex-wrap gap-2 pt-5">
                        @if ($status === 'archived' && $canManage)
                            <form method="POST" action="{{ route('admin.cultural.restore', $event) }}" data-ops-form>
                                @csrf
                                <input type="hidden" name="event_version" value="{{ $versions[$event->id] }}">
                                <button class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-700">Restore event</button>
                            </form>
                        @else
                            <a href="{{ route('admin.cultural.edit', $event) }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white">{{ $canManage ? 'Edit event & points' : 'View event' }}</a>
                            @if ($canManage && ! $event->sportResult)
                                <button type="button" data-ops-modal-open="archive-cultural-{{ $event->id }}" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Archive event</button>
                            @endif
                        @endif
                    </div>
                    @if ($canManage && $status !== 'archived' && ! $event->sportResult)
                        <dialog id="archive-cultural-{{ $event->id }}" data-ops-modal class="w-[min(30rem,calc(100vw-2rem))] rounded-xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60">
                            <form method="POST" action="{{ route('admin.cultural.destroy', $event) }}" data-ops-form class="p-5">
                                @csrf @method('DELETE')
                                <input type="hidden" name="event_version" value="{{ $versions[$event->id] }}">
                                <h3 class="text-lg font-semibold">Archive cultural event?</h3>
                                <p class="mt-3 text-sm text-slate-500">{{ $event->sport->name }} will be removed from the current event and scoring lists for this edition. Its records stay available, and you can restore it from Archived events.</p>
                                <div class="mt-5 flex flex-wrap justify-end gap-2">
                                    <button type="button" data-ops-modal-close class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Cancel</button>
                                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Archive event</button>
                                </div>
                            </form>
                        </dialog>
                    @endif
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-slate-300 px-5 py-10 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">No cultural events match these filters.</p>
            @endforelse
        </div>
    </section>
@endsection
