@extends('layouts.admin',['title'=>'Courses','subtitle'=>'Create reusable courses for student records.'])
@section('content')
    @if (session('success'))<div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <form method="GET" action="{{ route('admin.courses.index') }}" class="flex w-full gap-3 sm:max-w-xl">
            <label for="course-search" class="sr-only">Search courses</label>
            <input id="course-search" type="search" name="search" value="{{ $search }}" placeholder="Search course name or code" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-800">Search</button>
        </form>
        @if ($search)<a href="{{ route('admin.courses.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Clear filters</a>@endif
    </div>
    <form method="POST" action="{{ route('admin.courses.store') }}" class="mt-5 flex max-w-2xl gap-3 rounded-xl border bg-white p-5">@csrf <label for="course-name" class="sr-only">Course name</label><input id="course-name" name="name" required placeholder="Course name" class="flex-1 rounded border-slate-300"><button class="rounded bg-blue-700 px-4 text-white">Add course</button></form>
    <div class="mt-6 rounded-xl border bg-white p-5"><p class="mb-3 text-sm text-slate-500">{{ number_format($courses->total()) }} course{{ $courses->total() === 1 ? '' : 's' }} found{{ $search ? ' for “'.$search.'”' : '' }}.</p><table class="min-w-full text-sm">@forelse($courses as $course)<tr><td class="py-2">{{ $course->name }}</td><td>{{ $course->code }}</td><td>{{ $course->students_count }} students</td></tr>@empty<tr><td colspan="3" class="py-8 text-center text-slate-500">No courses found.</td></tr>@endforelse</table>{{ $courses->links() }}</div>
@endsection
