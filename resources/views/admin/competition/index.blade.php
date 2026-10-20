@extends('layouts.admin', ['title' => $manageSchedules ? 'Schedules' : 'Live Competition', 'subtitle' => $manageSchedules ? 'Create and manage bracket and event schedules.' : 'Monitor competitions and open their brackets. Manage dates and venues in Schedules.'])

@section('content')
    @if ($manageSchedules)
        @include('admin.schedules._tabs', ['activeTab' => 'matches'])
    @endif
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Please review the schedule details.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" data-list-search-container>
        <div class="flex flex-col justify-between gap-2 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-semibold text-slate-950">Competition schedule</h2>
                @if ($manageSchedules)
                    <a href="{{ route('admin.schedules.matches.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">+ Add schedule</a>
                @else
                    <a href="{{ route('admin.schedules.matches.index') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Manage schedules</a>
                @endif
            </div>
            <form method="GET" class="flex min-w-0 items-center gap-2"><label class="relative block min-w-0 sm:w-80"><span class="sr-only">Search competition schedule</span><input type="search" name="search" value="{{ $search }}" data-list-search-input placeholder="Search event, sport, team, venue, or status" class="block w-full rounded-lg border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg></label><button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Search all</button></form>
        </div>
        <div class="flex justify-end border-b border-slate-200 px-5 py-2"><span data-list-search-count class="text-xs font-medium text-slate-500" aria-live="polite"></span></div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Event</th>
                        <th scope="col" class="px-5 py-3">Teams / participants</th>
                        <th scope="col" class="px-5 py-3">When &amp; where</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $schedule)
                        @php
                            $scheduleLabel = $schedule->title ?: ($schedule->bracketMatch ? 'Game '.$schedule->bracketMatch->match_number : 'Competition');
                            $isActivity = (bool) $schedule->is_activity;
                            $isCultural = ! $isActivity && str_starts_with(strtoupper((string) $schedule->editionSport->sport?->code), 'CULT-');
                            $participantTeams = $isCultural
                                ? $schedule->editionSport->edition->teams->where('status', 'active')->values()
                                : $schedule->participants->pluck('team')->filter()->unique('id')->values();
                            $participantNames = $schedule->participants
                                ->map(fn ($participant) => $participant->athleteEntry?->student?->full_name)
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp
                        <tr id="schedule-{{ $schedule->id }}" data-list-search-row data-search-text="{{ Str::lower($scheduleLabel.' '.($schedule->editionSport->sport?->name ?? '').' '.($schedule->editionSport->edition?->name ?? '').' '.($schedule->venue ?? '').' '.$schedule->status.' '.$participantTeams->pluck('name')->implode(' ').' '.$participantNames->implode(' ')) }}" class="scroll-mt-28 align-top target:bg-blue-900/40">
                            <td class="px-5 py-4 text-slate-700">
                                <span class="mb-2 block text-xs font-medium text-slate-400">{{ $schedule->editionSport->edition->name }}</span>
                                @if ($isActivity)
                                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">School activity</span>
                                @else
                                    <span class="flex items-center gap-2">
                                        <span class="ops-sport-inline-icon"><x-sport-group-icon :group="$schedule->editionSport->sport?->catalogue_group_key ?? 'other'" /></span>
                                        <span><span class="block font-semibold text-slate-900">{{ $schedule->editionSport->sport->name }}</span><span class="text-xs text-slate-500">{{ $schedule->editionSport->sport?->catalogue_group_label ?? 'Other Sports' }}</span></span>
                                    </span>
                                @endif
                                <p class="mt-3 font-medium leading-5 text-slate-800">{{ $scheduleLabel }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                @if ($schedule->bracketMatch)
                                    <x-programme-matchups :games="[['number' => $schedule->bracketMatch->match_number, 'home' => app(\App\Services\ScheduleBracketService::class)->participant($schedule->bracketMatch, 'home'), 'away' => app(\App\Services\ScheduleBracketService::class)->participant($schedule->bracketMatch, 'away')]]" />
                                @elseif ($participantTeams->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2">
                                        @foreach ($participantTeams as $team)
                                            <x-team-badge :team="$team" :show-subtitle="false" :logo-only="$participantTeams->count() > 2" class="font-medium text-slate-900" />
                                            @unless ($loop->last)
                                                <span class="text-xs font-semibold uppercase text-slate-400">vs</span>
                                            @endunless
                                        @endforeach
                                    </div>
                                @elseif ($participantNames->isNotEmpty())
                                    <span>{{ $participantNames->implode(' / ') }}</span>
                                @else
                                    <span class="text-slate-500">To be announced</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <span class="block font-medium text-slate-900">{{ $schedule->starts_at->format('M j, Y g:i A') }}</span>
                                @if ($schedule->ends_at)
                                    <span class="mt-1 block text-xs text-slate-500">Ends {{ $schedule->ends_at->format('g:i A') }}</span>
                                @endif
                                <span class="mt-3 inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $schedule->venue ?: 'TBA' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{{ $schedule->status }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($manageSchedules)
                                <div class="flex items-start gap-2">
                                <a href="{{ route('admin.schedules.matches.edit', $schedule) }}" class="rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-sm font-semibold text-blue-700">Edit schedule</a>
                                <x-delete-schedule-button :schedule="$schedule" />
                                </div>
                                @endif
                                @if ($schedule->bracket_match_id)
                                    <a class="mt-2 inline-block text-sm font-semibold text-blue-700" href="{{ route('admin.sports.bracket', ['sport' => $schedule->editionSport->sport_id, 'edition_id' => $schedule->editionSport->edition_id]) }}">Open bracket</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">No competitions are scheduled yet. Configure an edition sport, add eligible athletes or teams, then create its schedule.</td>
                        </tr>
                    @endforelse
                    <tr data-list-search-empty hidden><td colspan="5" class="px-5 py-10 text-center text-slate-500">No scheduled competitions match your search.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $schedules->links() }}
        </div>
    </section>
    @if ($manageSchedules)<x-delete-schedule-dialog />@endif
@endsection
