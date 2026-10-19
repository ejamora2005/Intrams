@extends('layouts.admin', ['title' => 'System logs', 'subtitle' => 'Activity across Admin, GAM, Tabulator, and Coordinator workspaces.'])

@section('content')
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold uppercase text-slate-500">Matching records</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary->total ?? 0) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold uppercase text-slate-500">Successful</p><p class="mt-2 text-2xl font-bold text-emerald-300">{{ number_format($summary->successful ?? 0) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold uppercase text-slate-500">Failed or denied</p><p class="mt-2 text-2xl font-bold text-red-700">{{ number_format($summary->issues ?? 0) }}</p></div>
    </div>

    <form method="GET" action="{{ route('admin.system-logs.index') }}" class="mt-5 grid gap-4 rounded-xl border border-slate-200 bg-white p-5 md:grid-cols-2 xl:grid-cols-4">
        <label class="text-sm font-medium">Search activity
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, event, page, or IP" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="text-sm font-medium">Actor
            <select name="actor_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All actors</option>@foreach ($actors as $actor)<option value="{{ $actor->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actor->id)>{{ $actor->name }} ({{ $actor->email }})</option>@endforeach</select>
        </label>
        <label class="text-sm font-medium">Role
            <select name="role" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All roles</option>@foreach ($roles as $role => $label)<option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $label }}</option>@endforeach</select>
        </label>
        <label class="text-sm font-medium">Outcome
            <select name="outcome" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All outcomes</option>@foreach (['success', 'denied', 'failed'] as $outcome)<option value="{{ $outcome }}" @selected(($filters['outcome'] ?? '') === $outcome)>{{ ucfirst($outcome) }}</option>@endforeach</select>
        </label>
        <label class="text-sm font-medium">Action
            <input type="search" name="action" value="{{ $filters['action'] ?? '' }}" placeholder="e.g. request.gam or student.updated" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="text-sm font-medium">From (UTC+8)
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
        </label>
        <label class="text-sm font-medium">To (UTC+8)
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
        </label>
        @if (!empty($filters['request_id']))<input type="hidden" name="request_id" value="{{ $filters['request_id'] }}">@endif
        <div class="flex items-end gap-3"><button type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">Filter logs</button><a href="{{ route('admin.system-logs.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold">Clear</a></div>
    </form>
    @if ($errors->any())<div role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 p-5"><h2 class="font-semibold text-slate-900">Activity history</h2><p class="text-xs text-slate-500">Latest first · Times shown in UTC+8</p></div>
        @forelse ($logs as $log)
            @php
                $details = $log->new_values ?? [];
                $context = $details['request'] ?? null;
                $changes = $details['changes'] ?? [];
                $actor = $log->user?->name ?? ($details['actor']['name'] ?? ($log->action === 'auth.failed' ? 'Login attempt' : 'System / guest'));
            @endphp
            <article class="min-w-0 border-b border-slate-200 p-4 last:border-b-0 sm:p-5" data-activity-record>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="break-all text-sm font-semibold text-slate-900">{{ $log->action }}</p>
                        <p class="mt-1 break-words text-sm text-slate-600">{{ $actor }} <span class="ml-2 text-xs">{{ $roles[$log->actor_role] ?? $log->actor_role }}</span></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs">
                        <time datetime="{{ $log->created_at->toIso8601String() }}" class="text-slate-500">{{ $log->created_at->timezone($timezone)->format('M j, Y g:i:s A') }}</time>
                        <span @class(['rounded-full px-3 py-1 font-semibold', 'bg-green-50 text-green-700' => $log->outcome === 'success', 'bg-red-50 text-red-700' => $log->outcome !== 'success'])>{{ ucfirst($log->outcome) }}</span>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                    @if ($context)<span class="break-all">{{ $context['method'] ?? '' }} {{ $context['path'] ?? '' }} · HTTP {{ $context['status'] ?? '' }}</span>@else<span>{{ class_basename($log->auditable_type) }}{{ $log->auditable_id ? ' #'.$log->auditable_id : '' }}</span>@endif
                    @if ($log->ip_address)<span>IP {{ $log->ip_address }}</span>@endif
                    @if ($changes)<span>{{ count($changes) }} record change{{ count($changes) === 1 ? '' : 's' }}{{ ($details['change_state'] ?? '') === 'attempted' ? ' attempted' : '' }}</span>@endif
                </div>
                <details class="mt-3">
                    <summary class="w-fit cursor-pointer text-sm font-semibold text-blue-700">View details</summary>
                    @if ($log->request_id)<div class="mt-3 flex flex-wrap items-center gap-3 text-xs"><span class="break-all text-slate-500">Request {{ $log->request_id }}</span><a class="font-semibold text-blue-700" href="{{ route('admin.system-logs.index', ['request_id' => $log->request_id]) }}">Related records</a></div>@endif
                    @if ($changes)
                        <div class="mt-3 space-y-3">
                            @foreach ($changes as $change)
                                <div class="min-w-0 rounded-lg border border-slate-200 p-3">
                                    <p class="text-sm font-semibold">{{ $change['type'] ?? 'Record' }} #{{ $change['id'] ?? '' }} · {{ $change['event'] ?? '' }}</p>
                                    <pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap break-all text-xs leading-relaxed">{{ json_encode(['before' => $change['before'] ?? null, 'after' => $change['after'] ?? null], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap break-all rounded-lg border border-slate-200 p-3 text-xs leading-relaxed">{{ json_encode(['before' => $log->old_values, 'details' => \Illuminate\Support\Arr::except($details, ['changes'])], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
            </article>
        @empty
            <div class="px-5 py-14 text-center"><p class="font-medium text-slate-900">No activity records found</p><p class="mt-1 text-sm text-slate-500">Try changing the filters. New workspace activity will appear here.</p></div>
        @endforelse
        @if ($logs->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $logs->links() }}</div>@endif
    </section>
@endsection
