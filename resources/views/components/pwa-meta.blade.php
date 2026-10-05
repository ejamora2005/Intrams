@props([
    'theme' => '#06152c',
])

@php
    $iconVersion = @filemtime(public_path('favicon.ico')) ?: '20261005';
    $favicon = asset('favicon.ico').'?v='.$iconVersion;
    $favicon16 = asset('icons/favicon-16.png').'?v='.$iconVersion;
    $favicon32 = asset('icons/favicon-32.png').'?v='.$iconVersion;
    $appleTouchIcon = asset('icons/pwa-icon-192.png').'?v='.$iconVersion;
    $faviconLarge = asset('icons/pwa-icon-512.png').'?v='.$iconVersion;
@endphp

<meta name="theme-color" content="{{ $theme }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('app.display_name', 'SLSU INTRAMURALS') }}">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" type="image/x-icon" href="{{ $favicon }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ $favicon }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ $favicon16 }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon32 }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ $faviconLarge }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $appleTouchIcon }}">
