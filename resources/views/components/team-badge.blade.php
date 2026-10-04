@props([
    'team' => null,
    'name' => null,
    'subtitle' => null,
    'size' => 'sm',
    'logoOnly' => false,
])

@php
    $teamName = $name;
    $teamSubtitle = $subtitle;
    $logoPath = null;

    if (is_array($team)) {
        $teamName ??= $team['name'] ?? null;
        $teamSubtitle ??= $team['department'] ?? $team['code'] ?? null;
        $logoPath = $team['logo_path'] ?? $team['image'] ?? null;
    } elseif ($team instanceof \App\Models\Team) {
        $teamName ??= $team->name;
        $logoPath = $team->logo_path;
    } elseif (is_object($team)) {
        $teamName ??= $team->name ?? $team->label ?? null;
        $logoPath = $team->logo_path ?? null;
    } elseif (is_string($team)) {
        $teamName ??= $team;
    }

    $logoPath ??= \App\Models\Team::logoPathFor($teamName);

    $logoClasses = match ($size) {
        'lg' => 'h-12 w-12',
        'md' => 'h-10 w-10',
        'xs' => 'h-6 w-6',
        default => 'h-8 w-8',
    };
@endphp

<span {{ $attributes->class(['inline-flex min-w-0 max-w-full items-center gap-2 align-middle']) }}>
    @if ($logoPath)
        <img src="{{ asset($logoPath) }}" alt="" width="48" height="48" decoding="async" class="{{ $logoClasses }} shrink-0 object-contain drop-shadow-sm">
    @endif

    @unless ($logoOnly)
        <span class="min-w-0">
            <span class="block truncate leading-tight">{{ $teamName ?? 'All active factions' }}</span>
            @if ($teamSubtitle)
                <span class="mt-0.5 block truncate text-xs font-medium opacity-75">{{ $teamSubtitle }}</span>
            @endif
        </span>
    @endunless
</span>
