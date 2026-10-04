@extends($layout, [
    'title' => 'Rules & Guidelines',
    'subtitle' => 'Participation combinations, possible DQ flags, and medical certificate reminders.',
    'workspaceLabel' => $workspaceLabel,
    'homeRoute' => $homeRoute,
])

@section('content')
    <div class="ops-hero p-5 sm:p-7 lg:p-8">
        <p class="ops-eyebrow">Eligibility control</p>
        <div class="mt-2 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div class="min-w-0">
                <h1 class="ops-display">Rules & Guidelines</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    {{ $edition?->name ?? 'No active intramurals edition is available yet.' }}
                    @if ($managedTeam ?? null)
                        / {{ $managedTeam->name }}
                    @endif
                </p>
            </div>
            <a href="{{ route($homeRoute) }}" class="inline-flex w-fit items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 shadow-sm transition hover:border-blue-300 hover:bg-blue-50">Back to dashboard</a>
        </div>
    </div>

    <section class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)]">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-semibold text-slate-950">Participation rules</h2>
                <p class="mt-1 text-sm text-slate-500">Each student can be registered in a maximum of {{ $maxEvents }} events.</p>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($allowedCombinations as $combination)
                    <article class="rounded-lg border border-slate-200 p-4">
                        <p class="font-semibold text-slate-950">{{ $combination['label'] }}</p>
                        <p class="mt-2 text-sm text-slate-500">Major {{ $combination['major'] }} / Minor {{ $combination['minor'] }} / Individual-Dual {{ $combination['individual_or_dual'] }}</p>
                    </article>
                @endforeach
            </div>
            <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Students outside these combinations are marked red as Possible DQ so the team can correct the registration before scoring.</p>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-base font-semibold text-slate-950">Medical certificates</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Sports events require GAM review of medical certificates before participation, except these exempt events:</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($medicalCertificateExemptions as $code)
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $code }}</span>
                @endforeach
            </div>
            <p class="mt-4 text-sm text-slate-500">GAM can review each required certificate from the GAM dashboard.</p>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-end sm:px-6">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Possible DQ watchlist</h2>
                <p class="mt-1 text-sm text-slate-500">Students currently breaking the participation rules for the active edition.</p>
            </div>
            <span class="w-fit rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">{{ $flaggedStudents->count() }} flagged</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">Events</th><th class="px-4 py-3">Rule status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($flaggedStudents as $evaluation)
                        <tr class="bg-red-50/90">
                            <td class="px-4 py-3 align-top">
                                <p class="font-semibold text-slate-950">{{ $evaluation['student']->full_name }}</p>
                                <p class="text-xs text-slate-500">{{ $evaluation['student']->student_number }} / {{ $evaluation['student']->course?->code ?? $evaluation['student']->course?->name ?? 'No course' }}</p>
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($evaluation['entries'] as $entry)
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 shadow-sm">{{ $entry['name'] }} - {{ $entry['slot_label'] }}</span>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs text-slate-500">{{ $evaluation['summary'] }}</p>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">Possible DQ</span>
                                <p class="mt-2 text-sm font-medium text-red-800">{{ implode(' ', $evaluation['issues']) }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">No Possible DQ students found for the active edition.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
