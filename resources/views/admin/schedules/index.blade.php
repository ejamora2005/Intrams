@extends('layouts.admin', ['title' => 'Schedules', 'subtitle' => 'Manage the events shown on the public timetable. Times use Philippine time (UTC+8).'])

@section('content')
    @include('admin.schedules._tabs', ['activeTab' => 'public'])
    <x-ops-feedback />
    @if ($errors->any() && old('schedule_action') === 'create')<span data-ops-auto-modal="add-public-schedule"></span>@endif
    @if ($errors->any() && is_string(old('entry_key')) && preg_match('/^[a-f0-9]{24}$/', old('entry_key')))
        <span data-ops-auto-modal="edit-public-schedule-{{ old('entry_key') }}"></span>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Public timetable</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-950">{{ $edition?->name ?? 'No active intramurals edition' }}</h2>
                <p class="mt-2 text-sm text-slate-500">Edit an event below. Published changes appear immediately on the public schedule.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($available)<button type="button" data-ops-modal-open="add-public-schedule" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">+ Add public schedule</button>@endif
                <a href="{{ route('public.schedule', ['date' => $date ?: now('Asia/Manila')->toDateString()]) }}" target="_blank" rel="noopener" class="inline-flex w-fit shrink-0 justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">View public schedule</a>
            </div>
        </div>
        @if ($available)
            <form method="GET" action="{{ route('admin.schedules.index') }}" class="mt-5 grid gap-3 border-t border-slate-200 pt-5 sm:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm font-medium text-slate-700">Search schedules
                    <input name="search" type="search" value="{{ $search }}" placeholder="Event name or venue" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                </label>
                <label class="text-sm font-medium text-slate-700">Date
                    <select name="date" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">All dates</option>
                        @foreach ($dates as $day)<option value="{{ $day }}" @selected($date === $day)>{{ \Carbon\Carbon::parse($day)->format('M j, Y') }}</option>@endforeach
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">Category
                    <select name="category" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">All categories</option>
                        @foreach (['sports' => 'Sports', 'cultural' => 'Cultural', 'activities' => 'Campus activities'] as $value => $label)<option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>@endforeach
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Search</button>
                    <a href="{{ route('admin.schedules.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>
            <p class="mt-3 text-sm text-slate-500">Showing {{ $visibleCount }} of {{ $total }} sessions</p>
        @endif
    </section>

    @if ($available)
        @php
            $key = 'create'; $modalId = 'add-public-schedule'; $editing = old('schedule_action') === 'create';
            $defaultDate = $date ?: now('Asia/Manila')->toDateString();
            $defaultDate = max(config('public_programme.starts_on'), min(config('public_programme.ends_on'), $defaultDate));
            $defaults = ['event' => '', 'date' => $defaultDate, 'session' => 'AM', 'category' => 'sports', 'venue' => '', 'is_published' => true];
            $value = function ($field, $default = null) use ($editing, $defaults) { $input = $editing ? old($field, $defaults[$field] ?? $default) : ($defaults[$field] ?? $default); return is_scalar($input) || $input === null ? $input : ($defaults[$field] ?? $default); };
            $gameRows = $editing && is_array(old('games')) ? array_values(array_filter(old('games'), 'is_array')) : [];
        @endphp
        @include('admin.schedules._editor', ['creating' => true])
    @endif

    @forelse ($eventsByDate as $day => $events)
        <section class="mt-6" aria-label="{{ \Carbon\Carbon::parse($day)->format('F j') }} schedules">
            <div class="mb-3 flex items-center gap-3">
                <h2 class="text-lg font-semibold text-slate-900">{{ \Carbon\Carbon::parse($day)->format('l, F j, Y') }}</h2>
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ $events->count() }} sessions</span>
            </div>
            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($events as $event)
                    @php
                        $key = $event['key'];
                        $editing = old('entry_key') === $key;
                        $value = function ($field, $default = null) use ($editing, $event) {
                            $current = $event[$field] ?? $default;
                            $input = $editing ? old($field, $current) : $current;
                            return is_scalar($input) || $input === null ? $input : $current;
                        };
                        $modalId = 'edit-public-schedule-'.$key;
                        $period = ['AM' => 'Morning', 'PM' => 'Afternoon', 'Evening' => 'Evening'][$event['session']];
                        $time = filled($event['starts_at'] ?? null) ? \Carbon\Carbon::createFromFormat('!H:i', $event['starts_at'], 'Asia/Manila')->format('g:i A') : $event['session'];
                        $categoryLabel = ['sports' => 'Sports', 'cultural' => 'Cultural', 'activities' => 'Campus activity'][$event['category']];
                        $gameRows = $editing ? old('games', old('games_present') ? [] : ($event['games'] ?? [])) : ($event['games'] ?? []);
                        $gameRows = is_array($gameRows) ? array_values(array_filter($gameRows, 'is_array')) : ($event['games'] ?? []);
                    @endphp
                    <article id="schedule-{{ $key }}" data-public-schedule-card class="flex scroll-mt-28 flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm target:ring-2 target:ring-blue-500">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-800">{{ $categoryLabel }}</span>
                            <span class="rounded-full px-2.5 py-1 {{ $event['is_published'] ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $event['is_published'] ? 'Published' : 'Hidden' }}</span>
                        </div>
                        <h3 class="mt-3 break-words text-lg font-semibold text-slate-950">{{ $event['event'] }}</h3>
                        <p class="mt-3 text-sm font-semibold text-slate-800">{{ $time }} <span class="font-normal text-slate-500">· {{ $period }}</span></p>
                        @if (filled($event['ends_at'] ?? null))<p class="mt-1 text-sm text-slate-500">Until {{ \Carbon\Carbon::createFromFormat('!H:i', $event['ends_at'], 'Asia/Manila')->format('g:i A') }}</p>@endif
                        <p class="mb-5 mt-2 break-words text-sm text-slate-600">Venue: {{ $event['venue'] ?: 'TBA' }}</p>
                        @if ($event['matchups'])<x-programme-matchups :games="$event['matchups']" :manage="true" />@endif
                        <button type="button" data-ops-modal-open="{{ $modalId }}" class="mt-auto rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-800 hover:bg-blue-100">Edit schedule</button>
                        <button type="button" data-ops-modal-open="delete-public-schedule-{{ $key }}" class="mt-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Delete schedule</button>
                    </article>

                    @include('admin.schedules._editor', ['creating' => false])
                    <dialog id="delete-public-schedule-{{ $key }}" data-ops-modal aria-labelledby="delete-public-title-{{ $key }}" class="w-[min(30rem,calc(100vw-2rem))] rounded-xl border-0 bg-white p-5 text-slate-900 shadow-xl backdrop:bg-slate-950/60">
                        <form method="POST" action="{{ route('admin.schedules.destroy', $key) }}" data-ops-form>
                            @csrf @method('DELETE')
                            <input type="hidden" name="edition_id" value="{{ $edition->id }}"><input type="hidden" name="version" value="{{ $event['version'] }}"><input type="hidden" name="confirmation" value="DELETE">
                            <h2 id="delete-public-title-{{ $key }}" class="text-lg font-semibold">Delete {{ $event['event'] }}?</h2>
                            <p class="mt-3 text-sm text-slate-600">This removes this public event. Linked bracket matches, match schedules, participants and recorded results are kept.</p>
                            <div class="mt-5 flex justify-end gap-2"><button type="button" data-ops-modal-close class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button><button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Delete schedule</button></div>
                        </form>
                    </dialog>
                @endforeach
            </div>
        </section>
    @empty
        <p class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center text-sm text-slate-500">{{ $available ? 'No schedules match these filters. Try another search or reset the filters.' : 'No public timetable is available for the active edition.' }}</p>
    @endforelse
    <datalist id="public-schedule-venues">@foreach (collect(config('intramurals.venues', []))->merge(['GYM', 'FIELD', 'Library', 'CompLab'])->unique() as $venue)<option value="{{ $venue }}">@endforeach</datalist>
    <script>
        document.querySelectorAll('[data-public-start-time]').forEach(input => {
            input.addEventListener('change', () => {
                if (!input.value) return;
                const hour = Number(input.value.slice(0, 2));
                input.closest('form').querySelector('[name="session"]').value = hour < 12 ? 'AM' : (hour < 18 ? 'PM' : 'Evening');
            });
        });
    </script>
@endsection
