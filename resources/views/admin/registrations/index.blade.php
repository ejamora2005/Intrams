@extends('layouts.admin', ['title' => 'Athletes', 'subtitle' => 'Add athletes to scheduled events and retain participation history.'])

@section('content')
    <div class="flex justify-between">
        <a href="{{ route('admin.participation-rules.index') }}" class="rounded-lg border px-4 py-2 text-sm">Participation rules</a>
        <a href="{{ route('admin.registrations.create') }}" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Add athlete</a>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border bg-white" data-list-search-container>
        <div class="flex flex-col justify-between gap-2 border-b bg-slate-50 px-5 py-4 sm:flex-row sm:items-center">
            <p data-list-search-count class="text-sm text-slate-500" aria-live="polite"></p>
            <label class="relative block min-w-0 sm:w-80"><span class="sr-only">Search athletes</span><input type="search" data-list-search-input placeholder="Search student, event, team, or status" class="block w-full rounded-lg border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg></label>
        </div>
        <table class="min-w-full divide-y text-sm">
            <thead>
                <tr>
                    <th class="px-5 py-3 text-left">Student</th>
                    <th class="px-5 py-3 text-left">Event</th>
                    <th class="px-5 py-3 text-left">Team</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($registrations as $registration)
                    <tr data-list-search-row data-search-text="{{ Str::lower($registration->student->full_name.' '.$registration->event->name.' '.($registration->team?->name ?? '').' '.$registration->status) }}">
                        <td class="px-5 py-4">{{ $registration->student->full_name }}</td>
                        <td class="px-5 py-4">{{ $registration->event->name }}</td>
                        <td class="px-5 py-4">
                            @if ($registration->team)
                                <x-team-badge :team="$registration->team" class="font-medium text-slate-900" />
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">{{ ucfirst($registration->status) }}</td>
                        <td class="px-5 py-4">
                            @if ($registration->status === 'active')
                                <form method="POST" action="{{ route('admin.registrations.withdraw', $registration) }}">
                                    @csrf
                                    <button class="text-red-700">Withdraw</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center">No athletes found.</td>
                    </tr>
                @endforelse
                <tr data-list-search-empty hidden><td colspan="5" class="px-5 py-10 text-center text-slate-500">No athletes match your search.</td></tr>
            </tbody>
        </table>

        {{ $registrations->links() }}
    </div>
@endsection
