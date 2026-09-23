@extends('layouts.admin', ['title' => 'Teams', 'subtitle' => 'Manage edition-based teams and athlete rosters.'])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <form method="GET" class="flex w-full flex-col gap-3 lg:max-w-3xl lg:flex-row">
            <label for="team-search" class="sr-only">Search teams</label>
            <input id="team-search" type="search" name="search" value="{{ $search }}" placeholder="Search by team name or code" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600">
            <select name="edition_id" class="rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()">
                <option value="">All editions</option>
                @foreach ($editions as $edition)
                    <option value="{{ $edition->id }}" @selected((int) $editionId === $edition->id)>{{ $edition->name }} ({{ $edition->school_year }})</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()">
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                <option value="disqualified" @selected($status === 'disqualified')>Disqualified</option>
                <option value="all" @selected($status === 'all')>All current</option>
                <option value="archived" @selected($status === 'archived')>Archived</option>
            </select>
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-800">Search</button>
        </form>
        <a href="{{ route('admin.teams.create', ['edition_id' => $editionId]) }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Add team</a>
    </div>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="team-list-heading">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 id="team-list-heading" class="font-semibold text-slate-900">Team records</h2>
        </div>
        @if ($teams->isEmpty())
            <div class="px-5 py-14 text-center">
                <p class="font-medium text-slate-800">No teams found</p>
                <p class="mt-1 text-sm text-slate-500">Adjust the filters or create the first team for an edition.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><th scope="col" class="px-5 py-3">Team</th><th scope="col" class="px-5 py-3">Edition</th><th scope="col" class="px-5 py-3">Roster</th><th scope="col" class="px-5 py-3">Status</th><th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($teams as $team)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4"><p class="font-medium text-slate-900">{{ $team->name }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">{{ $team->code }}</p></td>
                                <td class="px-5 py-4"><p>{{ $team->edition->name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $team->edition->school_year }}</p></td>
                                <td class="px-5 py-4">{{ $team->members_count }} athlete{{ $team->members_count === 1 ? '' : 's' }}</td>
                                <td class="px-5 py-4">
                                    @if ($team->trashed())
                                        <span class="font-medium text-slate-500">Archived</span>
                                    @else
                                        <span @class(['font-medium', 'text-green-700' => $team->status === 'active', 'text-amber-700' => $team->status === 'inactive', 'text-red-700' => $team->status === 'disqualified'])>{{ ucfirst($team->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($team->trashed())
                                        <form method="POST" action="{{ route('admin.teams.restore', $team->id) }}">@csrf <button type="submit" class="font-medium text-blue-700 hover:text-blue-900">Restore</button></form>
                                    @else
                                        <div class="flex justify-end gap-4"><a href="{{ route('admin.teams.edit', $team) }}" class="font-medium text-blue-700 hover:text-blue-900">Manage</a><form method="POST" action="{{ route('admin.teams.destroy', $team) }}" onsubmit="return confirm('Archive this team? Its roster history will be preserved.');">@csrf @method('DELETE') <button type="submit" class="font-medium text-slate-600 hover:text-red-700">Archive</button></form></div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $teams->links() }}</div>
        @endif
    </section>
@endsection
