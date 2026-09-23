@extends('layouts.admin', ['title' => 'Students', 'subtitle' => 'Manage athlete records and preserve competition history.'])

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <form method="GET" class="flex w-full flex-col gap-3 sm:max-w-xl sm:flex-row">
            <label for="student-search" class="sr-only">Search students</label>
            <input id="student-search" type="search" name="search" value="{{ $search }}" placeholder="Search by name, student number, or section" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600">
            <select name="status" class="rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()">
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                <option value="all" @selected($status === 'all')>All current</option>
                <option value="archived" @selected($status === 'archived')>Archived</option>
            </select>
            <select name="course_id" class="rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" onchange="this.form.submit()"><option value="">All courses</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((int) $courseId === $course->id)>{{ $course->code }}</option>@endforeach</select>
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-800">Search</button>
        </form>
        <div class="flex shrink-0 flex-wrap gap-3"><a href="{{ route('admin.courses.index') }}" class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2.5 text-sm font-semibold text-blue-800 transition hover:border-blue-400">Manage courses</a><a href="{{ route('admin.students.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">Add student</a></div>
    </div>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="student-list-heading">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 id="student-list-heading" class="font-semibold text-slate-900">Student records</h2>
        </div>

        @if ($students->isEmpty())
            <div class="px-5 py-14 text-center">
                <p class="font-medium text-slate-800">No student records found</p>
                <p class="mt-1 text-sm text-slate-500">Adjust the filters or add the first student athlete.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Student</th>
                            <th scope="col" class="px-5 py-3">Student number</th>
                            <th scope="col" class="px-5 py-3">Year & section</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($students as $student)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4 font-medium text-slate-900">{{ $student->full_name }}</td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-600">{{ $student->student_number }}</td>
                                <td class="px-5 py-4">{{ collect([$student->year_level ? 'Year '.$student->year_level : null, $student->section])->filter()->implode(' · ') ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($student->trashed())
                                        <span class="text-sm font-medium text-slate-500">Archived</span>
                                    @else
                                        <span @class([
                                            'text-sm font-medium',
                                            'text-green-700' => $student->status === 'active',
                                            'text-amber-700' => $student->status === 'inactive',
                                        ])>{{ ucfirst($student->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($student->trashed())
                                        <form method="POST" action="{{ route('admin.students.restore', $student->id) }}">
                                            @csrf
                                            <button type="submit" class="font-medium text-blue-700 hover:text-blue-900">Restore</button>
                                        </form>
                                    @else
                                        <div class="flex justify-end gap-4">
                                            <a href="{{ route('admin.students.edit', $student) }}" class="font-medium text-blue-700 hover:text-blue-900">Edit</a>
                                            <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Archive this student record?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-slate-600 hover:text-red-700">Archive</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">{{ $students->links() }}</div>
        @endif
    </section>
@endsection
