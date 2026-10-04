@extends('layouts.admin', [
    'title' => 'Add '.$sport->name.' participants',
    'subtitle' => ucfirst($editionSport->participant_type).' sport - '.ucwords(str_replace('_', ' ', $editionSport->game_mechanic)),
])

@section('content')
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="participant-filters-heading">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <h2 id="participant-filters-heading" class="font-semibold text-slate-900">Filter eligible students</h2>
                <p class="mt-1 text-sm text-slate-500">This sport is configured as <span class="font-semibold text-slate-700">{{ ucfirst($editionSport->participant_type) }}</span>. The registration rules are applied automatically.</p>
            </div>
            <a href="{{ route('admin.sports.participants', ['sport' => $sport, 'edition_id' => $edition->id]) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Back to participants</a>
        </div>

        <form method="GET" class="mt-5 grid gap-4 sm:grid-cols-2">
            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
            <div>
                <label for="course_id" class="text-sm font-medium text-slate-700">Course</label>
                <select id="course_id" name="course_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="">All courses</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((int) $courseId === $course->id)>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="team_id" class="text-sm font-medium text-slate-700">Team</label>
                <select id="team_id" name="team_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="">{{ $requiresTeam ? 'Select team' : 'All teams' }}</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected((int) $teamId === $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 transition hover:border-blue-300 hover:bg-blue-50">Apply filters</button>
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="eligible-students-heading">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
            <div>
                <h2 id="eligible-students-heading" class="font-semibold text-slate-900">Add participants</h2>
                <p class="mt-1 text-sm text-slate-500">Already registered students are excluded from this list.</p>
            </div>
            @if ($editionSport->participant_type === 'dual')
                <span class="w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">Create one two-student pair</span>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.sports.participants.store', $sport) }}">
            @csrf
            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
            @if ($requiresTeam)
                <input type="hidden" name="team_id" value="{{ $teamId }}">
            @endif

            @if ($requiresTeam && ! $teamId)
                <div class="px-5 py-12 text-center text-sm text-slate-500">Select a team and apply the filters to show its eligible roster students.</div>
            @elseif ($students->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-slate-500">No eligible active students match the selected filters.</div>
            @elseif ($editionSport->participant_type === 'dual')
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <p id="pair-selection-summary" class="text-sm font-medium text-slate-600">Select one student from each column.</p>
                        <button id="add-selected-pair" type="submit" disabled class="w-fit rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-300">Add selected pair</button>
                    </div>
                </div>
                <div class="grid divide-y divide-slate-200 md:grid-cols-2 md:divide-x md:divide-y-0">
                    @foreach ([0 => 'First pair member', 1 => 'Second pair member'] as $side => $heading)
                        <section data-pair-column data-side="{{ $side }}" aria-labelledby="pair-member-{{ $side }}-heading">
                            <div class="sticky top-0 z-10 border-b border-slate-200 bg-white p-4">
                                <h3 id="pair-member-{{ $side }}-heading" class="text-sm font-semibold text-slate-900">{{ $heading }}</h3>
                                <label for="pair-search-{{ $side }}" class="sr-only">Search {{ strtolower($heading) }}</label>
                                <input id="pair-search-{{ $side }}" data-pair-search type="search" placeholder="Search name or student number" class="mt-3 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                            </div>
                            <div class="max-h-[30rem] divide-y divide-slate-100 overflow-y-auto" data-pair-list>
                                @foreach ($students as $student)
                                    @php($eligibility = $eligibilityPreviewByStudentId->get($student->id))
                                    @php($blocked = $eligibility['possible_dq'] ?? false)
                                    <label data-pair-row data-search="{{ Str::lower($student->full_name.' '.$student->student_number.' '.$student->course?->name) }}" @class(['flex cursor-pointer items-center gap-3 px-4 py-3 text-sm transition hover:bg-blue-50 has-[:checked]:bg-blue-50', 'border-l-4 border-red-500 bg-red-50 hover:bg-red-50' => $blocked])>
                                        <input name="student_ids[{{ $side }}]" value="{{ $student->id }}" type="radio" data-pair-radio data-student-name="{{ $student->full_name }}" @disabled($blocked) class="rounded-full border-slate-300 text-blue-700 focus:ring-blue-600 disabled:cursor-not-allowed disabled:bg-slate-200">
                                        <span class="min-w-0">
                                            <span class="flex flex-wrap items-center gap-2 font-medium text-slate-900">
                                                <span class="truncate">{{ $student->full_name }}</span>
                                                @if ($blocked)
                                                    <span class="rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white">Possible DQ</span>
                                                @endif
                                            </span>
                                            <span class="block truncate text-xs text-slate-500">{{ $student->course?->name }} - {{ $student->student_number }}</span>
                                            @if ($blocked)
                                                <span class="mt-1 block text-xs font-medium text-red-700">{{ implode(' ', $eligibility['issues']) }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                                <p data-pair-empty hidden class="px-4 py-10 text-center text-sm text-slate-500">No matching students.</p>
                            </div>
                        </section>
                    @endforeach
                </div>
            @else
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                    <label class="flex w-fit items-center gap-2 text-sm font-semibold text-slate-700">
                        <input id="select-all-students" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                        Select all filtered students
                    </label>
                </div>
                <div class="max-h-[32rem] divide-y divide-slate-100 overflow-y-auto">
                    @foreach ($students as $student)
                        @php($eligibility = $eligibilityPreviewByStudentId->get($student->id))
                        @php($blocked = $eligibility['possible_dq'] ?? false)
                        <label @class(['flex cursor-pointer items-center gap-3 px-5 py-3 text-sm transition hover:bg-slate-50', 'border-l-4 border-red-500 bg-red-50 hover:bg-red-50' => $blocked])>
                            <input name="student_ids[]" value="{{ $student->id }}" type="checkbox" @disabled($blocked) class="student-checkbox rounded border-slate-300 text-blue-700 focus:ring-blue-600 disabled:cursor-not-allowed disabled:bg-slate-200">
                            <span>
                                <span class="flex flex-wrap items-center gap-2 font-medium text-slate-900">
                                    {{ $student->full_name }}
                                    @if ($blocked)
                                        <span class="rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white">Possible DQ</span>
                                    @endif
                                </span>
                                <span class="text-xs text-slate-500">{{ $student->course?->name }} - {{ $student->student_number }}</span>
                                @if ($blocked)
                                    <span class="mt-1 block text-xs font-medium text-red-700">{{ implode(' ', $eligibility['issues']) }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="flex flex-col justify-between gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
                    <div>{{ $students->links() }}</div>
                    <button type="submit" class="w-fit rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Add selected participants</button>
                </div>
            @endif
        </form>
    </section>

    <script>
        const selectAllStudents = document.getElementById('select-all-students');
        if (selectAllStudents) {
            selectAllStudents.addEventListener('change', function () {
                document.querySelectorAll('.student-checkbox:not(:disabled)').forEach((checkbox) => checkbox.checked = this.checked);
            });
        }

        const pairColumns = [...document.querySelectorAll('[data-pair-column]')];
        const pairSubmit = document.getElementById('add-selected-pair');
        const pairSummary = document.getElementById('pair-selection-summary');

        const selectedPairMember = (side) => document.querySelector(`[data-pair-column][data-side="${side}"] [data-pair-radio]:checked`);
        const updatePairSelection = () => {
            const first = selectedPairMember(0);
            const second = selectedPairMember(1);

            document.querySelectorAll('[data-pair-radio]').forEach((radio) => {
                const other = radio.closest('[data-pair-column]').dataset.side === '0' ? second : first;
                radio.disabled = Boolean(other && other.value === radio.value && ! radio.checked);
                radio.closest('[data-pair-row]').classList.toggle('opacity-40', radio.disabled);
            });

            pairSubmit.disabled = ! first || ! second || first.value === second.value;
            pairSummary.textContent = first && second
                ? `${first.dataset.studentName} / ${second.dataset.studentName}`
                : 'Select one student from each column.';
        };

        pairColumns.forEach((column) => {
            const search = column.querySelector('[data-pair-search]');
            const rows = [...column.querySelectorAll('[data-pair-row]')];
            const empty = column.querySelector('[data-pair-empty]');

            search.addEventListener('input', () => {
                const term = search.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach((row) => {
                    const matches = row.dataset.search.includes(term);
                    row.hidden = ! matches;
                    if (matches) visible++;
                });
                empty.hidden = visible !== 0;
            });
        });

        document.querySelectorAll('[data-pair-radio]').forEach((radio) => radio.addEventListener('change', updatePairSelection));
        if (pairSubmit) updatePairSelection();
    </script>
@endsection
