@extends('layouts.admin', [
    'title' => $sport->name.' participants',
    'subtitle' => $edition->name.' - manage registered '.strtolower($editionSport->participant_type).' participants.',
])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <section class="overflow-visible rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="participants-heading">
        <header class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
            <div>
                <h2 id="participants-heading" class="text-lg font-semibold text-slate-900">Manage participants</h2>
                <p class="mt-1 text-sm text-slate-500">{{ number_format($competitors->count()) }} registered {{ Str::plural('competitor', $competitors->count()) }} for {{ $sport->name }}.</p>
            </div>
            <a href="{{ route('admin.sports.participants.assign', ['sport' => $sport, 'edition_id' => $edition->id]) }}" class="relative z-10 w-fit shrink-0 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Add participants</a>
        </header>

        @if ($teamGroups->isNotEmpty())
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-3 sm:px-6">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Faction filter</p>
                <div class="overflow-x-auto pb-1" aria-label="Faction filters">
                    <div class="flex min-w-max gap-2">
                        @foreach ($teamGroups as $group)
                            <button type="button" data-team-tab data-team-id="{{ $group->team->id }}" aria-controls="team-panel-{{ $group->team->id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-semibold transition {{ $loop->first ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-blue-300 hover:bg-blue-50' }}">
                                <x-team-badge :team="$group->team" size="xs" class="pointer-events-none" />
                                <span class="rounded-full px-1.5 py-0.5 text-xs opacity-80">{{ $group->entries->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="h-[34rem] p-5 sm:p-6">
                @foreach ($teamGroups as $group)
                    @php
                        $team = $group->team;
                        $entries = $group->entries;
                    @endphp
                    <form method="POST" action="{{ route('admin.sports.participants.bulk-remove', $sport) }}" id="team-panel-{{ $team->id }}" data-team-panel data-team-id="{{ $team->id }}" class="flex h-full flex-col{{ $loop->first ? '' : ' hidden' }}">
                        @csrf
                        <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    <x-team-badge :team="$team" size="md" />
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $entries->count() }} registered {{ Str::plural('student', $entries->count()) }}</p>
                            </div>
                            <label class="block sm:w-80" for="team-search-{{ $team->id }}">
                                <span class="sr-only">Find a student in {{ $team->name }}</span>
                                <input id="team-search-{{ $team->id }}" type="search" placeholder="Find a student" data-team-search class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                            </label>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3 border-y border-slate-200 bg-slate-50 px-4 py-3">
                            <label class="flex w-fit items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" data-team-select-all class="rounded border-slate-300 text-blue-700 focus:ring-blue-600"> Select visible students</label>
                            <button type="submit" @disabled($entries->isEmpty()) class="rounded-lg border border-red-200 bg-white px-3.5 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60" onclick="return confirm('Remove the selected participant registrations?');">Remove selected</button>
                        </div>

                        <div class="mt-4 min-h-0 flex-1 overflow-y-auto rounded-lg border border-slate-200 bg-white/5">
                            <table class="w-full table-fixed divide-y divide-slate-200 text-left text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="w-12 px-4 py-3"><span class="sr-only">Select</span></th><th class="px-4 py-3">Student</th><th class="hidden w-44 px-4 py-3 sm:table-cell">Student number</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($entries as $entry)
                                        @php
                                            $eligibility = $eligibilityByStudentId->get($entry->student_id);
                                            $medicalStatus = $entry->effective_medical_certificate_status ?? 'not_required';
                                            $medicalBadge = match ($medicalStatus) {
                                                'pending' => ['label' => 'Med cert pending', 'class' => 'border-amber-200 bg-amber-50 text-amber-800'],
                                                'verified' => ['label' => 'Med cert verified', 'class' => 'border-green-200 bg-green-50 text-green-800'],
                                                'rejected' => ['label' => 'Med cert rejected', 'class' => 'border-red-200 bg-red-50 text-red-800'],
                                                default => ['label' => 'No med cert required', 'class' => 'border-slate-200 bg-slate-50 text-slate-600'],
                                            };
                                        @endphp
                                        <tr data-team-row data-search="{{ Str::lower($entry->student->full_name.' '.$entry->student->student_number) }}" @class(['bg-red-950/50' => $eligibility['possible_dq'] ?? false])>
                                            <td class="px-4 py-3 align-middle"><input name="athlete_entry_ids[]" value="{{ $entry->id }}" type="checkbox" class="team-entry-checkbox rounded border-slate-300 text-blue-700 focus:ring-blue-600" aria-label="Select {{ $entry->student->full_name }}"></td>
                                            <td class="px-4 py-3 align-middle">
                                                <span class="flex flex-wrap items-center gap-2 font-medium text-slate-900">
                                                    {{ $entry->student->full_name }}
                                                    @if ($eligibility['possible_dq'] ?? false)
                                                        <span class="rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white">Possible DQ</span>
                                                    @endif
                                                </span>
                                                <span class="block text-xs text-slate-500">
                                                    {{ $entry->student->course?->name }}
                                                    @if ($editionSport->participant_type === 'dual')
                                                        <span class="ml-1">&middot; Dual pair</span>
                                                    @endif
                                                </span>
                                                <span class="mt-2 inline-flex max-w-full whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $medicalBadge['class'] }}">{{ $medicalBadge['label'] }}</span>
                                                @if ($eligibility['possible_dq'] ?? false)
                                                    <span class="mt-1 block text-xs font-medium text-red-200">{{ implode(' ', $eligibility['issues']) }}</span>
                                                @endif
                                            </td>
                                            <td class="hidden px-4 py-3 text-slate-500 sm:table-cell">{{ $entry->student->student_number }}</td>
                                        </tr>
                                    @endforeach
                                    <tr data-team-empty @if ($entries->isNotEmpty()) hidden @endif><td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">{{ $entries->isEmpty() ? 'No registered players for this sport in this faction yet.' : 'No matching students.' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </form>
                @endforeach
            </div>
        @else
            <form method="POST" action="{{ route('admin.sports.participants.bulk-remove', $sport) }}" class="flex h-[34rem] flex-col p-5 sm:p-6">
                @csrf
                <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                <div class="flex items-center justify-between gap-3 border-y border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-700">Registered students</p>
                    <button type="submit" class="rounded-lg border border-red-200 bg-white px-3.5 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50" onclick="return confirm('Remove the selected participant registrations?');">Remove selected</button>
                </div>
                <div class="mt-4 min-h-0 flex-1 overflow-y-auto rounded-lg border border-slate-200 bg-white/5">
                    @forelse ($participants as $groupName => $entries)
                        <div class="border-b border-slate-100 last:border-b-0">
                            <h3 class="sticky top-0 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-700">{{ $groupName }}</h3>
                            @foreach ($entries as $entry)
                                @php($eligibility = $eligibilityByStudentId->get($entry->student_id))
                                <label @class(['flex cursor-pointer justify-between gap-4 px-5 py-3 text-sm transition hover:bg-white/10', 'bg-red-950/50 hover:bg-red-950/60' => $eligibility['possible_dq'] ?? false])>
                                    <span class="flex items-center gap-3">
                                        <input name="athlete_entry_ids[]" value="{{ $entry->id }}" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                        <span>
                                            <span class="flex flex-wrap items-center gap-2 font-medium text-slate-800">
                                                {{ $entry->student->full_name }}
                                                @if ($eligibility['possible_dq'] ?? false)
                                                    <span class="rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white">Possible DQ</span>
                                                @endif
                                            </span>
                                            @if ($eligibility['possible_dq'] ?? false)
                                                <span class="mt-1 block text-xs font-medium text-red-200">{{ implode(' ', $eligibility['issues']) }}</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="text-right text-slate-500">{{ $entry->student->student_number }}</span>
                                </label>
                            @endforeach
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">No participants registered yet.</div>
                    @endforelse
                </div>
            </form>
        @endif
    </section>

    <script>
        const tabs = [...document.querySelectorAll('[data-team-tab]')];
        const panels = [...document.querySelectorAll('[data-team-panel]')];

        tabs.forEach((tab) => tab.addEventListener('click', () => {
            const teamId = tab.dataset.teamId;
            tabs.forEach((item) => {
                const active = item === tab;
                item.setAttribute('aria-selected', active ? 'true' : 'false');
                item.classList.toggle('border-blue-700', active);
                item.classList.toggle('bg-blue-700', active);
                item.classList.toggle('text-white', active);
                item.classList.toggle('border-slate-300', !active);
                item.classList.toggle('bg-white', !active);
                item.classList.toggle('text-slate-700', !active);
            });
            panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.teamId !== teamId));
        }));

        panels.forEach((panel) => {
            const search = panel.querySelector('[data-team-search]');
            const selectAll = panel.querySelector('[data-team-select-all]');
            const rows = [...panel.querySelectorAll('[data-team-row]')];
            const empty = panel.querySelector('[data-team-empty]');

            search.addEventListener('input', () => {
                const query = search.value.trim().toLowerCase();
                const visibleCount = rows.reduce((count, row) => {
                    const visible = row.dataset.search.includes(query);
                    row.hidden = !visible;
                    return count + (visible ? 1 : 0);
                }, 0);
                empty.hidden = visibleCount !== 0;
                selectAll.checked = false;
            });

            selectAll.addEventListener('change', () => {
                rows.filter((row) => !row.hidden).forEach((row) => {
                    row.querySelector('.team-entry-checkbox').checked = selectAll.checked;
                });
            });
        });
    </script>
@endsection
