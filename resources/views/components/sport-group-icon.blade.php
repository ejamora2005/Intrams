@props(['group' => 'other'])

@if ($group === 'team')
    <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'aria-hidden' => 'true']) }}><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M12 17v4M7 4h10v3a5 5 0 0 1-10 0V4Z"/><path stroke-linecap="round" d="M7 6H4a3 3 0 0 0 3 3M17 6h3a3 3 0 0 1-3 3"/></svg>
@elseif ($group === 'individual')
    <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'aria-hidden' => 'true']) }}><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="m12 7 1.7 3.8L17 12.5l-3.3 1.7L12 18l-1.7-3.8L7 12.5l3.3-1.7L12 7Z"/></svg>
@elseif ($group === 'track-field')
    <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'aria-hidden' => 'true']) }}><circle cx="16.5" cy="4.5" r="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m14 8-3 3 2.5 2.5M11 11l-4 1.5M13.5 13.5l-1 4.5M10 15l-3 4M5 21h14"/></svg>
@else
    <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'aria-hidden' => 'true']) }}><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>
@endif
