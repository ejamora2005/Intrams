@extends('layouts.admin', ['title' => 'System Logs', 'subtitle' => 'Official activity register for record changes, approvals, and competition results.'])

@section('content')
    <x-ops-feedback />
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach (['Recorded actions' => $summary->total ?? 0, 'Completed' => $summary->successful ?? 0, 'Not completed' => $summary->issues ?? 0] as $label => $count)
            <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($count) }}</p></div>
        @endforeach
    </div>
    <form method="GET" action="{{ route('admin.system-logs.index') }}" class="mt-5 grid gap-4 rounded-xl border border-slate-200 bg-white p-5 md:grid-cols-2 xl:grid-cols-4">
        <label class="min-w-0 text-sm font-medium">Search activity<input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Staff member, player, event, or faction" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
        <label class="min-w-0 text-sm font-medium">Staff member<select name="actor_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All staff</option>@foreach ($actors as $actor)<option value="{{ $actor->id }}" @selected((string)($filters['actor_id'] ?? '') === (string)$actor->id)>{{ $actor->name }}</option>@endforeach</select></label>
        <label class="min-w-0 text-sm font-medium">Role<select name="role" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All roles</option>@foreach ($roles as $role => $label)<option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $label }}</option>@endforeach</select></label>
        <label class="min-w-0 text-sm font-medium">Activity<select name="activity" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All record changes</option>@foreach ($activityTypes as $value => $label)<option value="{{ $value }}" @selected(($filters['activity'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm font-medium">From (Philippine time)<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
        <label class="text-sm font-medium">To (Philippine time)<input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
        <label class="min-w-0 text-sm font-medium">Status<select name="outcome" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">All statuses</option>@foreach (['success' => 'Completed', 'failed' => 'Not completed', 'denied' => 'Not authorized'] as $value => $label)<option value="{{ $value }}" @selected(($filters['outcome'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
        @if (!empty($filters['request_id']))<input type="hidden" name="request_id" value="{{ $filters['request_id'] }}">@endif
        <div class="flex flex-wrap items-end gap-3"><button type="submit" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white">Filter records</button><a href="{{ route('admin.system-logs.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold">Clear</a></div>
    </form>
    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5"><div><h2 class="font-semibold text-slate-950">Activity register</h2><p class="mt-1 text-sm text-slate-500">Record changes and declarations · Latest first</p></div><span class="text-xs text-slate-500">Philippine time (UTC+8)</span></div>
        @forelse ($logs as $log)
            @php
                $entry = $log->presentation;
                $actor = $log->new_values['actor']['name'] ?? $log->user?->name ?? ($log->user_id ? 'Former staff member' : 'System');
                $outcome = ['success' => 'Completed', 'failed' => 'Not completed', 'denied' => 'Not authorized'][$log->outcome] ?? 'Recorded';
            @endphp
            <article data-activity-record="{{ $log->id }}" class="min-w-0 border-b border-slate-200 p-5 last:border-b-0 sm:p-6">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div class="min-w-0"><h3 class="break-words font-semibold text-slate-950">{{ $entry['title'] }}</h3><p class="mt-1 break-words text-sm font-medium text-slate-700">{{ $entry['subject'] }}</p><p class="mt-3 break-words text-sm text-slate-500">Performed by <span class="font-semibold text-slate-950">{{ $actor }}</span><span class="mx-2">·</span>{{ $roles[$log->actor_role] ?? ($log->actor_role ? 'Staff' : 'Automated process') }}</p></div>
                    <div class="flex shrink-0 flex-wrap items-center gap-3 text-xs sm:flex-col sm:items-end"><time datetime="{{ $log->created_at->toIso8601String() }}" class="text-slate-500">{{ $log->created_at->timezone($timezone)->format('M j, Y g:i:s A') }}</time><span @class(['w-fit rounded-full px-3 py-1 font-semibold', 'bg-green-50 text-green-700' => $log->outcome === 'success', 'bg-red-50 text-red-700' => $log->outcome !== 'success'])>{{ $outcome }}</span></div>
                </div>
                @if ($log->outcome !== 'success')<p class="mt-3 text-sm font-medium text-red-700">This action was not completed. Any changes below describe the attempted action.</p>@endif
                <ul class="mt-4 space-y-1 text-sm text-slate-600">@foreach ($entry['details'] as $detail)<li class="break-words">{{ $detail }}</li>@endforeach</ul>
                @if ($entry['changes'])
                    <details class="mt-4"><summary class="w-fit cursor-pointer text-sm font-semibold text-blue-700">View changes</summary><div class="mt-3 grid gap-3 lg:grid-cols-2">@foreach ($entry['changes'] as $change)<div class="min-w-0 rounded-xl border border-slate-200 p-4"><p class="break-words text-xs text-slate-500">{{ $change['record'] }}</p><p class="mt-1 font-semibold text-slate-950">{{ $change['field'] }}</p><dl class="mt-3 grid grid-cols-[3rem_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm"><dt class="text-slate-500">Before</dt><dd class="break-words text-slate-700">{{ $change['before'] }}</dd><dt class="text-slate-500">After</dt><dd class="break-words font-medium text-slate-950">{{ $change['after'] }}</dd></dl></div>@endforeach</div></details>
                @endif
            </article>
        @empty
            <div class="px-5 py-14 text-center"><p class="font-semibold text-slate-950">No recorded changes match these filters</p><p class="mt-2 text-sm text-slate-500">Try another staff member, activity, or date range.</p></div>
        @endforelse
        @if ($logs->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $logs->links() }}</div>@endif
    </section>
@endsection
