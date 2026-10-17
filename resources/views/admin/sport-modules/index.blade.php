@extends('layouts.admin', ['title' => 'Sports', 'subtitle' => 'Choose a sport to manage the events created under it.'])

@section('content')
    @php
        $groupedSports = $sports->groupBy(fn ($sport) => $sport->catalogue_group_key);
    @endphp

    <div class="space-y-6" data-list-search-container>
        <div class="flex flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
            <p data-list-search-count class="text-sm text-slate-500" aria-live="polite"></p>
            <label class="relative block min-w-0 sm:w-80"><span class="sr-only">Search sports</span><input type="search" data-list-search-input placeholder="Search sports" class="block w-full rounded-lg border-slate-300 py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg></label>
        </div>
        @foreach (\App\Models\Sport::CATALOGUE_GROUPS as $groupKey => $groupDefinition)
            @if ($groupedSports->has($groupKey))
                <section class="ops-sport-group" aria-labelledby="sport-module-group-{{ $groupKey }}">
                    <div class="ops-sport-group-heading">
                        <div class="ops-sport-group-icon" aria-hidden="true"><x-sport-group-icon :group="$groupKey" class="h-6 w-6" /></div>
                        <div class="min-w-0">
                            <h2 id="sport-module-group-{{ $groupKey }}" class="text-xl font-semibold text-slate-900">{{ $groupDefinition['label'] }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $groupDefinition['description'] }}</p>
                        </div>
                        <span class="ops-sport-group-count">{{ $groupedSports->get($groupKey)->count() }} {{ Str::plural('sport', $groupedSports->get($groupKey)->count()) }}</span>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($groupedSports->get($groupKey) as $sport)
                            <a data-list-search-row data-search-text="{{ Str::lower($sport->name.' '.$groupDefinition['label']) }}" href="{{ route('admin.sport-modules.show', $sport) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-400">
                                <p class="font-semibold text-slate-900">{{ $sport->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">View and manage its events</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
        <p data-list-search-empty hidden class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">No sports match your search.</p>
    </div>
@endsection
