@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="edition_id" class="block text-sm font-medium text-slate-700">Intramurals edition</label>
        <select id="edition_id" name="edition_id" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" @disabled(isset($team) && $team->members->isNotEmpty())>
            <option value="">Select an edition</option>
            @foreach ($editions as $edition)
                <option value="{{ $edition->id }}" @selected((string) old('edition_id', $team->edition_id) === (string) $edition->id)>{{ $edition->name }} ({{ $edition->school_year }})</option>
            @endforeach
        </select>
        @if (isset($team) && $team->members->isNotEmpty())
            <input type="hidden" name="edition_id" value="{{ $team->edition_id }}">
            <p class="mt-1 text-xs text-slate-500">The edition is locked while this team has roster members.</p>
        @endif
        @error('edition_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-slate-700">Team name</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $team->name) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" placeholder="Blue Sharks">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="code" class="block text-sm font-medium text-slate-700">Team code</label>
        <input id="code" name="code" type="text" required value="{{ old('code', $team->code) }}" class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-sm uppercase shadow-sm focus:border-blue-600 focus:ring-blue-600" placeholder="BLUE">
        <p class="mt-1 text-xs text-slate-500">Unique within the selected edition.</p>
        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2"><label for="course_id" class="block text-sm font-medium text-slate-700">Course <span class="font-normal text-slate-500">(optional)</span></label><select id="course_id" name="course_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">No course</option>@foreach(\App\Models\Course::where('status','active')->orderBy('name')->get() as $course)<option value="{{ $course->id }}" @selected(old('course_id',$team->course_id)===$course->id)>{{ $course->name }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">On creation, all active students in this course are added to the team roster.</p></div>
    <div>
        <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
        <select id="status" name="status" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'disqualified' => 'Disqualified'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $team->status ?: 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Only active teams can accept athlete assignments.</p>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-7 flex items-center gap-4 border-t border-slate-200 pt-6">
    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">{{ $submitLabel }}</button>
    <a href="{{ route('admin.teams.index') }}" class="text-sm font-semibold text-slate-600 hover:text-blue-800">Cancel</a>
</div>
