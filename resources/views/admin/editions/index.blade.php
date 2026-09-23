@extends('layouts.admin', ['title' => 'Intramurals editions', 'subtitle' => 'Manage the program periods that own teams and events.'])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <form method="GET" class="flex w-full flex-col gap-3 sm:max-w-xl sm:flex-row">
            <label for="edition-search" class="sr-only">Search editions</label>
            <input id="edition-search" type="search" name="search" value="{{ $search }}" placeholder="Search by edition or school year" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600">
            <select name="status" class="rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()">
                <option value="all" @selected($status === 'all')>All statuses</option>
                @foreach (['draft' => 'Draft', 'active' => 'Active', 'closed' => 'Closed', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-800">Search</button>
        </form>
        <a href="{{ route('admin.editions.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Add edition</a>
    </div>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="edition-list-heading">
        <div class="border-b border-slate-200 px-5 py-4"><h2 id="edition-list-heading" class="font-semibold text-slate-900">Edition records</h2></div>
        @if ($editions->isEmpty())
            <div class="px-5 py-14 text-center"><p class="font-medium text-slate-800">No editions found</p><p class="mt-1 text-sm text-slate-500">Create the first intramurals edition to start organising teams.</p></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th scope="col" class="px-5 py-3">Edition</th><th scope="col" class="px-5 py-3">Dates</th><th scope="col" class="px-5 py-3">Teams</th><th scope="col" class="px-5 py-3">Status</th><th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($editions as $edition)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4"><p class="font-medium text-slate-900">{{ $edition->name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $edition->school_year }}</p></td>
                                <td class="px-5 py-4">{{ $edition->starts_on->format('M j, Y') }} <span class="text-slate-400">to</span> {{ $edition->ends_on->format('M j, Y') }}</td>
                                <td class="px-5 py-4">{{ $edition->teams_count }} team{{ $edition->teams_count === 1 ? '' : 's' }}</td>
                                <td class="px-5 py-4"><span @class(['font-medium', 'text-slate-600' => $edition->status === 'draft', 'text-green-700' => $edition->status === 'active', 'text-amber-700' => $edition->status === 'closed', 'text-slate-500' => $edition->status === 'archived'])>{{ ucfirst($edition->status) }}</span></td>
                                <td class="px-5 py-4 text-right"><div class="flex justify-end gap-4"><a href="{{ route('admin.editions.edit', $edition) }}" class="font-medium text-blue-700 hover:text-blue-900">Edit</a>@if ($edition->status !== 'archived')<form method="POST" action="{{ route('admin.editions.destroy', $edition) }}" onsubmit="return confirm('Archive this edition? Teams and historical records will be preserved.');">@csrf @method('DELETE') <button type="submit" class="font-medium text-slate-600 hover:text-red-700">Archive</button></form>@endif</div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $editions->links() }}</div>
        @endif
    </section>
@endsection
