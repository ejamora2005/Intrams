@props([
    'theme' => '#06152c',
])

<meta name="theme-color" content="{{ $theme }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="INTRAMURAL MS">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/pwa-icon-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/pwa-icon-192.png') }}">
