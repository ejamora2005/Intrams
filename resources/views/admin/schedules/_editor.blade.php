                    <dialog id="{{ $modalId }}" aria-labelledby="{{ $modalId }}-title" data-ops-modal class="w-[min(40rem,calc(100vw-2rem))] max-h-[calc(100dvh-2rem)] overflow-y-auto rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60">
                        <form method="POST" action="{{ ($creating ?? false) ? route('admin.schedules.store') : route('admin.schedules.update', $key) }}" data-ops-form data-programme-editor>
                            @csrf
                            @if (! ($creating ?? false)) @method('PUT') @endif
                            <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                            @if (! ($creating ?? false))<input type="hidden" name="entry_key" value="{{ $key }}">@endif<input type="hidden" name="schedule_action" value="{{ ($creating ?? false) ? 'create' : 'edit' }}">
                            @if (! ($creating ?? false))<input type="hidden" name="version" value="{{ $value('version', 0) }}">@endif
                            <header class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4">
                                <div><h2 id="{{ $modalId }}-title" class="text-lg font-semibold">{{ ($creating ?? false) ? 'Add public schedule' : 'Edit schedule' }}</h2><p class="mt-1 text-sm text-slate-500">Times use Philippine time (UTC+8).</p></div>
                                <button type="button" data-ops-modal-close aria-label="Close edit schedule dialog" class="rounded-lg px-2 py-1 text-xl text-slate-500 hover:bg-slate-100">&times;</button>
                            </header>
                            <div class="space-y-4 p-5">
                                @if ($editing && $errors->any())<div role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif
                                <label class="block text-sm font-medium">Event name
                                    <input name="event" value="{{ $value('event') }}" required maxlength="160" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                                </label>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="block text-sm font-medium">Date
                                        <input name="date" type="date" value="{{ $value('date') }}" min="{{ config('public_programme.starts_on') }}" max="{{ config('public_programme.ends_on') }}" required class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm">
                                    </label>
                                    <label class="block text-sm font-medium">Session
                                        <select name="session" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm">
                                            @foreach (['AM' => 'Morning (AM)', 'PM' => 'Afternoon (PM)', 'Evening' => 'Evening'] as $session => $label)<option value="{{ $session }}" @selected($value('session') === $session)>{{ $label }}</option>@endforeach
                                        </select>
                                    </label>
                                    <label class="block text-sm font-medium">Start time <span class="font-normal text-slate-500">(optional)</span>
                                        <input name="starts_at" type="time" data-public-start-time value="{{ $value('starts_at') }}" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm">
                                    </label>
                                    <label class="block text-sm font-medium">End / cutoff time <span class="font-normal text-slate-500">(optional)</span>
                                        <input name="ends_at" type="time" value="{{ $value('ends_at') }}" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm">
                                    </label>
                                    <label class="block text-sm font-medium">Category
                                        <select name="category" class="mt-1 block w-full rounded-lg border-slate-300 py-2.5 text-sm">
                                            @foreach (['sports' => 'Sports', 'cultural' => 'Cultural', 'activities' => 'Campus activities'] as $kind => $label)<option value="{{ $kind }}" @selected($value('category') === $kind)>{{ $label }}</option>@endforeach
                                        </select>
                                    </label>
                                    <label class="block text-sm font-medium">Venue
                                        <input name="venue" value="{{ $value('venue') }}" maxlength="160" placeholder="TBA" list="public-schedule-venues" class="mt-1 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm">
                                    </label>
                                </div>
                                <p class="text-xs text-slate-500">Leave the start time blank to show only the selected session.</p>
                                <section class="border-t border-slate-200 pt-4" aria-label="Edit game matchups">
                                    <input type="hidden" name="games_present" value="1">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="text-base font-semibold">Games / matchups</h3>
                                        <button type="button" data-programme-game-add class="rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-700">Add game</button>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Choose factions or the winner/loser of an earlier game. Logos appear automatically. Changes here update the public timetable.</p>
                                    <div data-programme-game-list class="mt-3 space-y-3">
                                        @foreach ($gameRows as $index => $game)
                                            @include('admin.schedules._game-row', ['index' => $index, 'game' => $game])
                                        @endforeach
                                    </div>
                                    <template data-programme-game-template>@include('admin.schedules._game-row', ['index' => 0, 'game' => []])</template>
                                    <p data-programme-game-empty class="mt-3 text-sm text-slate-500" @if ($gameRows) hidden @endif>No game matchups yet. Use Add game to set the opponents.</p>
                                </section>
                                <input type="hidden" name="is_published" value="0">
                                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm font-medium"><input name="is_published" type="checkbox" value="1" @checked((bool) $value('is_published', true)) class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-600"><span>Show on the public schedule</span></label>
                            </div>
                            <footer class="sticky bottom-0 z-10 flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                                <button type="button" data-ops-modal-close class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
                                <button type="submit" data-loading-label="Saving schedule…" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">{{ ($creating ?? false) ? 'Create schedule' : 'Save schedule' }}</button>
                            </footer>
                        </form>
                    </dialog>