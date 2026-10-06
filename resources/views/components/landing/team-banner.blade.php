@props(['team', 'index' => 0])

<article class="team-banner team-banner--{{ $team['theme'] }}" aria-labelledby="team-name-{{ $index }}" style="--team-primary: {{ $team['primaryColor'] }}; --team-secondary: {{ $team['secondaryColor'] }}; --wind-duration: {{ 8.4 + $index * 1.7 }}s; --wind-delay: -{{ 2 + $index * 3.1 }}s;">
    <div class="banner-rod" aria-hidden="true"><span></span></div>
    <div class="banner-shadow">
        <div class="banner-cloth">
            <div class="fabric-weave" aria-hidden="true"></div>
            <div class="fabric-light" aria-hidden="true"></div>
            <div class="banner-stitching" aria-hidden="true"></div>
            <div class="banner-content">
                <div class="banner-topline" aria-hidden="true"><span></span> SLSU &bull; BONTOC <span></span></div>
                <header class="team-heading">
                    <h3 id="team-name-{{ $index }}">{{ $team['name'] }}</h3>
                    <p>{{ $team['department'] }}</p>
                </header>
                <div class="team-image-area">
                    <x-landing.department-objects :objects="$team['objects'] ?? []" />
                    @if ($team['image'])
                        <img class="team-image" src="{{ asset($team['image']) }}" alt="{{ $team['name'] }} team logo" decoding="async">
                    @else
                        <div class="team-logo-placeholder" role="img" aria-label="{{ $team['name'] }} team logo placeholder">
                            <span class="placeholder-corner corner-tl" aria-hidden="true"></span>
                            <span class="placeholder-corner corner-tr" aria-hidden="true"></span>
                            <span class="placeholder-label">Team Logo</span>
                            <span class="placeholder-corner corner-bl" aria-hidden="true"></span>
                            <span class="placeholder-corner corner-br" aria-hidden="true"></span>
                        </div>
                    @endif
                </div>
                <div class="team-score" aria-label="{{ $team['score'] ?? 0 }} points, {{ isset($team['rank']) ? 'rank '.$team['rank'] : 'not yet ranked' }}">
                    <span class="score-rule" aria-hidden="true"></span>
                    <strong>{{ $team['score'] ?? 0 }}</strong>
                    <span class="score-unit">PTS</span>
                    @if (isset($team['rank']) && $team['rank'] !== null)
                        <span class="rank-label"><x-landing.ranking-medal :rank="$team['rank']" /></span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</article>
