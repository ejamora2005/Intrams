<article class="relative w-64 rounded-lg border border-slate-300 bg-white shadow-sm" data-bracket-match>
    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Game {{ $match->match_number }}</p>
        <span class="rounded border px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $match->status === 'completed' ? 'border-green-200 bg-green-50 text-green-700' : ($match->status === 'bye' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-500') }}">{{ $match->status }}</span>
    </div>
    <div class="divide-y divide-slate-200">
        @foreach ([$match->competitorOne, $match->competitorTwo] as $competitor)
            @if ($competitor)
                <button type="button" data-match="{{ $match->id }}" data-competitor-id="{{ $competitor->id }}" aria-pressed="false" class="bracket-team flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm font-semibold {{ $match->winner_competitor_id === $competitor->id ? 'bg-green-50 text-green-800' : 'bg-white text-slate-800' }}" @disabled($match->status !== 'pending' || ! $match->competitor_one_id || ! $match->competitor_two_id)>
                    <span class="flex min-w-0 items-center gap-2">
                        @if ($competitor->team)
                            <x-team-badge :team="$competitor->team" size="xs" :logo-only="true" />
                        @endif
                        <span class="truncate">{{ $competitor->label }}</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2">
                        <span class="bracket-selection-indicator hidden rounded bg-blue-700 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Selected</span>
                        @if ($match->winner_competitor_id === $competitor->id)
                            <span class="text-xs text-green-700">Winner</span>
                        @endif
                    </span>
                </button>
            @else
                <div class="px-3 py-2.5 text-sm text-slate-400">{{ $match->status === 'bye' ? 'Bye' : 'Awaiting competitor' }}</div>
            @endif
        @endforeach
    </div>

    @if ($match->status === 'pending' && $match->competitor_one_id && $match->competitor_two_id)
        <div id="match-actions-{{ $match->id }}" class="match-actions hidden border-t border-blue-100 bg-blue-50 p-3 text-xs text-blue-900">
            <p class="mb-2 font-semibold">Record the selected competitor</p>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.sports.bracket.result', [$sport, $match]) }}">
                    @csrf
                    <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                    <input class="selected-winner" type="hidden" name="winner_competitor_id">
                    <button class="rounded bg-blue-700 px-2.5 py-1.5 font-semibold text-white hover:bg-blue-800">Declare win</button>
                </form>
                <form method="POST" action="{{ route('admin.sports.bracket.result', [$sport, $match]) }}">
                    @csrf
                    <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                    <input class="selected-loser-winner" type="hidden" name="winner_competitor_id">
                    <button class="rounded border border-red-200 bg-white px-2.5 py-1.5 font-semibold text-red-700 hover:bg-red-50">Declare loss</button>
                </form>
            </div>
        </div>
        <div class="border-t border-slate-200 bg-white p-3 text-xs">
            <button type="button" data-schedule-toggle aria-expanded="false" aria-controls="match-schedule-{{ $match->id }}" class="w-full rounded border border-blue-200 bg-white px-2.5 py-1.5 font-semibold text-blue-700 hover:bg-blue-50">{{ $match->schedule ? 'Edit schedule' : 'Schedule game' }}</button>
            <form id="match-schedule-{{ $match->id }}" data-schedule-form method="POST" action="{{ route('admin.sports.bracket.schedule', [$sport, $match]) }}" class="mt-3 hidden space-y-2 border-t border-blue-200 pt-3">
                @csrf
                <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                <label class="block font-semibold" for="schedule-date-{{ $match->id }}">Date</label>
                <input id="schedule-date-{{ $match->id }}" name="date" type="date" required min="{{ $edition->starts_on->format('Y-m-d') }}" max="{{ $edition->ends_on->format('Y-m-d') }}" value="{{ $match->schedule?->starts_at?->format('Y-m-d') ?? $edition->starts_on->format('Y-m-d') }}" class="block w-full rounded border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                <label class="block font-semibold" for="schedule-period-{{ $match->id }}">Time of day</label>
                <select id="schedule-period-{{ $match->id }}" name="period" required class="block w-full rounded border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                    <option value="morning" @selected(! $match->schedule || $match->schedule->starts_at->hour < 12)>Morning</option>
                    <option value="afternoon" @selected($match->schedule && $match->schedule->starts_at->hour >= 12)>Afternoon</option>
                </select>
                <label class="block font-semibold" for="schedule-venue-{{ $match->id }}">Venue</label>
                <input id="schedule-venue-{{ $match->id }}" name="venue" type="text" maxlength="120" value="{{ old('venue', $match->schedule?->venue ?: 'TBA') }}" class="block w-full rounded border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                <button class="w-full rounded bg-blue-700 px-3 py-2 font-semibold text-white hover:bg-blue-800">Save schedule</button>
            </form>
        </div>
    @elseif ($match->status === 'bye')
        <p class="border-t border-slate-200 px-3 py-2 text-xs font-medium text-blue-700">Advanced by bye</p>
    @elseif ($match->status === 'completed')
        <p class="border-t border-slate-200 px-3 py-2 text-xs font-medium text-green-700">{{ $match->bracket === 'round_robin' ? 'Winner recorded' : 'Winner advanced automatically' }}</p>
    @endif
</article>
