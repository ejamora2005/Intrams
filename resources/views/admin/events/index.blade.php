@extends('layouts.admin', ['title' => 'Sports & Events', 'subtitle' => 'Configure competition events, timing, capacity, and lifecycle.'])

@section('content')
    <div class="flex justify-between"><a href="{{ route('admin.sports.index') }}" class="rounded-lg border px-4 py-2 text-sm">Manage sports</a><a href="{{ route('admin.events.create') }}" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Add event</a></div>
    <div class="mt-6 overflow-hidden rounded-xl border bg-white" data-list-search-container>
        <div class="flex flex-col justify-between gap-2 border-b bg-slate-50 px-5 py-4 sm:flex-row sm:items-center">
            <p data-list-search-count class="text-sm text-slate-500" aria-live="polite"></p>
            <label class="relative block min-w-0 sm:w-80"><span class="sr-only">Search events</span><input type="search" data-list-search-input placeholder="Search event, sport, edition, or status" class="block w-full rounded-lg border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600"><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg></label>
        </div>
        <table class="min-w-full divide-y text-sm">
            <thead><tr><th class="px-5 py-3 text-left">Event</th><th class="px-5 py-3 text-left">Sport / edition</th><th class="px-5 py-3 text-left">Schedule</th><th class="px-5 py-3 text-left">Status</th><th></th></tr></thead>
            <tbody class="divide-y">
                @forelse($events as $event)
                    <tr data-list-search-row data-search-text="{{ Str::lower($event->name.' '.$event->code.' '.($event->sport?->name ?? '').' '.($event->edition?->name ?? '').' '.$event->status) }}">
                        <td class="px-5 py-4"><b>{{ $event->name }}</b><div class="font-mono text-xs">{{ $event->code }}</div></td>
                        <td class="px-5 py-4">
                            <span class="flex items-center gap-2"><span class="ops-sport-inline-icon"><x-sport-group-icon :group="$event->sport?->catalogue_group_key ?? 'other'" /></span><span><span class="block">{{ $event->sport->name }}</span><span class="text-xs text-slate-500">{{ $event->sport?->catalogue_group_label ?? 'Other Sports' }}</span><span class="block text-xs">{{ $event->edition->name }}</span></span></span>
                        </td>
                        <td class="px-5 py-4">{{ $event->starts_at?->format('M j, Y g:i A') ?? 'Not scheduled' }}</td>
                        <td class="px-5 py-4">{{ ucfirst($event->status) }}</td>
                        <td class="px-5 py-4 text-right"><a class="text-blue-700" href="{{ route('admin.events.edit', $event) }}">Manage</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center">No events found. Add an active sport and edition first.</td></tr>
                @endforelse
                <tr data-list-search-empty hidden><td colspan="5" class="px-5 py-10 text-center text-slate-500">No events match your search.</td></tr>
            </tbody>
        </table>
        {{ $events->links() }}
    </div>
@endsection
