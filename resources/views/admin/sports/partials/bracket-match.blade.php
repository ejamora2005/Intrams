@php
    $bracketBlank = $bracketBlank ?? false;
    $displayStatus = $bracketBlank ? 'unassigned' : $match->status;
    $matchCompetitors = $bracketBlank ? [null, null] : [$match->competitorOne, $match->competitorTwo];
@endphp
<article class="relative w-64 rounded-lg border border-slate-300 bg-white shadow-sm" data-bracket-match>
    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Game {{ $match->match_number }}</p>
        <span class="rounded border px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $displayStatus === 'completed' ? 'border-green-200 bg-green-50 text-green-700' : ($displayStatus === 'bye' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-500') }}">{{ $displayStatus }}</span>
    </div>
    <div class="divide-y divide-slate-200">
        @foreach ($matchCompetitors as $competitor)
            @php
                $bracketSlotIndex = isset($bracketSlotStart) && $bracketSlotStart !== null
                    ? $bracketSlotStart + $loop->index
                    : null;
                $canDragBracketSlot = $bracketSlotIndex !== null && ($bracketBlank || $match->status === 'pending');
            @endphp
            @if ($competitor)
                <button type="button" data-match="{{ $match->id }}" data-competitor-id="{{ $competitor->id }}" @if ($bracketSlotIndex !== null) data-bracket-slot-card data-bracket-slot-index="{{ $bracketSlotIndex }}" @endif aria-pressed="false" class="bracket-team flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm font-semibold {{ $match->winner_competitor_id === $competitor->id ? 'bg-green-50 text-green-800' : 'bg-white text-slate-800' }} {{ $canDragBracketSlot ? 'cursor-grab active:cursor-grabbing' : '' }}" @if ($canDragBracketSlot) draggable="true" @endif @disabled($match->status !== 'pending' || ! $match->competitor_one_id || ! $match->competitor_two_id)>
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
                <div @if ($bracketSlotIndex !== null) data-bracket-slot-card data-bracket-slot-index="{{ $bracketSlotIndex }}" @endif class="px-3 py-2.5 text-sm text-slate-400 {{ $canDragBracketSlot ? 'bracket-slot-empty' : '' }}">@if ($bracketBlank && $bracketSlotIndex !== null)<span data-bracket-slot-label>Drop team here</span>@else{{ $match->status === 'bye' ? 'Bye' : 'Awaiting competitor' }}@endif</div>
            @endif
        @endforeach
    </div>

    @if (! $bracketBlank && $match->status === 'pending' && $match->competitor_one_id && $match->competitor_two_id)
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
            @if ($match->schedule)
                <p class="mb-2 rounded border border-slate-200 bg-slate-50 px-2.5 py-1.5 font-semibold text-slate-700">
                    {{ $match->schedule->starts_at->format('M j, Y') }} &middot; {{ $match->schedule->starts_at->format('g:i A') }}<br>
                    <span class="font-normal text-slate-500">{{ $match->schedule->venue ?: 'TBA' }}</span>
                </p>
            @endif
            <button type="button" data-schedule-toggle aria-expanded="false" aria-controls="match-schedule-{{ $match->id }}" class="w-full rounded border border-blue-200 bg-white px-2.5 py-1.5 font-semibold text-blue-700 hover:bg-blue-50">{{ $match->schedule ? 'Edit schedule' : 'Schedule game' }}</button>
            <form id="match-schedule-{{ $match->id }}" data-schedule-form method="POST" action="{{ route('admin.sports.bracket.schedule', [$sport, $match]) }}" class="mt-3 hidden space-y-2 border-t border-blue-200 pt-3">
                @csrf
                <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                <label class="block font-semibold" for="schedule-date-{{ $match->id }}">Date</label>
                <input id="schedule-date-{{ $match->id }}" name="date" type="date" required min="{{ $edition->starts_on->format('Y-m-d') }}" max="{{ $edition->ends_on->format('Y-m-d') }}" value="{{ $match->schedule?->starts_at?->format('Y-m-d') ?? $edition->starts_on->format('Y-m-d') }}" class="block w-full rounded border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                <label class="block font-semibold" for="schedule-time-{{ $match->id }}">Time of day</label>
                <input id="schedule-time-{{ $match->id }}" name="time" type="time" required step="60" value="{{ $match->schedule?->starts_at?->format('H:i') ?? '08:00' }}" class="block w-full rounded border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                <p class="text-[11px] text-slate-500">Enter the exact start time. The public schedule will show it in 12-hour format.</p>
                <label class="block font-semibold" for="schedule-venue-{{ $match->id }}">Venue</label>
                @php
                    $defaultVenue = str_starts_with(strtoupper((string) $sport->code), 'BASKET-') ? 'MPCC' : 'TBA';
                @endphp
                <input id="schedule-venue-{{ $match->id }}" name="venue" type="text" maxlength="120" value="{{ old('venue', $match->schedule?->venue ?: $defaultVenue) }}" class="block w-full rounded border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                <button class="w-full rounded bg-blue-700 px-3 py-2 font-semibold text-white hover:bg-blue-800">Save schedule</button>
            </form>
        </div>
    @elseif (! $bracketBlank && $match->status === 'bye')
        <p class="border-t border-slate-200 px-3 py-2 text-xs font-medium text-blue-700">Advanced by bye</p>
    @elseif (! $bracketBlank && $match->status === 'completed')
        <p class="border-t border-slate-200 px-3 py-2 text-xs font-medium text-green-700">{{ $match->bracket === 'round_robin' ? 'Winner recorded' : 'Winner advanced automatically' }}</p>
    @endif
</article>
