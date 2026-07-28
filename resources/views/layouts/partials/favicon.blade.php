{{-- Favicon de solapa + iconos para “Añadir a pantalla de inicio” (Chrome/Android / iOS). --}}
@php
    $vlThemeColor = \App\Support\Entorno\TemaFondoSistema::colorBase();
@endphp
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('img/icon-192.png') }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('img/icon-512.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ route('webmanifest') }}">
<meta name="theme-color" content="{{ $vlThemeColor }}">
<meta name="msapplication-TileColor" content="{{ $vlThemeColor }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'SILAVET') }}">
<meta name="application-name" content="{{ config('app.name', 'SILAVET') }}">
