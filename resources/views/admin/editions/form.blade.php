@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="name" class="block text-sm font-medium text-slate-700">Edition name</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $edition->name) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" placeholder="2026 Intramurals">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="school_year" class="block text-sm font-medium text-slate-700">School year</label>
        <input id="school_year" name="school_year" type="text" required value="{{ old('school_year', $edition->school_year) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" placeholder="2026-2027">
        @error('school_year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="starts_on" class="block text-sm font-medium text-slate-700">Starts on</label>
        <input id="starts_on" name="starts_on" type="date" required value="{{ old('starts_on', $edition->starts_on?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('starts_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="ends_on" class="block text-sm font-medium text-slate-700">Ends on</label>
        <input id="ends_on" name="ends_on" type="date" required value="{{ old('ends_on', $edition->ends_on?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
        @error('ends_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-slate-700">Lifecycle status</label>
        <select id="status" name="status" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
            @foreach (['draft' => 'Draft', 'active' => 'Active', 'closed' => 'Closed', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $edition->status ?: 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Only one edition can be active at a time.</p>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-7 flex items-center gap-4 border-t border-slate-200 pt-6">
    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">{{ $submitLabel }}</button>
    <a href="{{ route('admin.editions.index') }}" class="text-sm font-semibold text-slate-600 hover:text-blue-800">Cancel</a>
</div>
