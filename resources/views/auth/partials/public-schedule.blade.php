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
    $factionRosters = $factionRosters ?? collect();
    $factionRostersByKey = $factionRosters->keyBy('key');
    $configuredTeamKeys = collect(config('landing.teams', []))
        ->map(fn (array $team): string => \App\Models\Team::logoKey($team['name'] ?? ''));
    $unlistedFactions = $factionRosters->reject(fn (array $faction): bool => $configuredTeamKeys->contains($faction['key']));
@endphp

<main class="schedule-page" style="--schedule-background: url('{{ asset(config('landing.background')) }}')">
    <div class="schedule-shell">
        <header class="schedule-nav">
            <a class="schedule-brand" href="{{ url('/') }}" aria-label="SLSU INTRAMURALS home">
                <img src="{{ asset(config('landing.appLogo')) }}" alt="" class="schedule-seal" width="72" height="66" decoding="async">
                <span>SLSU INTRAMURALS<small>Southern Leyte State University</small></span>
            </a>
            <nav class="schedule-links" aria-label="Event navigation">
                <a href="{{ url('/') }}#event-results" class="schedule-home">Event results</a>
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

        <ul class="schedule-teams" aria-label="Campus factions">
            @foreach (config('landing.teams') as $team)
                @php
                    $roster = $factionRostersByKey->get(\App\Models\Team::logoKey($team['name'] ?? ''));
                @endphp
                <li style="--team-color: {{ $team['primaryColor'] }}; --team-highlight: {{ $team['secondaryColor'] }}">
                    <div class="schedule-team-identity">
                        @if ($team['image'] ?? null)
                            <img class="schedule-team-logo" src="{{ asset($team['image']) }}" alt="" width="44" height="44" decoding="async">
                        @else
                            <span class="schedule-team-pennant" aria-hidden="true"></span>
                        @endif
                        <span class="schedule-team-name">{{ $team['name'] }}<small>{{ $team['department'] }}</small></span>
                    </div>
                    @if ($roster)
                        <button type="button" class="schedule-roster-trigger" data-open-roster data-roster-id="roster-panel-{{ $roster['id'] }}" aria-haspopup="dialog">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm6-6.5a3 3 0 0 1 0 5.83M19.5 20v-1.5a3.5 3.5 0 0 0-2.5-3.35"/></svg>
                            <span>View players</span>
                            <strong>{{ $roster['member_count'] }}</strong>
                        </button>
                    @endif
                </li>
            @endforeach
            @foreach ($unlistedFactions as $faction)
                <li class="schedule-team-card--database" style="--team-color: #0c7345; --team-highlight: #d9b87d">
                    <div class="schedule-team-identity">
                        @if ($faction['logo_path'])
                            <img class="schedule-team-logo" src="{{ asset($faction['logo_path']) }}" alt="" width="44" height="44" decoding="async">
                        @else
                            <span class="schedule-team-pennant" aria-hidden="true"></span>
                        @endif
                        <span class="schedule-team-name">{{ $faction['name'] }}<small>{{ $faction['code'] ?: 'Active faction' }}</small></span>
                    </div>
                    <button type="button" class="schedule-roster-trigger" data-open-roster data-roster-id="roster-panel-{{ $faction['id'] }}" aria-haspopup="dialog">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm6-6.5a3 3 0 0 1 0 5.83M19.5 20v-1.5a3.5 3.5 0 0 0-2.5-3.35"/></svg>
                        <span>View players</span>
                        <strong>{{ $faction['member_count'] }}</strong>
                    </button>
                </li>
            @endforeach
        </ul>

        @if ($factionRosters->isNotEmpty())
            <dialog class="schedule-roster-modal" data-roster-modal aria-labelledby="roster-modal-heading">
                <div class="schedule-roster-dialog">
                    <header class="schedule-roster-header">
                        <div>
                            <p class="schedule-section-label">Faction rosters</p>
                            <h2 id="roster-modal-heading">Meet the players</h2>
                            <p>Choose a faction to see its registered players and sports.</p>
                        </div>
                        <button type="button" class="schedule-roster-close" data-close-roster aria-label="Close player list">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                        </button>
                    </header>
                    <div class="schedule-roster-body">
                        <nav class="schedule-roster-tabs" aria-label="Faction roster selection" role="tablist">
                            @foreach ($factionRosters as $faction)
                                <button type="button" id="roster-tab-{{ $faction['id'] }}" class="schedule-roster-tab" data-roster-tab data-roster-target="roster-panel-{{ $faction['id'] }}" role="tab" aria-controls="roster-panel-{{ $faction['id'] }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}">
                                    @if ($faction['logo_path'])
                                        <img src="{{ asset($faction['logo_path']) }}" alt="" width="30" height="30" decoding="async">
                                    @endif
                                    <span>{{ $faction['name'] }}</span>
                                    <strong>{{ $faction['member_count'] }}</strong>
                                </button>
                            @endforeach
                        </nav>
                        <div class="schedule-roster-panels">
                            @foreach ($factionRosters as $faction)
                                <section id="roster-panel-{{ $faction['id'] }}" class="schedule-roster-panel" data-roster-panel role="tabpanel" aria-labelledby="roster-tab-{{ $faction['id'] }}" @if (! $loop->first) hidden @endif>
                                    <div class="schedule-roster-panel-heading">
                                        <div>
                                            <p>Active faction</p>
                                            <h3>{{ $faction['name'] }}</h3>
                                        </div>
                                        <span>{{ $faction['member_count'] }} {{ \Illuminate\Support\Str::plural('player', $faction['member_count']) }}</span>
                                    </div>
                                    @if (count($faction['members']))
                                        <ol class="schedule-roster-list">
                                            @foreach ($faction['members'] as $member)
                                                <li>
                                                    <span class="schedule-roster-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                                    <div>
                                                        <strong>{{ $member['name'] }}</strong>
                                                        @if (count($member['sports']))
                                                            <span class="schedule-roster-sports">
                                                                @foreach ($member['sports'] as $sport)
                                                                    <small>{{ $sport['name'] }}@if(filled($sport['category']))<em>{{ $sport['category'] }}</em>@endif</small>
                                                                @endforeach
                                                            </span>
                                                        @else
                                                            <span class="schedule-roster-unassigned">Faction roster member</span>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    @else
                                        <div class="schedule-roster-empty">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm6-6.5a3 3 0 0 1 0 5.83M19.5 20v-1.5a3.5 3.5 0 0 0-2.5-3.35"/></svg>
                                            <p>No players have been published for this faction yet.</p>
                                        </div>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </div>
                </div>
            </dialog>
        @endif

        @php
            $scheduleSections = [
                [
                    'key' => 'sports',
                    'label' => 'Sports schedule',
                    'title' => 'Sports events',
                    'schedules' => $todaySchedules->where('category', 'sports')->values(),
                ],
                [
                    'key' => 'cultural',
                    'label' => 'Cultural schedule',
                    'title' => 'Cultural events',
                    'schedules' => $todaySchedules->where('category', 'cultural')->values(),
                ],
                [
                    'key' => 'activities',
                    'label' => 'Programme & activities',
                    'title' => 'Campus activities',
                    'schedules' => $todaySchedules->where('category', 'activities')->values(),
                ],
            ];
        @endphp

        <div class="schedule-boards" aria-label="Event schedules">
            @if ($todaySchedules->isEmpty())
                @include('auth.partials.public-schedule-board', [
                    'schedules' => collect(),
                    'sectionKey' => 'empty',
                    'sectionLabel' => "Today's events",
                    'sectionTitle' => 'No events scheduled',
                    'todayLabel' => $todayLabel,
                    'selectedDate' => $selectedDate,
                ])
            @else
                @foreach ($scheduleSections as $section)
                    @if ($section['schedules']->isNotEmpty())
                        @include('auth.partials.public-schedule-board', [
                            'schedules' => $section['schedules'],
                            'sectionKey' => $section['key'],
                            'sectionLabel' => $section['label'],
                            'sectionTitle' => $section['title'],
                            'todayLabel' => $todayLabel,
                            'selectedDate' => $selectedDate,
                        ])
                    @endif
                @endforeach
            @endif
        </div>
        <footer class="schedule-footer"><span>SLSU Bontoc Campus</span><span>SLSU INTRAMURALS</span></footer>
    </div>
</main>
