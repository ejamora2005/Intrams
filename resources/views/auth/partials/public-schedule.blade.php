@push('styles')
    @vite('resources/css/public-schedule.css')
@endpush

@php
    $scheduleCount = $todaySchedules->count();
    $sportCount = $todaySchedules->pluck('sport')->unique()->count();
    $morningCount = $todaySchedules->where('period', 'Morning')->count();
    $afternoonCount = $todaySchedules->where('period', 'Afternoon')->count();
    $themeTitle = config('intramurals.theme.title');
    $themeTagline = config('intramurals.theme.tagline');
@endphp

<main class="schedule-page" style="--schedule-background: url('{{ asset(config('landing.background')) }}')">
    <div class="schedule-shell">
        <header class="schedule-nav">
            <a class="schedule-brand" href="{{ url('/') }}" aria-label="SLSU Bontoc intramurals home">
                @if (config('landing.universityLogo'))
                    <img src="{{ asset(config('landing.universityLogo')) }}" alt="" class="schedule-seal" width="44" height="44">
                @else
                    <svg class="schedule-seal" viewBox="736 104 640 640" aria-hidden="true"><image href="{{ asset(config('landing.background')) }}" width="1672" height="941" /></svg>
                @endif
                <span>Southern Leyte State University<small>Bontoc Campus · Intramurals</small></span>
            </a>
            <nav class="schedule-links" aria-label="Event navigation">
                <a href="{{ url('/') }}" class="schedule-home" aria-label="Back to team standings"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m14 6-6 6 6 6M8 12h13" /></svg><span>Standings</span></a>
                <button type="button" data-open-login class="schedule-login"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M14 4h5v16h-5M3 12h12m-4-4 4 4-4 4" /></svg>Login</button>
            </nav>
        </header>

        <section class="schedule-hero" aria-labelledby="schedule-heading">
            <div class="schedule-intro">
                <p class="schedule-eyebrow">{{ $themeTitle }}</p>
                <h1 id="schedule-heading">Today's match <span>schedule</span></h1>
                <p class="schedule-description">{{ $themeTagline }} Official match board for the SLSU Bontoc Intramural Meet.</p>
                <p class="schedule-edition"><span aria-hidden="true"></span>{{ $currentEdition?->name ?? 'No active intramurals edition' }}</p>
                <ul class="schedule-metrics" aria-label="Schedule summary">
                    <li><strong>{{ $scheduleCount }}</strong><span>Matches</span></li>
                    <li><strong>{{ $sportCount }}</strong><span>Sports</span></li>
                    <li><strong>{{ $morningCount }}</strong><span>Morning</span></li>
                    <li><strong>{{ $afternoonCount }}</strong><span>Afternoon</span></li>
                </ul>
            </div>
            <div class="schedule-date">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-13 4h3m2 0h3"/></svg>
                <div><p>SLSU schedule desk</p><strong>{{ $todayLabel }}</strong><span>Morning &amp; afternoon competitions</span></div>
            </div>
        </section>

        <ul class="schedule-teams" aria-label="Campus teams">
            @foreach (config('landing.teams') as $team)
                <li style="--team-color: {{ $team['primaryColor'] }}; --team-highlight: {{ $team['secondaryColor'] }}">
                    <span class="schedule-team-pennant" aria-hidden="true"></span>
                    <span>{{ $team['name'] }}<small>{{ $team['department'] }}</small></span>
                </li>
            @endforeach
        </ul>

        <section class="schedule-board" aria-label="Today's competitions">
            <div class="schedule-toolbar">
                <div><p class="schedule-section-label">Match schedule</p><h2>On the schedule today <span class="schedule-count">{{ $scheduleCount }}</span></h2></div>
                <a class="schedule-refresh" href="{{ route('login') }}#schedule-heading"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6 7a7 7 0 0 1 12-1l2 6M4 12l2 6a7 7 0 0 0 12-1"/></svg><span>Refresh schedule</span></a>
            </div>

            <div class="schedule-table-wrap hidden lg:block">
                <table class="schedule-table" aria-describedby="schedule-refresh-note">
                    <caption class="sr-only">Competitions scheduled for {{ $todayLabel }}</caption>
                    <colgroup><col style="width:18%"><col style="width:15%"><col style="width:34%"><col style="width:15%"><col style="width:18%"></colgroup>
                    <thead><tr><th scope="col">Sport</th><th scope="col">Session</th><th scope="col">Teams / participants</th><th scope="col">Venue</th><th scope="col">Facilitator</th></tr></thead>
                    <tbody>
                        @forelse ($todaySchedules->groupBy('sport') as $sport => $sportSchedules)
                            @php($sharedFacilitator = $sportSchedules->pluck('facilitator')->unique()->count() === 1)
                            @foreach ($sportSchedules as $schedule)
                                <tr>
                                    @if ($loop->first)
                                        <th scope="rowgroup" rowspan="{{ $sportSchedules->count() }}" class="schedule-sport">{{ $sport }}</th>
                                    @endif
                                    <td>
                                        <div class="schedule-session-stack">
                                            <span class="schedule-time">{{ $schedule['time'] }}</span>
                                            <span class="schedule-session {{ $schedule['period'] === 'Morning' ? 'schedule-session--morning' : 'schedule-session--afternoon' }}"><span aria-hidden="true"></span>{{ $schedule['period'] }}</span>
                                        </div>
                                    </td>
                                    <td class="schedule-match">
                                        @if ($schedule['game'])<span class="schedule-game">{{ $schedule['game'] }}</span>@endif
                                        <span class="schedule-competitors">{{ $schedule['competitors'] }}</span>
                                    </td>
                                    <td class="schedule-facilitator">{{ $schedule['venue'] }}</td>
                                    @if (! $sharedFacilitator || $loop->first)
                                        <td @if ($sharedFacilitator) rowspan="{{ $sportSchedules->count() }}" @endif class="schedule-facilitator">{{ $schedule['facilitator'] }}</td>
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
                            <div><h3>{{ $schedule['sport'] }}</h3><time>{{ $schedule['time'] }}</time></div>
                            <span class="schedule-session {{ $schedule['period'] === 'Morning' ? 'schedule-session--morning' : 'schedule-session--afternoon' }}"><span aria-hidden="true"></span>{{ $schedule['period'] }}</span>
                        </header>
                        <div class="schedule-mobile-match">
                            @if ($schedule['game'])<span class="schedule-game">{{ $schedule['game'] }}</span>@endif
                            <p class="schedule-competitors">{{ $schedule['competitors'] }}</p>
                        </div>
                        <dl><dt>Venue</dt><dd>{{ $schedule['venue'] }}</dd></dl>
                        <dl><dt>Facilitator</dt><dd>{{ $schedule['facilitator'] }}</dd></dl>
                    </article>
                @empty
                    <x-landing.schedule-empty />
                @endforelse
            </div>
            <footer class="schedule-board-footer"><p id="schedule-refresh-note">Updates appear as coordinator entries are published.</p><span>Schedule for today</span></footer>
        </section>
        <footer class="schedule-footer"><span>SLSU Bontoc Campus</span><span>Intramurals Management System</span></footer>
    </div>
</main>
