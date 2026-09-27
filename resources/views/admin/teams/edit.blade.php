@extends('layouts.admin', ['title' => 'Manage team', 'subtitle' => 'Update the team and maintain its athlete roster for this edition.'])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,0.8fr)]">
        <form method="POST" action="{{ route('admin.teams.update', $team) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @method('PUT')
            @include('admin.teams.form', ['submitLabel' => 'Save changes'])
        </form>

        <section class="h-fit rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="roster-heading">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 id="roster-heading" class="font-semibold text-slate-900">Add athletes to roster</h2>
                <p class="mt-1 text-sm text-slate-500">This assigns athletes to {{ $team->name }} for {{ $team->edition->name }}, not to a specific sport.</p>
            </div>
            <div class="p-6">
                @if ($team->status !== 'active')
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Set this team to Active before assigning athletes.</p>
                @elseif ($availableStudents->isEmpty())
                    <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">No unassigned active students are available for this edition.</p>
                @else
                    <form method="GET" class="mb-4">
                        <input type="hidden" name="roster_course_id" value="{{ $rosterCourseId }}">
                        <input type="hidden" name="roster_search" value="{{ $rosterSearch }}">
                        <label for="course_id" class="sr-only">Filter students to add by course</label>
                        <select id="course_id" name="course_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
                            <option value="">All courses</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected((int) $courseId === $course->id)>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    <form method="POST" action="{{ route('admin.teams.members.store', $team) }}">
                        @csrf
                        <label class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700"><input id="select-all-students" type="checkbox" class="rounded border-slate-300 text-blue-700"> Select all filtered students</label>
                        <div class="max-h-64 divide-y overflow-y-auto rounded-lg border border-slate-200">
                            @foreach ($availableStudents as $student)
                                <label class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-slate-50"><input name="student_ids[]" value="{{ $student->id }}" type="checkbox" class="student-checkbox rounded border-slate-300 text-blue-700"><span>{{ $student->full_name }}</span><span class="ml-auto font-mono text-xs text-slate-500">{{ $student->student_number }}</span></label>
                            @endforeach
                        </div>
                        <button type="submit" class="mt-3 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Assign selected athletes</button>
                    </form>
                    @error('student_ids')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @endif
            </div>
        </section>
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="current-roster-heading">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center">
            <div>
                <h2 id="current-roster-heading" class="font-semibold text-slate-900">Current roster</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $team->members_count }} assigned athlete{{ $team->members_count === 1 ? '' : 's' }} in total.</p>
            </div>
        </div>
        <div class="p-6">
            <form method="GET" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_14rem_auto]">
                <input type="hidden" name="course_id" value="{{ $courseId }}">
                <label for="roster_search" class="sr-only">Search roster members</label>
                <input id="roster_search" type="search" name="roster_search" value="{{ $rosterSearch }}" placeholder="Search name or student number" class="rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                <label for="roster_course_id" class="sr-only">Filter roster by course</label>
                <select id="roster_course_id" name="roster_course_id" class="rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="">All courses</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((int) $rosterCourseId === $course->id)>{{ $course->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 hover:border-blue-300 hover:bg-blue-50">Filter</button>
            </form>

            @if ($rosterMembers->isEmpty())
                <p class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">No roster members match these filters.</p>
            @else
                <form method="POST" action="{{ route('admin.teams.members.bulk-remove', $team) }}" class="mt-5">
                    @csrf
                    <label class="mb-3 flex w-fit items-center gap-2 text-sm font-semibold text-slate-700"><input id="select-all-roster-members" type="checkbox" class="rounded border-slate-300 text-blue-700"> Select all members on this page</label>
                    <div class="divide-y divide-slate-100 rounded-lg border border-slate-200">
                        @foreach ($rosterMembers as $member)
                            <label class="flex cursor-pointer items-center gap-3 px-4 py-3 text-sm hover:bg-slate-50">
                                <input name="member_ids[]" value="{{ $member->id }}" type="checkbox" class="roster-member-checkbox rounded border-slate-300 text-blue-700">
                                <span><span class="block font-medium text-slate-900">{{ $member->student->full_name }}</span><span class="text-xs text-slate-500">{{ $member->student->course?->name }} - {{ $member->student->student_number }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="mt-4 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50" onclick="return confirm('Remove the selected athletes from this team?');">Remove selected members</button>
                </form>
                <div class="mt-4">{{ $rosterMembers->links() }}</div>
                @error('member_ids')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            @endif
        </div>
    </section>

    <script>
        const bindSelectAll = (sourceId, selector) => {
            const source = document.getElementById(sourceId);
            if (source) source.addEventListener('change', function () { document.querySelectorAll(selector).forEach((checkbox) => checkbox.checked = this.checked); });
        };
        bindSelectAll('select-all-students', '.student-checkbox');
        bindSelectAll('select-all-roster-members', '.roster-member-checkbox');
    </script>
@endsection
