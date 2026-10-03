@extends('layouts.coordinator', ['title' => 'Coordinator dashboard'])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="ops-hero p-5 sm:p-7 lg:p-8">
        <p class="ops-eyebrow">Coordinator workspace</p>
        <div class="mt-2 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div class="min-w-0">
                <h1 class="ops-display">My events</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">You can work only on events assigned to your account. Request changes here when an event needs to be added, removed, or reassigned.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:min-w-80">
                <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Assigned</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-950">{{ $assignments->count() }}</p>
                </div>
                <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Pending</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-950">{{ $requests->where('status', 'pending')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
                <div>
                    <p class="ops-eyebrow">Active assignments</p>
                    <h2 class="text-base font-semibold text-slate-950">Event access</h2>
                </div>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($assignments as $assignment)
                    <div class="py-3">
                        <p class="font-medium text-slate-900">{{ $assignment->event?->name ?? 'Removed event' }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ ucfirst($assignment->event?->status ?? 'unknown') }}</p>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">You do not have an active event assignment yet.</p>
                @endforelse
            </div>
        </section>

        <form method="POST" action="{{ route('coordinator.requests.store') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            @csrf
            <p class="ops-eyebrow">Admin review</p>
            <h2 class="text-base font-semibold text-slate-950">Request an assignment change</h2>
            <div class="mt-4 space-y-4">
                <select name="request_type" class="block w-full rounded-lg border-slate-300 py-2.5 text-sm">
                    <option value="add_event">Add event</option>
                    <option value="remove_event">Remove event</option>
                    <option value="reassign">Reassign to event</option>
                </select>
                <select name="source_event_id" class="block w-full rounded-lg border-slate-300 py-2.5 text-sm">
                    <option value="">Current assigned event (for reassign only)</option>
                    @foreach($assignments as $assignment)
                        <option value="{{ $assignment->event?->id }}">{{ $assignment->event?->name }}</option>
                    @endforeach
                </select>
                <select name="event_id" class="block w-full rounded-lg border-slate-300 py-2.5 text-sm" required>
                    <option value="">Choose requested event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}">{{ $event->name }}{{ $event->division ? ' - '.$event->division : '' }}</option>
                    @endforeach
                </select>
                <textarea name="reason" required rows="4" placeholder="Explain the request" class="block w-full rounded-lg border-slate-300 text-sm"></textarea>
                @error('event_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                @error('source_event_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                @error('reason')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Submit request</button>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end">
            <div>
                <p class="ops-eyebrow">Request log</p>
                <h2 class="text-base font-semibold text-slate-950">My request history</h2>
            </div>
        </div>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse($requests as $request)
                <div class="py-3">
                    <p class="font-medium text-slate-800">{{ ucfirst(str_replace('_', ' ', $request->request_type)) }} - {{ $request->event?->name ?? 'Removed event' }}</p>
                    @if($request->sourceEvent)
                        <p class="mt-1 text-sm text-slate-500">From: {{ $request->sourceEvent->name }}</p>
                    @endif
                    <p class="mt-1 text-sm text-slate-600">{{ $request->reason }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ ucfirst($request->status) }}</p>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-slate-500">No requests submitted.</p>
            @endforelse
        </div>
    </section>
@endsection
