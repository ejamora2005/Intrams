@extends('layouts.admin', [
    'title' => $title ?? 'Sports',
    'subtitle' => $subtitle ?? 'Manage the sport catalogue, mechanics, and participants.',
])

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <form method="GET" action="{{ route(($cardTarget ?? 'bracket') === 'edit' ? 'admin.cultural.index' : 'admin.sports.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
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
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">Search</button>
        </form>
        @if ($showAddButton ?? true)
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
        @if ($search || ($category ?? '') !== '' || $status !== 'active')
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
        <div class="mt-5 space-y-8" aria-label="{{ $listLabel ?? 'Sports list' }}">
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
                            @php($cardUrl = ($cardTarget ?? 'bracket') === 'edit'
                                ? route('admin.sports.edit', ['sport' => $sport, 'edition_id' => $editionId])
                                : route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $editionId]))
                            <article role="link" tabindex="0" onclick="window.location='{{ $cardUrl }}'" onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ $cardUrl }}'; }" class="flex min-h-[18rem] cursor-pointer flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-lg font-semibold text-slate-900">{{ $sport->name }}</h3>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ ucfirst($sport->status) }}</span>
                                </div>

                                <p class="mt-2 text-sm text-slate-500">Category: <span class="font-medium text-slate-700">{{ $sport->participant_category_label }}</span></p>

                                <div class="mt-5 border-y border-slate-100 py-4">
                                    <p class="text-sm font-medium text-slate-500">Current edition configuration</p>
                                    <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ number_format($sport->edition_sports_count) }}</p>
                                </div>

                                <div class="mt-auto flex flex-wrap gap-3 pt-5">
                                    @if (($cardTarget ?? 'bracket') === 'edit')
                                        <a href="{{ route('admin.sports-points.index') }}" onclick="event.stopPropagation()" class="rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Point system</a>
                                    @else
                                        <a href="{{ route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Manage participants</a>
                                    @endif
                                    @if (str_contains(strtolower($sport->name), 'basketball'))
                                        <a href="{{ route('admin.sports.basketball-score-sheet', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50">Score sheet</a>
                                    @elseif (str_contains(strtolower($sport->name), 'volleyball'))
                                        <a href="{{ route('admin.sports.volleyball-score-sheet', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50">Score sheet</a>
                                    @endif
                                    <a href="{{ route('admin.sports.edit', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Edit</a>
                                </div>
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
