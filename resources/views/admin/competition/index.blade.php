@extends('layouts.admin', ['title' => 'Live Competition', 'subtitle' => 'Scheduled competitions use the configured sport, eligible athletes or teams, and the edition schedule window.'])

@section('content')
    <section class="rounded-xl border bg-white p-6">
        <h2 class="font-semibold">Competition schedule</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b text-left text-slate-500"><tr><th class="px-3 py-2">Edition</th><th class="px-3 py-2">Sport</th><th class="px-3 py-2">Starts</th><th class="px-3 py-2">Status</th></tr></thead>
                <tbody class="divide-y">@forelse($schedules as $schedule)<tr><td class="px-3 py-3">{{ $schedule->editionSport->edition->name }}</td><td class="px-3 py-3">{{ $schedule->editionSport->sport->name }}</td><td class="px-3 py-3">{{ $schedule->starts_at->format('M j, Y g:i A') }}</td><td class="px-3 py-3">{{ ucfirst($schedule->status) }}</td></tr>@empty<tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No competitions are scheduled yet. Configure an edition sport, add eligible athletes or teams, then create its schedule.</td></tr>@endforelse</tbody>
            </table>
        </div>
        {{ $schedules->links() }}
    </section>
@endsection
