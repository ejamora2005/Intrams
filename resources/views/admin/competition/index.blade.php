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

    <section class="rounded-xl border bg-white p-6">
        <h2 class="font-semibold">Competition schedule</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b text-left text-slate-500"><tr><th class="px-3 py-2">Edition</th><th class="px-3 py-2">Sport</th><th class="px-3 py-2">Schedule item</th><th class="px-3 py-2">Starts</th><th class="px-3 py-2">Venue</th><th class="px-3 py-2">Status</th></tr></thead>
                <tbody class="divide-y">@forelse($schedules as $schedule)<tr><td class="px-3 py-3">{{ $schedule->editionSport->edition->name }}</td><td class="px-3 py-3">{{ $schedule->editionSport->sport->name }}</td><td class="px-3 py-3">{{ $schedule->title ?: ($schedule->bracketMatch ? 'Game '.$schedule->bracketMatch->match_number : 'Competition') }}</td><td class="px-3 py-3">{{ $schedule->starts_at->format('M j, Y g:i A') }}</td><td class="px-3 py-3"><form method="POST" action="{{ route('admin.competition.schedule-venue.update', $schedule) }}" class="flex min-w-56 items-center gap-2">@csrf @method('PUT')<label for="venue-{{ $schedule->id }}" class="sr-only">Venue</label><input id="venue-{{ $schedule->id }}" name="venue" value="{{ $schedule->venue ?: 'TBA' }}" maxlength="120" class="w-36 rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:ring-blue-600"><button class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Save</button></form></td><td class="px-3 py-3">{{ ucfirst($schedule->status) }}</td></tr>@empty<tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No competitions are scheduled yet. Configure an edition sport, add eligible athletes or teams, then create its schedule.</td></tr>@endforelse</tbody>
            </table>
        </div>
        {{ $schedules->links() }}
    </section>
@endsection
