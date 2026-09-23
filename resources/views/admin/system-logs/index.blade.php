@extends('layouts.admin', ['title' => 'System logs', 'subtitle' => 'Review append-only activity from administrator and coordinator operations.'])

@section('content')
    <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-3 xl:grid-cols-6">
        <select name="actor_id" class="rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"><option value="">All actors</option>@foreach ($actors as $actor)<option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>{{ $actor->name }} ({{ $actor->email }})</option>@endforeach</select>
        <select name="role" class="rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"><option value="">All roles</option><option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option><option value="coordinator" @selected(($filters['role'] ?? '') === 'coordinator')>Coordinator</option></select>
        <input type="search" name="action" value="{{ $filters['action'] ?? '' }}" placeholder="Action, e.g. team.created" class="rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
        <select name="outcome" class="rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"><option value="">All outcomes</option><option value="success" @selected(($filters['outcome'] ?? '') === 'success')>Success</option><option value="denied" @selected(($filters['outcome'] ?? '') === 'denied')>Denied</option><option value="failed" @selected(($filters['outcome'] ?? '') === 'failed')>Failed</option></select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="From date" class="rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
        <div class="flex gap-2"><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="To date" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"><button class="rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">Filter</button></div>
    </form>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @if ($logs->isEmpty())
            <div class="px-5 py-14 text-center"><p class="font-medium text-slate-800">No activity records found</p><p class="mt-1 text-sm text-slate-500">Try changing the filters or perform an audited administrator action.</p></div>
        @else
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">When</th><th class="px-5 py-3">Actor</th><th class="px-5 py-3">Action</th><th class="px-5 py-3">Subject</th><th class="px-5 py-3">Outcome</th></tr></thead><tbody class="divide-y divide-slate-100 text-slate-700">@foreach ($logs as $log)<tr><td class="whitespace-nowrap px-5 py-4 text-xs">{{ $log->created_at->format('M j, Y g:i A') }}</td><td class="px-5 py-4"><p class="font-medium text-slate-900">{{ $log->user?->name ?? 'System' }}</p><p class="text-xs text-slate-500">{{ $log->actor_role ?? '—' }}</p></td><td class="px-5 py-4 font-mono text-xs">{{ $log->action }}</td><td class="px-5 py-4 text-xs">{{ class_basename($log->auditable_type) }}@if ($log->auditable_id) #{{ $log->auditable_id }}@endif</td><td class="px-5 py-4"><span @class(['font-medium', 'text-green-700' => $log->outcome === 'success', 'text-red-700' => in_array($log->outcome, ['denied', 'failed'], true)])>{{ ucfirst($log->outcome) }}</span></td></tr>@endforeach</tbody></table></div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $logs->links() }}</div>
        @endif
    </section>
@endsection
