@props(['games' => [], 'manage' => false])

<div {{ $attributes->class(['programme-matchups']) }} aria-label="Game matchups">
    @foreach ($games as $game)
        @if (! empty($game['bracket_label']))
            <div class="programme-game-link">
                <span>{{ $game['bracket_label'] }}</span>
                @if ($manage)<a href="{{ $game['bracket_url'] }}">Open bracket</a> <a href="{{ $game['schedule_url'] }}">Edit match schedule</a>@endif
                @if ($game['scheduled_time'])<span>{{ $game['scheduled_time'] }} · {{ $game['scheduled_venue'] }}</span>@endif
                @if (!empty($game['schedule_status']))<span>{{ ucfirst($game['schedule_status']) }}</span>@endif
                @if ($game['winner'])<strong>Winner: {{ $game['winner'] }}</strong>@endif
            </div>
        @endif
        <div class="programme-matchup" data-programme-game="{{ $game['number'] }}">
            <span class="programme-game-number">G{{ $game['number'] }}</span>
            @foreach (['home', 'away'] as $side)
                @if ($side === 'away')<span class="programme-game-vs" aria-hidden="true">vs</span>@endif
                <span class="programme-game-team" title="{{ $game[$side]['name'] }}">
                    @if ($game[$side]['logo_path'])
                        <img src="{{ asset($game[$side]['logo_path']) }}" alt="" width="32" height="32" loading="lazy" decoding="async">
                    @else
                        <span class="programme-game-placeholder" aria-hidden="true">{{ $game[$side]['short'] }}</span>
                    @endif
                    <span>{{ $game[$side]['name'] }}</span>
                </span>
            @endforeach
        </div>
    @endforeach
</div>
