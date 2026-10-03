@extends('layouts.admin', [
    'title' => $title ?? 'Sports',
    'subtitle' => $subtitle ?? 'Manage the sport catalogue, mechanics, and participants.',
])

@section('content')
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <form class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div>
                <label for="edition_id" class="sr-only">Select edition</label>
                <select id="edition_id" name="edition_id" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-auto">
                    @forelse ($editions as $editionOption)
                        <option value="{{ $editionOption->id }}" @selected($editionId === $editionOption->id)>{{ $editionOption->name }} ({{ ucfirst($editionOption->status) }})</option>
                    @empty
                        <option value="">No edition available</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label for="status" class="sr-only">Filter sports by status</label>
                <select id="status" name="status" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:w-auto">
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                    <option value="archived" @selected($status === 'archived')>Archived</option>
                </select>
            </div>
        </form>
        @if ($showAddButton ?? true)
            <a href="{{ route('admin.sports.create', ['edition_id' => $editionId]) }}" class="inline-flex w-fit rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                Add sport
            </a>
        @else
            <a href="{{ route('admin.sports-points.index') }}" class="inline-flex w-fit rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                Manage points
            </a>
        @endif
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="{{ $listLabel ?? 'Sports list' }}">
        @forelse ($sports as $sport)
            @php($cardUrl = ($cardTarget ?? 'bracket') === 'edit'
                ? route('admin.sports.edit', ['sport' => $sport, 'edition_id' => $editionId])
                : route('admin.sports.bracket', ['sport' => $sport, 'edition_id' => $editionId]))
            <article role="link" tabindex="0" onclick="window.location='{{ $cardUrl }}'" onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ $cardUrl }}'; }" class="flex min-h-52 cursor-pointer flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $sport->name }}</h2>
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ ucfirst($sport->status) }}</span>
                </div>

                <div class="mt-5 border-y border-slate-100 py-4">
                    <p class="text-sm font-medium text-slate-500">Current edition configuration</p>
                    <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ number_format($sport->edition_sports_count) }}</p>
                </div>

                <div class="mt-auto flex flex-wrap gap-3 pt-5">
                    @if (($cardTarget ?? 'bracket') === 'edit')
                        <a href="{{ route('admin.sports-points.index') }}" onclick="event.stopPropagation()" class="rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Point system</a>
                    @else
                        <a href="{{ route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Manage participants</a>
                    @endif
                    @if (str_contains(strtolower($sport->name), 'basketball'))
                        <a href="{{ route('admin.sports.basketball-score-sheet', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50">Score sheet</a>
                    @elseif (str_contains(strtolower($sport->name), 'volleyball'))
                        <a href="{{ route('admin.sports.volleyball-score-sheet', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50">Score sheet</a>
                    @endif
                    <a href="{{ route('admin.sports.edit', ['sport' => $sport, 'edition_id' => $editionId]) }}" onclick="event.stopPropagation()" class="rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-50">Edit</a>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">
                {{ $emptyMessage ?? 'No sports found.' }}
            </div>
        @endforelse
    </section>

    <div class="mt-6">
        {{ $sports->links() }}
    </div>
@endsection
