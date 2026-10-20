@props(['games' => []])

<div {{ $attributes->class(['programme-matchups']) }} aria-label="Game matchups">
    @foreach ($games as $game)
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
