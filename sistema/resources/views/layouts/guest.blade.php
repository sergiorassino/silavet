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

<div class="flex min-h-screen flex-col md:flex-row">

    <div class="h-3 shrink-0 bg-gradient-to-r from-[#333333] via-[#0EA5E9] to-[#BAE6FD] md:hidden"
         aria-hidden="true"></div>

    <aside class="relative hidden min-h-screen flex-col justify-between overflow-hidden bg-gradient-to-br from-[#0EA5E9] via-[#0284C7] to-[#333333] px-10 py-12 text-white md:flex md:w-[46%] lg:w-[48%] xl:px-14">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_120%_80%_at_90%_0%,rgba(255,255,255,0.08),transparent_50%),radial-gradient(ellipse_90%_55%_at_10%_100%,rgba(51,51,51,0.35),transparent_52%)]"
             aria-hidden="true"></div>

        <div class="relative z-10 flex w-full flex-col gap-8">
            <div class="flex w-full justify-center md:justify-start">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/15 text-2xl font-bold ring-2 ring-white/25">SV</span>
            </div>

            <div class="mx-auto w-full max-w-lg md:mx-0">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/65">{{ config('app.name') }}</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight tracking-tight xl:text-[2rem]">
                    {{ config('tenant.login.titulo_staff', 'Portal del personal') }}
                </h1>
                <p class="mt-4 text-base leading-relaxed text-white/80">
                    Acceso para personal del laboratorio veterinario.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-white/70">
                    Ingrese con su usuario y contraseña para gestionar protocolos, clientes e informes.
                </p>
            </div>
        </div>

        <div class="relative z-10 flex gap-3 border-t border-white/10 pt-10">
            <span class="h-2 max-w-[4.5rem] flex-1 rounded-full bg-[#0EA5E9]" aria-hidden="true"></span>
            <span class="h-2 max-w-[3rem] flex-1 rounded-full bg-[#7DD3FC]" aria-hidden="true"></span>
            <span class="h-2 max-w-[5rem] flex-1 rounded-full bg-[#BAE6FD]/85" aria-hidden="true"></span>
        </div>
    </aside>

    <div class="flex flex-1 flex-col bg-[#F0F9FF] md:bg-white">
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-8 md:py-14">
            <div class="mb-7 flex w-full justify-center md:hidden">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 text-lg font-bold text-primary-700 ring-2 ring-primary-200">SV</span>
            </div>
            <div class="w-full max-w-md">{{ $slot }}</div>
        </div>
    </div>
</div>

@livewireScripts
</body>
</html>
