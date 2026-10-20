@php
    $schedules = $schedules ?? collect();
    $sectionKey = $sectionKey ?? 'sports';
    $sectionLabel = $sectionLabel ?? 'Sports schedule';
    $sectionTitle = $sectionTitle ?? 'Sports events';
    $todayLabel = $todayLabel ?? '';
    $selectedDate = $selectedDate ?? null;
    $refreshNoteId = 'schedule-refresh-note-'.$sectionKey;
@endphp

<section class="schedule-board schedule-board--{{ $sectionKey }}" aria-label="{{ $sectionTitle }}">
    <div class="schedule-toolbar">
        <div>
            <p class="schedule-section-label">{{ $sectionLabel }}</p>
            <h2>{{ $sectionTitle }} <span class="schedule-count">{{ $schedules->count() }}</span></h2>
        </div>
        @if ($selectedDate)
            <a class="schedule-refresh" href="{{ route('login', ['date' => $selectedDate]) }}#schedule-heading">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6 7a7 7 0 0 1 12-1l2 6M4 12l2 6a7 7 0 0 0 12-1"/></svg>
                <span>Refresh events</span>
            </a>
        @endif
    </div>

    <div class="schedule-table-wrap hidden lg:block">
        <table class="schedule-table" aria-describedby="{{ $refreshNoteId }}">
            <caption class="sr-only">{{ $sectionTitle }} for {{ $todayLabel }}</caption>
            <colgroup><col style="width:18%"><col style="width:15%"><col style="width:34%"><col style="width:15%"><col style="width:18%"></colgroup>
            <thead><tr><th scope="col">Event</th><th scope="col">Session</th><th scope="col">Teams / participants</th><th scope="col">Venue</th><th scope="col">Facilitator</th></tr></thead>
            <tbody>
                @forelse ($schedules as $schedule)
                    <tr>
                        <th scope="row" class="schedule-sport">
                            @if ($schedule['sport_group'])
                                <span class="schedule-sport-group"><x-sport-group-icon :group="$schedule['sport_group']" /></span>
                            @endif
                            <span class="schedule-sport-name">{{ $schedule['event'] }}</span>
                        </th>
                        <td>
                            <div class="schedule-session-stack">
                                <span class="schedule-time">{{ $schedule['time'] }}</span>
                                @if (! empty($schedule['cutoff']))<span class="schedule-game">{{ $schedule['cutoff'] }}</span>@endif
                                <span class="schedule-session {{ $schedule['period'] === 'Morning' ? 'schedule-session--morning' : 'schedule-session--afternoon' }}"><span aria-hidden="true"></span>{{ $schedule['period'] }}</span>
                            </div>
                        </td>
                        <td class="schedule-match">
                            @if ($schedule['game'])<span class="schedule-game">{{ $schedule['game'] }}</span>@endif
                            @if (! empty($schedule['competitor_teams']))
                                <span class="schedule-competitor-list" aria-label="{{ $schedule['competitors'] }}">
                                    @foreach ($schedule['competitor_teams'] as $team)
                                        <x-team-badge :team="$team" :show-subtitle="false" :logo-only="count($schedule['competitor_teams']) > 2" class="schedule-team-badge" />
                                        @unless ($loop->last)<span class="schedule-versus">VS</span>@endunless
                                    @endforeach
                                </span>
                            @else
                                <span class="schedule-competitors">{{ $schedule['competitors'] }}</span>
                            @endif
                        </td>
                        <td class="schedule-facilitator schedule-venue">{{ $schedule['venue'] }}</td>
                        <td class="schedule-facilitator">{{ $schedule['facilitator'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-landing.schedule-empty /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="space-y-3 lg:hidden" aria-describedby="{{ $refreshNoteId }}">
        @forelse ($schedules as $schedule)
            <article class="schedule-match-card">
                <header>
                    <div>
                        @if ($schedule['sport_group'])
                            <span class="schedule-sport-group"><x-sport-group-icon :group="$schedule['sport_group']" /></span>
                        @endif
                        <h3>{{ $schedule['event'] }}</h3><time>{{ $schedule['time'] }}</time>
                        @if (! empty($schedule['cutoff']))<span class="schedule-game">{{ $schedule['cutoff'] }}</span>@endif
                    </div>
                    <span class="schedule-session {{ $schedule['period'] === 'Morning' ? 'schedule-session--morning' : 'schedule-session--afternoon' }}"><span aria-hidden="true"></span>{{ $schedule['period'] }}</span>
                </header>
                <div class="schedule-mobile-match">
                    @if ($schedule['game'])<span class="schedule-game">{{ $schedule['game'] }}</span>@endif
                    @if (! empty($schedule['competitor_teams']))
                        <p class="schedule-competitor-list" aria-label="{{ $schedule['competitors'] }}">
                            @foreach ($schedule['competitor_teams'] as $team)
                                <x-team-badge :team="$team" :show-subtitle="false" :logo-only="count($schedule['competitor_teams']) > 2" class="schedule-team-badge" />
                                @unless ($loop->last)<span class="schedule-versus">VS</span>@endunless
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

    <footer class="schedule-board-footer">
        <p id="{{ $refreshNoteId }}">{{ ! empty($programmeSource) ? 'AM/PM follow the published programme. Exact hours are shown where provided.' : 'Updates appear as coordinator entries are published.' }}</p>
        <span>{{ $schedules->count() }} {{ \Illuminate\Support\Str::plural('event', $schedules->count()) }}</span>
    </footer>
</section>
