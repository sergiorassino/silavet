<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    @include('layouts.partials.meta-viewport')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Laboratorio</title>
    @include('layouts.partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-accent-50 antialiased text-neutral-800">
<div class="min-h-screen flex">
    @include('layouts.partials.sidebar-laboratorio')

    <div class="flex min-h-screen flex-1 flex-col">
        @include('layouts.partials.topbar')

        <main class="flex-1 p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>
</div>
@include('layouts.partials.livewire-scripts')
</body>
</html>
