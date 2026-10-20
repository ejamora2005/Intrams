@extends($layout, [
    'title' => 'Possible DQ Watchlist',
    'subtitle' => 'Review registration issues before competition and scoring.',
    'workspaceLabel' => $workspaceLabel,
    'homeRoute' => $homeRoute,
])

@section('content')
    <div class="ops-hero p-5 sm:p-7 lg:p-8">
        <p class="ops-eyebrow">Eligibility control</p>
        <div class="mt-2 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div class="min-w-0">
                <h1 class="ops-display">Possible DQ Watchlist</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    <span>{{ $edition?->name ?? 'No active intramurals edition is available yet.' }}</span>
                    @if ($managedTeam ?? null)
                        <span class="mx-1">/</span>
                        <x-team-badge :team="$managedTeam" size="xs" class="font-semibold text-slate-700" />
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route($rulesRoute) }}" class="inline-flex w-fit items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">Rules & Guidelines</a>
                <a href="{{ route($homeRoute) }}" class="inline-flex w-fit items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Back to dashboard</a>
            </div>
        </div>
    </div>

    <section class="mt-6 overflow-hidden rounded-lg border border-red-200 bg-white shadow-sm" aria-labelledby="possible-dq-heading" data-list-search-container>
        <div class="flex flex-col justify-between gap-3 border-b border-red-100 bg-red-50 px-4 py-4 sm:flex-row sm:items-end sm:px-6">
            <div>
                <h2 id="possible-dq-heading" class="text-base font-semibold text-red-950">Students requiring review</h2>
                <p class="mt-1 text-sm text-red-900/75">These registrations were saved with participation-rule issues for review. A Possible DQ flag does not automatically disqualify a student.</p>
            </div>
            <div class="flex flex-col gap-2 sm:items-end">
                <label class="relative block min-w-0 sm:w-72">
                    <span class="sr-only">Search Possible DQ students</span>
                    <input type="search" data-list-search-input placeholder="Search student, ID, or sport" class="block w-full rounded-lg border-red-200 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-red-500 focus:ring-red-500">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 4.5 4.5"/></svg>
                </label>
                <div class="flex items-center gap-2">
                    <span data-list-search-count class="text-xs font-medium text-red-900/70" aria-live="polite"></span>
                    <span class="inline-flex w-max whitespace-nowrap rounded-full bg-red-600 px-3 py-1 text-xs font-semibold text-white">{{ $flaggedStudents->count() }} flagged</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Registered sports/events</th>
                        <th class="px-4 py-3">Issue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($flaggedStudents as $evaluation)
                        <tr data-list-search-row data-search-text="{{ Str::lower($evaluation['student']->full_name.' '.$evaluation['student']->student_number.' '.($evaluation['student']->course?->code ?? '').' '.collect($evaluation['entries'])->pluck('name')->implode(' ').' '.implode(' ', $evaluation['issues'])) }}" class="border-l-4 border-red-400 bg-red-950/45">
                            <td class="px-4 py-4 align-top">
                                <p class="font-semibold text-slate-950">{{ $evaluation['student']->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $evaluation['student']->student_number }} / {{ $evaluation['student']->course?->code ?? $evaluation['student']->course?->name ?? 'No course' }}</p>
                            </td>
                            <td class="px-4 py-4 align-top text-slate-600">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($evaluation['entries'] as $entry)
                                        <span class="inline-flex max-w-full whitespace-nowrap rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 shadow-sm">{{ $entry['name'] }} - {{ $entry['slot_label'] }}</span>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs text-slate-500">{{ $evaluation['summary'] }}</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex w-max whitespace-nowrap rounded-full bg-red-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">Possible DQ</span>
                                <p class="mt-2 text-sm font-medium text-red-200">{{ implode(' ', $evaluation['issues']) }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-12 text-center text-sm text-slate-500">No Possible DQ students found for the active edition.</td>
                        </tr>
                    @endforelse
                    <tr data-list-search-empty hidden>
                        <td colspan="3" class="px-4 py-12 text-center text-sm text-slate-500">No Possible DQ students match your search.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection
