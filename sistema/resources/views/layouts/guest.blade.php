<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Acceso</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased text-neutral-800">
<div class="min-h-screen flex flex-col md:flex-row">
    <div class="md:hidden h-3 shrink-0 bg-gradient-to-r from-[#2C3333] via-[#2D6A6A] to-[#D4E4E4]" aria-hidden="true"></div>

    <aside class="relative hidden md:flex md:w-[46%] lg:w-[48%] min-h-screen flex-col justify-between px-10 xl:px-14 py-12 text-white overflow-hidden bg-gradient-to-br from-[#2D6A6A] via-[#245656] to-[#2C3333]">
        <div class="relative z-10 max-w-lg">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/65">{{ config('app.name') }}</p>
            <h1 class="mt-3 text-3xl font-bold leading-tight">{{ config('tenant.login.titulo_staff', 'Portal del personal') }}</h1>
            <p class="mt-4 text-base leading-relaxed text-white/80">
                Acceso para personal del laboratorio veterinario.
            </p>
        </div>
    </aside>

    <div class="flex flex-1 flex-col bg-accent-50 md:bg-white">
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-8">
            <div class="w-full max-w-md">{{ $slot }}</div>
        </div>
    </div>
</div>
@livewireScripts
</body>
</html>
