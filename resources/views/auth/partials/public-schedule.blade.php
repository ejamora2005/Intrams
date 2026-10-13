@push('styles')
    @vite('resources/css/public-schedule.css')
@endpush

@php
    $scheduleCount = $todaySchedules->count();
    $eventCount = $todaySchedules->pluck('event')->unique()->count();
    $morningCount = $todaySchedules->where('period', 'Morning')->count();
    $afternoonCount = $todaySchedules->where('period', 'Afternoon')->count();
    $themeTitle = config('intramurals.theme.title');
    $themeTagline = config('intramurals.theme.tagline');
    $selectedDate = $selectedDate ?? now()->format('Y-m-d');
@endphp

<main class="schedule-page" style="--schedule-background: url('{{ asset(config('landing.background')) }}')">
    <div class="schedule-shell">
        <header class="schedule-nav">
            <a class="schedule-brand" href="{{ url('/') }}" aria-label="SLSU INTRAMURALS home">
                <img src="{{ asset(config('landing.appLogo')) }}" alt="" class="schedule-seal" width="72" height="66" decoding="async">
                <span>SLSU INTRAMURALS<small>Southern Leyte State University</small></span>
            </a>
            <nav class="schedule-links" aria-label="Event navigation">
                <a href="{{ url('/') }}" class="schedule-home" aria-label="Back to team standings"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m14 6-6 6 6 6M8 12h13" /></svg><span>Standings</span></a>
                <button type="button" data-open-login class="schedule-login"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M14 4h5v16h-5M3 12h12m-4-4 4 4-4 4" /></svg>Login</button>
            </nav>
        </header>

        <section class="schedule-hero" aria-labelledby="schedule-heading">
            <div class="schedule-intro">
                <p class="schedule-eyebrow">{{ $themeTitle }}</p>
                <h1 id="schedule-heading">Today's <span>events</span></h1>
                <p class="schedule-description">{{ $themeTagline }} Official event board for the SLSU Bontoc Intramural Meet.</p>
                <p class="schedule-edition"><span aria-hidden="true"></span>{{ $currentEdition?->name ?? 'No active intramurals edition' }}</p>
                <ul class="schedule-metrics" aria-label="Event summary">
                    <li><strong>{{ $scheduleCount }}</strong><span>Matches</span></li>
                    <li><strong>{{ $eventCount }}</strong><span>Events</span></li>
                    <li><strong>{{ $morningCount }}</strong><span>Morning</span></li>
                    <li><strong>{{ $afternoonCount }}</strong><span>Afternoon</span></li>
                </ul>
            </div>
            <div class="schedule-date">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-13 4h3m2 0h3"/></svg>
                <div>
                    <p>SLSU event desk</p>
                    <strong>{{ $todayLabel }}</strong>
                    <span>Morning &amp; afternoon competitions</span>
                    @if (! empty($availableDates))
                        <form method="GET" action="{{ route('login') }}" class="schedule-date-picker">
                            <label for="schedule-date">View date</label>
                            <select id="schedule-date" name="date" onchange="this.form.submit()">
                                @foreach ($availableDates as $availableDate)
                                    <option value="{{ $availableDate }}" @selected($availableDate === $selectedDate)>{{ \Carbon\Carbon::parse($availableDate)->format('M j, Y') }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <ul class="schedule-teams" aria-label="Campus teams">
            @foreach (config('landing.teams') as $team)
                <li style="--team-color: {{ $team['primaryColor'] }}; --team-highlight: {{ $team['secondaryColor'] }}">
                    @if ($team['image'] ?? null)
                        <img class="schedule-team-logo" src="{{ asset($team['image']) }}" alt="" width="44" height="44" decoding="async">
                    @else
                        <span class="schedule-team-pennant" aria-hidden="true"></span>
                    @endif
                    <span>{{ $team['name'] }}<small>{{ $team['department'] }}</small></span>
                </li>
            @endforeach
        </ul>

        <section class="schedule-board" aria-label="Today's competitions">
            <div class="schedule-toolbar">
                <div><p class="schedule-section-label">Today's events</p><h2>Happening today <span class="schedule-count">{{ $scheduleCount }}</span></h2></div>
                <a class="schedule-refresh" href="{{ route('login', ['date' => $selectedDate]) }}#schedule-heading"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6 7a7 7 0 0 1 12-1l2 6M4 12l2 6a7 7 0 0 0 12-1"/></svg><span>Refresh events</span></a>
            </div>

            <div class="schedule-table-wrap hidden lg:block">
                <table class="schedule-table" aria-describedby="schedule-refresh-note">
                    <caption class="sr-only">Events for {{ $todayLabel }}</caption>
                    <colgroup><col style="width:18%"><col style="width:15%"><col style="width:34%"><col style="width:15%"><col style="width:18%"></colgroup>
                    <thead><tr><th scope="col">Event</th><th scope="col">Session</th><th scope="col">Teams / participants</th><th scope="col">Venue</th><th scope="col">Facilitator</th></tr></thead>
                    <tbody>
                        @forelse ($todaySchedules->groupBy('event') as $event => $eventSchedules)
                            @php($sharedFacilitator = $eventSchedules->pluck('facilitator')->unique()->count() === 1)
                            @foreach ($eventSchedules as $schedule)
                                <tr>
                                    @if ($loop->first)
                                        <th scope="rowgroup" rowspan="{{ $eventSchedules->count() }}" class="schedule-sport">@if ($schedule['sport_group'])<span class="schedule-sport-group"><x-sport-group-icon :group="$schedule['sport_group']" /><small>{{ $schedule['sport_group_label'] }}</small></span>@endif{{ $event }}</th>
                                    @endif
                                    <td>
                                        <div class="schedule-session-stack">
                                            <span class="schedule-time">{{ $schedule['time'] }}</span>
                                            <span class="schedule-session {{ $schedule['period'] === 'Morning' ? 'schedule-session--morning' : 'schedule-session--afternoon' }}"><span aria-hidden="true"></span>{{ $schedule['period'] }}</span>
                                        </div>
                                    </td>
                                    <td class="schedule-match">
                                        @if ($schedule['game'])<span class="schedule-game">{{ $schedule['game'] }}</span>@endif
                                        @if (! empty($schedule['competitor_teams']))
                                            <span class="schedule-competitor-list" aria-label="{{ $schedule['competitors'] }}">
                                                @foreach ($schedule['competitor_teams'] as $team)
                                                    <x-team-badge :team="$team" class="schedule-team-badge" />
                                                    @unless ($loop->last)
                                                        <span class="schedule-versus">VS</span>
                                                    @endunless
                                                @endforeach
                                            </span>
                                        @else
                                            <span class="schedule-competitors">{{ $schedule['competitors'] }}</span>
                                        @endif
                                    </td>
                                    <td class="schedule-facilitator">{{ $schedule['venue'] }}</td>
                                    @if (! $sharedFacilitator || $loop->first)
                                        <td @if ($sharedFacilitator) rowspan="{{ $eventSchedules->count() }}" @endif class="schedule-facilitator">{{ $schedule['facilitator'] }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="5"><x-landing.schedule-empty /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 lg:hidden" aria-describedby="schedule-refresh-note">
                @forelse ($todaySchedules as $schedule)
                    <article class="schedule-match-card">
                        <header>
                            <div>
                                @if ($schedule['sport_group'])
                                    <span class="schedule-sport-group"><x-sport-group-icon :group="$schedule['sport_group']" /><small>{{ $schedule['sport_group_label'] }}</small></span>
                                @endif
                                <h3>{{ $schedule['event'] }}</h3><time>{{ $schedule['time'] }}</time>
                            </div>
                            <span class="schedule-session {{ $schedule['period'] === 'Morning' ? 'schedule-session--morning' : 'schedule-session--afternoon' }}"><span aria-hidden="true"></span>{{ $schedule['period'] }}</span>
                        </header>
                        <div class="schedule-mobile-match">
                            @if ($schedule['game'])<span class="schedule-game">{{ $schedule['game'] }}</span>@endif
                            @if (! empty($schedule['competitor_teams']))
                                <p class="schedule-competitor-list" aria-label="{{ $schedule['competitors'] }}">
                                    @foreach ($schedule['competitor_teams'] as $team)
                                        <x-team-badge :team="$team" class="schedule-team-badge" />
                                        @unless ($loop->last)
                                            <span class="schedule-versus">VS</span>
                                        @endunless
                                    @endforeach
                                </p>
                            @else
                                <p class="schedule-competitors">{{ $schedule['competitors'] }}</p>
                            @endif
                        </div>
                        <dl><dt>Venue</dt><dd>{{ $schedule['venue'] }}</dd></dl>
                        <dl><dt>Facilitator</dt><dd>{{ $schedule['facilitator'] }}</dd></dl>
                    </article>
                @empty
                    <x-landing.schedule-empty />
                @endforelse
            </div>
            <footer class="schedule-board-footer"><p id="schedule-refresh-note">Updates appear as coordinator entries are published.</p><span>Today's events</span></footer>
        </section>
        <footer class="schedule-footer"><span>SLSU Bontoc Campus</span><span>SLSU INTRAMURALS</span></footer>
    </div>
</main>
