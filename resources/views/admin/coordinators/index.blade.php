@extends('layouts.admin', ['title' => 'Coordinators', 'subtitle' => 'Manage coordinator accounts, event responsibilities, and trusted devices.'])

@section('content')
    @if (session('success'))<div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <form method="GET" class="flex w-full flex-col gap-3 sm:max-w-xl sm:flex-row">
            <input type="search" name="search" value="{{ $search }}" placeholder="Search coordinators" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <select name="status" class="rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()">
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                <option value="suspended" @selected($status === 'suspended')>Suspended</option>
                <option value="all" @selected($status === 'all')>All</option>
            </select>
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Search</button>
        </form>
        <a href="{{ route('admin.coordinators.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Add coordinator</a>
    </div>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @if ($coordinators->isEmpty())
            <div class="px-5 py-14 text-center"><p class="font-medium text-slate-800">No coordinators found</p><p class="mt-1 text-sm text-slate-500">Create a coordinator account to assign it to events.</p></div>
        @else
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Coordinator</th><th class="px-5 py-3">Email</th><th class="px-5 py-3">Assignments</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach ($coordinators as $coordinator)
                        <tr class="hover:bg-slate-50"><td class="px-5 py-4 font-medium text-slate-900">{{ $coordinator->name }}</td><td class="px-5 py-4">{{ $coordinator->email }}</td><td class="px-5 py-4">{{ $coordinator->active_assignment_count }}</td><td class="px-5 py-4"><span @class(['font-medium', 'text-green-700' => $coordinator->status === 'active', 'text-amber-700' => $coordinator->status === 'inactive', 'text-red-700' => $coordinator->status === 'suspended'])>{{ ucfirst($coordinator->status) }}</span></td><td class="px-5 py-4 text-right"><a href="{{ route('admin.coordinators.edit', $coordinator) }}" class="font-medium text-blue-700 hover:text-blue-900">Manage</a></td></tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $coordinators->links() }}</div>
        @endif
    </section>
@endsection
