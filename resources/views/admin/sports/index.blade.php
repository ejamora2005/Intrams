@extends('layouts.admin', [
    'title' => $title ?? 'Sports',
    'subtitle' => $subtitle ?? 'Manage the sport catalogue, mechanics, and participants.',
])

@section('content')
    <x-ops-feedback />
    @php
        $canManage = $edition && in_array($edition->status, ['draft', 'active'], true);
    @endphp
    <section class="ops-hero mb-6 p-5 sm:p-7">
        <p class="ops-eyebrow">Sports management</p>
        <h2 class="mt-2 text-2xl font-semibold">Sports events &amp; point systems</h2>
        <p class="mt-2 text-sm text-slate-500">Create events, edit their linked point system, and manage sports for the selected edition.</p>
    </section>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <form method="GET" action="{{ route(($cardTarget ?? 'bracket') === 'edit' ? 'admin.cultural.index' : 'admin.sports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="search" class="sr-only">Search sports</label>
                <input id="search" name="search" value="{{ $search ?? '' }}" placeholder="Search sports" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-52">
            </div>
            <div>
                <label for="edition_id" class="sr-only">Select edition</label>
                <select id="edition_id" name="edition_id" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-auto">
                    @forelse ($editions as $editionOption)
                        <option value="{{ $editionOption->id }}" @selected($editionId === $editionOption->id)>{{ $editionOption->name }} ({{ ucfirst($editionOption->status) }})</option>
                    @empty
                        <option value="">No edition available</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label for="status" class="sr-only">Filter sports by status</label>
                <select id="status" name="status" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-auto">
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                    <option value="archived" @selected($status === 'archived')>Archived</option>
                </select>
            </div>
            @if (($cardTarget ?? 'bracket') !== 'edit')
                <div>
                    <label for="category" class="sr-only">Filter sports by participant category</label>
                    <select id="category" name="category" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-auto">
                        <option value="">All categories</option>
                        @foreach (($participantCategories ?? []) as $categoryKey => $categoryLabel)
                            <option value="{{ $categoryKey }}" @selected(($category ?? '') === $categoryKey)>{{ $categoryLabel }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label for="point_system" class="sr-only">Filter by linked point system</label>
                <select id="point_system" name="point_system" class="w-full rounded-lg border-slate-300 bg-white text-sm sm:max-w-56">
                    <option value="">All point systems</option>
                    @foreach ($pointSystems as $option)<option value="{{ $option['key'] }}" @selected($system === $option['key'])>{{ $option['label'] }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">Search</button>
        </form>
        @if (($showAddButton ?? true) && $canManage)
            <a href="{{ route('admin.sports.create', ['edition_id' => $editionId]) }}" class="inline-flex w-fit rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                Add sport
            </a>
        @else
            <a href="{{ route('admin.sports-points.index') }}" class="inline-flex w-fit rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                Manage points
            </a>
        @endif
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ $sports->total() }} {{ Str::lower($title ?? 'sports') }} found{{ $search ? ' for “'.$search.'”' : '' }}.</p>
        @if ($search || ($category ?? '') !== '' || $status !== 'active' || $system !== '')
            <a href="{{ route(($cardTarget ?? 'bracket') === 'edit' ? 'admin.cultural.index' : 'admin.sports.index', ['edition_id' => $editionId]) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Clear filters</a>
        @endif
    </div>

    @php
        $groupDefinitions = \App\Models\Sport::CATALOGUE_GROUPS;
        $groupedSports = collect($groupDefinitions)->map(function (array $definition, string $key) use ($sports): array {
            return $definition + [
                'key' => $key,
                'sports' => $sports->getCollection()->filter(fn ($sport): bool => $sport->catalogue_group_key === $key)->values(),
            ];
        })->filter(fn (array $group): bool => $group['sports']->isNotEmpty());
    @endphp

    @if ($groupedSports->isNotEmpty())
        <div id="sports-list" class="mt-5 space-y-8" aria-label="{{ $listLabel ?? 'Sports list' }}">
            @foreach ($groupedSports as $group)
                <section class="ops-sport-group" data-sport-group="{{ $group['key'] }}" aria-labelledby="sport-group-{{ $group['key'] }}">
                    <div class="ops-sport-group-heading">
                        <div class="ops-sport-group-icon" aria-hidden="true">
                            <x-sport-group-icon :group="$group['key']" class="h-6 w-6" />
                        </div>
                        <div class="min-w-0">
                            <h2 id="sport-group-{{ $group['key'] }}" class="text-xl font-semibold text-slate-900">{{ $group['label'] }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $group['description'] }}</p>
                        </div>
                        <span class="ops-sport-group-count">{{ $group['sports']->count() }} {{ Str::plural('sport', $group['sports']->count()) }}</span>
                    </div>

                    <div class="mt-4 grid auto-rows-fr gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($group['sports'] as $sport)
                            @php
                                $event = $configurations->get($sport->id);
                                $pointSystem = $pointSystems->get($eventSystems[$sport->id] ?? '');
                                $cardUrl = ($cardTarget ?? 'bracket') === 'edit' || $sport->isTrackAndField()
                                ? route('admin.sports.edit', ['sport' => $sport, 'edition_id' => $editionId])
                                : route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $editionId]);
                            @endphp
                            <article data-sport-event="{{ $sport->id }}" class="flex min-h-[18rem] flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-lg font-semibold text-slate-900">@if (! $sport->trashed())<a href="{{ $cardUrl }}" class="hover:text-blue-700">{{ $sport->name }}</a>@else{{ $sport->name }}@endif</h3>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $status === 'archived' ? 'Archived' : ucfirst($event?->status ?? $sport->status) }}</span>
                                </div>

                                <p class="mt-2 text-sm text-slate-500">Category: <span class="font-medium text-slate-700">{{ $sport->participant_category_label }}</span></p>

                                <div class="mt-5 border-y border-slate-100 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Linked point system</p>
                                    <p class="mt-1 text-sm font-semibold text-blue-700">{{ $pointSystem['label'] ?? 'Current custom points (not linked)' }}</p>
                                    <dl class="mt-3 space-y-2 text-sm">
                                        @foreach ($eventRules[$sport->id] ?? [] as $rule)<div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $rule['label'] }}</dt><dd class="shrink-0 font-semibold text-slate-900">{{ number_format($rule['points'], 2) }} pts</dd></div>@endforeach
                                    </dl>
                                    @if ($event?->sportResult)<p class="mt-3 text-xs font-semibold text-green-700">Results declared</p>@endif
                                </div>

                                <div class="mt-auto flex flex-wrap gap-3 pt-5">
                                    @if ($status === 'archived')
                                        @if ($canManage && $event)
                                            <form method="POST" action="{{ route('admin.sports.restore', $sport->id) }}" data-ops-form>
                                                @csrf
                                                <input type="hidden" name="edition_id" value="{{ $editionId }}">
                                                <input type="hidden" name="event_version" value="{{ $versions[$sport->id] }}">
                                                <button type="submit" class="rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-sm font-semibold text-blue-700">Restore sport</button>
                                            </form>
                                        @endif
                                    @else
                                    @if (($cardTarget ?? 'bracket') === 'edit')
                                        <a href="{{ route('admin.sports-points.index') }}" onclick="event.stopPropagation()" class="rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Point system</a>
                                    @else
                                        <a href="{{ route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Manage participants</a>
                                    @endif
                                    @if (! str_starts_with(strtoupper($sport->code), 'CULT-'))
                                        <a href="{{ route('admin.sports.score-sheet', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50">Score sheet</a>
                                    @endif
                                    <a href="{{ route('admin.sports.edit', ['sport' => $sport, 'edition_id' => $editionId]) }}" class="rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Edit event &amp; points</a>
                                    @if ($canManage && $event && ! $event->sportResult)<button type="button" data-ops-modal-open="archive-sport-{{ $sport->id }}" class="rounded-lg border border-red-200 px-3.5 py-2 text-sm font-semibold text-red-700">Archive sport</button>@endif
                                    @endif
                                </div>
                                @if ($canManage && $event && $status !== 'archived' && ! $event->sportResult)
                                    <dialog id="archive-sport-{{ $sport->id }}" data-ops-modal class="w-[min(30rem,calc(100vw-2rem))] rounded-xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60">
                                        <form method="POST" action="{{ route('admin.sports.destroy', $sport) }}" data-ops-form class="p-5">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="edition_id" value="{{ $editionId }}">
                                            <input type="hidden" name="event_version" value="{{ $versions[$sport->id] }}">
                                            <h3 class="text-lg font-semibold">Archive sport?</h3>
                                            <p class="mt-3 text-sm text-slate-500">{{ $sport->name }} will be archived for {{ $edition->name }}. Its registrations, brackets and schedules are retained. You can restore it from Archived sports.</p>
                                            <div class="mt-5 flex flex-wrap justify-end gap-2"><button type="button" data-ops-modal-close class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Cancel</button><button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Archive sport</button></div>
                                        </form>
                                    </dialog>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
            {{ $emptyMessage ?? 'No sports found.' }}
        </div>
    @endif

    <div class="mt-6">
        {{ $sports->links() }}
    </div>
@endsection
