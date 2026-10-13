@extends('layouts.admin', ['title' => 'Sports', 'subtitle' => 'Choose a sport to manage the events created under it.'])

@section('content')
    @php
        $groupedSports = $sports->groupBy(fn ($sport) => $sport->catalogue_group_key);
    @endphp

    <div class="space-y-8">
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
                            <a href="{{ route('admin.sport-modules.show', $sport) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-400">
                                <p class="font-semibold text-slate-900">{{ $sport->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">View and manage its events</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
@endsection
