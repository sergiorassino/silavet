<!DOCTYPE html>
<html lang="es" class="h-full" style="{{ \App\Support\Entorno\TemaFondoSistema::variablesCss() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Acceso</title>
    <script>
        (function () {
            function vlAuthResolveScale() {
                var sw = screen.availWidth || screen.width || 0;
                var sh = screen.availHeight || screen.height || 0;
                var vh = window.innerHeight || 0;
                var vw = window.innerWidth || 0;
                var dpr = window.devicePixelRatio || 1;
                var area = sw * sh;
                var scale = 'md';

                /*
                 * compact: pantallas/viewports bajos (típico 17" o laptop con zoom OS).
                 * roomy: monitores con más área útil (21"+ / FHD sin escalado / QHD).
                 * No se puede leer pulgadas físicas; usamos área, DPR y viewport.
                 */
                var looksLaptopScaled = dpr >= 1.25 && sh <= 1080 && sw <= 1920;
                var looksDesktopFhd = dpr <= 1.1 && sw >= 1800 && sh >= 1000;
                var looksLarge = area >= (1920 * 1040) || sh >= 1100 || sw >= 2400;

                if (looksLaptopScaled || (vh > 0 && vh <= 920 && !looksDesktopFhd && !looksLarge)) {
                    scale = 'compact';
                } else if (looksLarge || looksDesktopFhd || (vh >= 960 && vw >= 1280)) {
                    scale = 'roomy';
                }

                document.documentElement.setAttribute('data-vl-auth-scale', scale);
            }

            vlAuthResolveScale();
            window.addEventListener('resize', vlAuthResolveScale);
            window.addEventListener('orientationchange', vlAuthResolveScale);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased text-neutral-800">

<div class="vl-auth-page flex flex-col md:flex-row">

    <div class="vl-auth-mobile-bar h-3 shrink-0 md:hidden"
         aria-hidden="true"></div>

    <aside class="vl-auth-brand relative hidden min-h-dvh flex-col overflow-hidden text-white md:flex md:w-[46%] lg:w-[48%]">
        <div class="vl-auth-brand__glow pointer-events-none absolute inset-0"
             aria-hidden="true"></div>

        <div class="vl-auth-brand__body relative z-10 flex flex-1 flex-col justify-center">
            <div class="vl-auth-brand__copy mx-auto w-full text-center md:text-left">
                <p class="vl-auth-brand__kicker font-semibold uppercase text-white/65">{{ config('app.name') }}</p>
                <h1 class="vl-auth-brand__title font-bold leading-tight tracking-tight">
                    {{ config('tenant.login.titulo_staff', 'Portal del personal') }}
                </h1>
                <p class="vl-auth-brand__lead leading-relaxed text-white/80">
                    Acceso para personal del laboratorio veterinario.
                </p>
                <p class="vl-auth-brand__note leading-relaxed text-white/70">
                    Ingrese con su usuario y contraseña para gestionar protocolos, clientes e informes.
                </p>
            </div>
        </div>

        <div class="vl-auth-brand__footer relative z-10 flex gap-3 border-t border-white/10">
            <span class="vl-auth-brand__bar vl-auth-brand__bar--1 h-2 max-w-[4.5rem] flex-1 rounded-full" aria-hidden="true"></span>
            <span class="vl-auth-brand__bar vl-auth-brand__bar--2 h-2 max-w-[3rem] flex-1 rounded-full" aria-hidden="true"></span>
            <span class="vl-auth-brand__bar vl-auth-brand__bar--3 h-2 max-w-[5rem] flex-1 rounded-full" aria-hidden="true"></span>
        </div>
    </aside>

    <div class="vl-auth-page__panel flex min-h-0 flex-1 flex-col">
        <div class="vl-auth-page__panel-inner flex min-h-0 flex-1 flex-col items-center justify-center">
            <div class="vl-auth-page__stack flex w-full flex-col items-center">
                <x-vl-lab-logo variant="login" />
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

@livewireScripts
</body>
</html>
