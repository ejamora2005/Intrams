@props([
    'theme' => '#06152c',
])

@php
    $favicon = asset('icons/pwa-icon-192.png');
    $faviconLarge = asset('icons/pwa-icon-512.png');
@endphp

<meta name="theme-color" content="{{ $theme }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('app.display_name', 'SLSU INTRAMURALS') }}">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" type="image/png" href="{{ $favicon }}">
<link rel="shortcut icon" type="image/png" href="{{ $favicon }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ $favicon }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ $faviconLarge }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $favicon }}">
