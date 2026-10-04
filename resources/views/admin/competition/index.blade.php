@extends('layouts.admin', ['title' => 'Live Competition', 'subtitle' => 'Scheduled competitions use the configured sport, eligible athletes or teams, and the edition schedule window.'])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Schedule was not saved.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-950">Competition schedule</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Edition</th>
                        <th scope="col" class="px-5 py-3">Sport</th>
                        <th scope="col" class="px-5 py-3">Schedule item</th>
                        <th scope="col" class="px-5 py-3">Teams / participants</th>
                        <th scope="col" class="px-5 py-3">Time</th>
                        <th scope="col" class="px-5 py-3">Venue</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $schedule)
                        @php
                            $scheduleLabel = $schedule->title ?: ($schedule->bracketMatch ? 'Game '.$schedule->bracketMatch->match_number : 'Competition');
                            $participantTeams = $schedule->participants->pluck('team')->filter()->unique('id')->values();
                            $participantNames = $schedule->participants
                                ->map(fn ($participant) => $participant->athleteEntry?->student?->full_name)
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp
                        <tr class="align-top">
                            <td class="px-5 py-4 font-medium text-slate-900">{{ $schedule->editionSport->edition->name }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $schedule->editionSport->sport->name }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $scheduleLabel }}</td>
                            <td class="px-5 py-4 text-slate-700">
                                @if ($participantTeams->isNotEmpty())
                                    <div class="flex flex-wrap items-center gap-2">
                                        @foreach ($participantTeams as $team)
                                            <x-team-badge :team="$team" class="font-medium text-slate-900" />
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
                                    <span class="mt-1 block text-xs text-slate-500">Ends {{ $schedule->ends_at->format('M j, Y g:i A') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $schedule->venue ?: 'TBA' }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{{ $schedule->status }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <details class="group min-w-80">
                                    <summary class="inline-flex cursor-pointer list-none items-center justify-center rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-600 [&::-webkit-details-marker]:hidden">
                                        Edit
                                    </summary>
                                    <form method="POST" action="{{ route('admin.competition.schedules.update', $schedule) }}" class="mt-3 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm sm:grid-cols-2">
                                        @csrf
                                        @method('PUT')

                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 sm:col-span-2">
                                            Schedule item
                                            <input name="title" value="{{ $schedule->title }}" maxlength="160" placeholder="{{ $scheduleLabel }}" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm font-medium normal-case tracking-normal text-slate-900 focus:border-blue-600 focus:ring-blue-600">
                                        </label>

                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Starts
                                            <input name="starts_at" type="datetime-local" value="{{ $schedule->starts_at->format('Y-m-d\TH:i') }}" required class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm font-medium normal-case tracking-normal text-slate-900 focus:border-blue-600 focus:ring-blue-600">
                                        </label>

                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Ends
                                            <input name="ends_at" type="datetime-local" value="{{ $schedule->ends_at?->format('Y-m-d\TH:i') }}" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm font-medium normal-case tracking-normal text-slate-900 focus:border-blue-600 focus:ring-blue-600">
                                        </label>

                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Venue
                                            <select name="venue" required class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm font-medium normal-case tracking-normal text-slate-900 focus:border-blue-600 focus:ring-blue-600">
                                                @foreach ($venueOptions as $venue)
                                                    <option value="{{ $venue }}" @selected(($schedule->venue ?: 'TBA') === $venue)>{{ $venue }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Status
                                            <select name="status" required class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm font-medium normal-case tracking-normal text-slate-900 focus:border-blue-600 focus:ring-blue-600">
                                                @foreach ($scheduleStatuses as $status)
                                                    <option value="{{ $status }}" @selected($schedule->status === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <div class="flex items-end justify-end sm:col-span-2">
                                            <button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Save schedule</button>
                                        </div>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-slate-500">No competitions are scheduled yet. Configure an edition sport, add eligible athletes or teams, then create its schedule.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $schedules->links() }}
        </div>
    </section>
@endsection
