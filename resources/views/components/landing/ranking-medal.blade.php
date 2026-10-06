@props(['rank'])

@php
    $rank = (int) $rank;
    $medal = match ($rank) {
        1 => ['symbol' => '🥇', 'label' => 'Gold medal, first place'],
        2 => ['symbol' => '🥈', 'label' => 'Silver medal, second place'],
        3 => ['symbol' => '🥉', 'label' => 'Bronze medal, third place'],
        default => ['symbol' => '🏅', 'label' => 'Rank '.$rank],
    };
@endphp

<span class="ranking-medal" role="img" aria-label="{{ $medal['label'] }}" title="{{ $medal['label'] }}">{{ $medal['symbol'] }}</span>
