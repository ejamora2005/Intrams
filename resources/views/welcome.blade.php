<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="landing-document">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-pwa-meta theme="#06152c" />
        <meta name="description" content="Southern Leyte State University — Bontoc Campus. Intramural Meet 2026 team standings and event schedule.">
        <title>Intramural Meet 2026 | SLSU Bontoc Campus</title>
        <link rel="preload" as="image" href="{{ asset(config('landing.background')) }}">
        @vite(['resources/css/landing.css', 'resources/js/landing.js'])
    </head>
    <body class="landing-body">
        <main class="meet-landing" style="--stadium-image: url('{{ asset(config('landing.background')) }}')">
            <div class="stadium-backdrop" aria-hidden="true"></div>
            <div class="stadium-atmosphere" aria-hidden="true"></div>
            <header class="meet-header">
                <div class="university-brand">
                    @if (config('landing.universityLogo'))
                        <img class="university-seal" src="{{ asset(config('landing.universityLogo')) }}" alt="Southern Leyte State University seal" width="54" height="54">
                    @else
                        {{-- Display the actual crest embedded in the supplied image; no recreated university mark. --}}
                        <svg class="university-seal" viewBox="736 104 640 640" role="img" aria-label="Southern Leyte State University seal">
                            <image href="{{ asset(config('landing.background')) }}" width="1672" height="941" />
                        </svg>
                    @endif
                    <div class="university-name">
                        <p>Southern Leyte State University</p>
                        <p class="campus-name">Bontoc Campus</p>
                    </div>
                </div>
                <h1>Intramural Meet <span>2026</span></h1>
                <p class="system-name"><span></span> Intramurals Management System <span></span></p>
            </header>
            <section class="standings" aria-labelledby="standings-heading">
                <div class="standings-heading">
                    <span class="heading-rule" aria-hidden="true"></span>
                    <h2 id="standings-heading">Current Team Standings</h2>
                    @if ($landingPreview ?? config('landing.preview'))
                        <span class="preview-label">Awaiting results</span>
                    @endif
                    <span class="heading-rule" aria-hidden="true"></span>
                </div>
                <div class="team-banners">
                    @foreach (($landingTeams ?? config('landing.teams')) as $team)
                        <x-landing.team-banner :team="$team" :index="$loop->index" />
                    @endforeach
                </div>
            </section>
            <aside class="utility-rail" aria-label="Event utilities">
                <nav class="landing-actions" aria-label="Event navigation">
                    <a class="landing-button schedule-button" href="{{ route('login') }}#schedule-heading">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4m8-4v4M4 11h16m-11 4h2m2 0h2m-6 3h2"/></svg>
                        <span>Schedule</span>
                    </a>
                </nav>
                <div class="results-control">
                    <button class="landing-button results-toggle" type="button" data-results-toggle aria-label="Partial results information" aria-expanded="false" aria-controls="results-popover">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v1"/></svg>
                    </button>
                    <aside id="results-popover" class="results-popover" data-results-popover role="region" aria-labelledby="results-notice-heading" hidden>
                        <h2 id="results-notice-heading">Partial &amp; Unofficial Results</h2>
                        <p>Scores and standings displayed are partial and may change as additional Intramural Meet 2026 event results are submitted and validated. Final team standings will be determined after all official results have been verified.</p>
                        @if ($landingPreview ?? config('landing.preview'))
                            <p class="preview-disclaimer">Scores start at 0. Rankings will appear when official results are available.</p>
                        @endif
                        @if (! ($landingPreview ?? config('landing.preview')) && ($landingUpdatedAt ?? config('landing.updatedAt')))
                            <p class="update-status"><span aria-hidden="true"></span> Live Standings &bull; Last Updated: <time datetime="{{ \Carbon\Carbon::parse($landingUpdatedAt ?? config('landing.updatedAt'))->toIso8601String() }}">{{ \Carbon\Carbon::parse($landingUpdatedAt ?? config('landing.updatedAt'))->format('M j, Y, g:i A T') }}</time></p>
                        @endif
                    </aside>
                </div>
            </aside>
        </main>
        <x-pwa-install />
    </body>
</html>
