@extends('layouts.admin', ['title' => 'Edit edition', 'subtitle' => 'Maintain this intramurals period and its lifecycle status.'])

@section('content')
    @if (session('success'))
        <div class="mb-6 max-w-3xl rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.editions.update', $edition) }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @method('PUT')
        @include('admin.editions.form', ['submitLabel' => 'Save changes'])
    </form>

    <div class="mt-4 flex items-center justify-between gap-4 text-sm text-slate-500">
        <p>This edition currently has {{ $edition->teams_count }} team{{ $edition->teams_count === 1 ? '' : 's' }}. Archiving preserves all related records.</p>
        <a href="{{ route('admin.teams.index', ['edition_id' => $edition->id]) }}" class="shrink-0 font-semibold text-blue-700 hover:text-blue-900">Manage edition teams</a>
    </div>

    @php($groupedEditionSports = $edition->editionSports->groupBy(fn ($editionSport) => $editionSport->sport?->catalogue_group_key ?? 'other'))
    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold">Configured sports</h2>
                <p class="mt-1 text-sm text-slate-500">Participant type and game mechanics belong to this edition.</p>
            </div>
            <a href="{{ route('admin.editions.sports.create', $edition) }}" class="shrink-0 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Add sport</a>
        </div>

        @if ($groupedEditionSports->isNotEmpty())
            <div class="mt-5 space-y-6">
                @foreach (\App\Models\Sport::CATALOGUE_GROUPS as $groupKey => $groupDefinition)
                    @if ($groupedEditionSports->has($groupKey))
                        <div>
                            <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <span class="ops-sport-inline-icon"><x-sport-group-icon :group="$groupKey" /></span>
                                <span>{{ $groupDefinition['label'] }}</span>
                            </div>
                            <div class="divide-y rounded-lg border border-slate-200">
                                @foreach ($groupedEditionSports->get($groupKey) as $editionSport)
                                    <div class="flex items-center justify-between gap-4 px-3 py-3 text-sm">
                                        <span class="font-medium">{{ $editionSport->sport?->name }}</span>
                                        <span class="text-right text-slate-500">{{ ucfirst($editionSport->participant_type) }} · {{ str_replace('_', ' ', ucfirst($editionSport->game_mechanic)) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="py-5 text-sm text-slate-500">No sports configured yet.</p>
        @endif
    </section>
@endsection
