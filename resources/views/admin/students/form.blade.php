@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="student_number" class="text-sm font-medium text-slate-700">Student number</label>
        <input id="student_number" name="student_number" value="{{ old('student_number', $student->student_number) }}" required class="mt-2 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('student_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="status" class="text-sm font-medium text-slate-700">Status</label>
        <select id="status" name="status" class="mt-2 block w-full rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <option value="active" @selected(old('status', $student->status ?: 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $student->status) === 'inactive')>Inactive</option>
        </select>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="school_year" class="text-sm font-medium text-slate-700">School year</label>
        @php($currentAcademicStart = now()->month >= 6 ? now()->year : now()->year - 1)
        <select id="school_year" name="school_year" required class="mt-2 block w-full rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <option value="">Select school year</option>
            @foreach (range($currentAcademicStart - 1, $currentAcademicStart + 2) as $year)
                @php($schoolYear = $year.'-'.($year + 1))
                <option value="{{ $schoolYear }}" @selected(old('school_year', $student->school_year) === $schoolYear)>{{ $schoolYear }}</option>
            @endforeach
        </select>
        @error('school_year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <div class="flex items-center justify-between"><label for="course_id" class="text-sm font-medium text-slate-700">Course</label><a href="{{ route('admin.courses.index') }}" class="text-xs font-semibold text-blue-700">Add/manage courses</a></div>
        <select id="course_id" name="course_id" class="mt-2 block w-full rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600"><option value="">Select course</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string) old('course_id', $student->course_id) === (string) $course->id)>{{ $course->code }} — {{ $course->name }}</option>@endforeach</select>
        @error('course_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="first_name" class="text-sm font-medium text-slate-700">First name</label>
        <input id="first_name" name="first_name" value="{{ old('first_name', $student->first_name) }}" required class="mt-2 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="middle_name" class="text-sm font-medium text-slate-700">Middle name <span class="font-normal text-slate-400">(optional)</span></label>
        <input id="middle_name" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}" class="mt-2 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('middle_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="last_name" class="text-sm font-medium text-slate-700">Last name</label>
        <input id="last_name" name="last_name" value="{{ old('last_name', $student->last_name) }}" required class="mt-2 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="gender" class="text-sm font-medium text-slate-700">Gender</label>
        <select id="gender" name="gender" required class="mt-2 block w-full rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <option value="">Select gender</option>
            @foreach (['Male', 'Female', 'LGBTQ+'] as $gender)
                <option value="{{ $gender }}" @selected(old('gender', $student->gender) === $gender)>{{ $gender }}</option>
            @endforeach
        </select>
        @error('gender')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="year_level" class="text-sm font-medium text-slate-700">Year level</label>
        <select id="year_level" name="year_level" required class="mt-2 block w-full rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <option value="">Select year level</option>
            @foreach (['1st', '2nd', '3rd', '4th'] as $yearLevel)
                <option value="{{ $yearLevel }}" @selected(old('year_level', $student->year_level) === $yearLevel)>{{ $yearLevel }} year</option>
            @endforeach
        </select>
        @error('year_level')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="section" class="text-sm font-medium text-slate-700">Section</label>
        <select id="section" name="section" required class="mt-2 block w-full rounded-lg border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            <option value="">Select section</option>
            @foreach (['A', 'B', 'C'] as $section)
                <option value="{{ $section }}" @selected(old('section', $student->section) === $section)>{{ $section }}</option>
            @endforeach
        </select>
        @error('section')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-7 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-6">
    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">{{ $submitLabel }}</button>
    <a href="{{ route('admin.students.index') }}" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancel</a>
</div>
