<!DOCTYPE html>
<html lang="es" class="h-full bg-[#F0F9FF]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} — {{ $menuLabel }}</title>
    @include('layouts.partials.sidebar-bosque-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased text-neutral-800">

<div id="vl-shell"
     class="h-full"
     x-data="{
        sidebarOpen: false,
        sidebarCollapsed: false,
        groups: {
            gestion: {{ request()->routeIs('protocolos.*') ? 'true' : 'false' }},
            clientes: {{ request()->routeIs('abm.clientes.*', 'clientes.cuenta-corriente*') ? 'true' : 'false' }},
            tesoreria: false,
            gestionStock: false,
            parametrosGenerales: {{ request()->routeIs('admin.parametros-sistema.*') ? 'true' : 'false' }},
            parametrosDeterminaciones: {{ request()->routeIs('admin.determinaciones.*', 'admin.grupos.*', 'admin.det-por-grupo.*', 'admin.items-informe.*', 'admin.automatizacion.*') ? 'true' : 'false' }},
            listadosEstadisticos: false,
            procedimientosTomaMuestras: false,
        },
        init() {
            if (window.matchMedia('(min-width: 768px)').matches) {
                this.sidebarCollapsed = {{ ($collapsedSidebar ?? true) ? 'true' : 'false' }};
            }
            const raw = localStorage.getItem('vlSidebarGroups');
            if (raw) {
                try {
                    const parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        this.groups = { ...this.groups, ...parsed };
                    }
                } catch (e) {}
            }
        },
        toggleGroup(key) {
            this.groups[key] = !this.groups[key];
            localStorage.setItem('vlSidebarGroups', JSON.stringify(this.groups));
        },
     }">

    <div x-show="sidebarOpen"
         x-transition.opacity
         class="fixed inset-0 z-30 bg-neutral-900/50 md:hidden"
         @click="sidebarOpen = false"
         style="display:none"></div>

    <aside class="vl-sidebar vl-sidebar--bosque vl-sidebar--active-typography fixed inset-y-0 left-0 z-[1000] flex flex-col overflow-hidden shadow-lg transition-transform duration-200 ease-in-out md:translate-x-0 md:transition-[width]"
           :class="[
               sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
               sidebarCollapsed ? 'is-collapsed' : ''
           ]"
           @mouseenter="if (window.matchMedia('(min-width: 768px)').matches) sidebarCollapsed = false"
           @mouseleave="if (window.matchMedia('(min-width: 768px)').matches) sidebarCollapsed = true">

        <div class="relative z-10 flex shrink-0 flex-col gap-2 overflow-hidden border-b vl-sidebar-sep px-3 py-3"
             :class="sidebarCollapsed ? 'items-center px-1.5' : ''">
            <a href="{{ $homeRoute }}"
               class="flex min-w-0 items-center gap-2 rounded-lg no-underline transition-colors hover:bg-[var(--vl-hover-bg)]"
               :class="sidebarCollapsed ? 'justify-center' : ''">
                <x-vl-lab-logo
                    size="md"
                    monogram-class="bg-white/12 text-white ring-1 ring-[rgba(186,230,253,0.35)] backdrop-blur-sm"
                />
                <div x-show="!sidebarCollapsed" x-cloak class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[rgba(224,247,255,0.72)]">{{ $menuLabel }}</p>
                    <p class="truncate text-sm font-bold text-white">{{ config('tenant.nombre') }}</p>
                </div>
            </a>
        </div>

        <nav class="relative z-[1] min-h-0 flex-1 space-y-0.5 overflow-y-auto px-2.5 py-3"
             :class="sidebarCollapsed ? '!px-1 !py-2' : ''">
            @include($navPartial)
        </nav>

        @include('layouts.partials.staff-sidebar-footer')
    </aside>

    <div class="vl-main flex min-h-screen flex-col"
         :class="[
             sidebarCollapsed ? 'is-collapsed' : '',
             sidebarOpen ? 'is-mobile-open' : ''
         ]">

        <header class="sticky top-0 z-20 border-b border-accent-200 bg-white/95 backdrop-blur-sm supports-[backdrop-filter]:bg-white/85 md:hidden">
            <div class="flex h-14 items-center gap-3 px-4">
                <button type="button"
                        @click="sidebarOpen = true"
                        class="text-neutral-500 hover:text-neutral-700 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <x-vl-lab-logo size="sm" class="md:hidden" monogram-class="bg-primary-100 text-primary-700 ring-1 ring-primary-200" />
                <span class="text-sm font-semibold text-neutral-800">{{ $menuLabel }}</span>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-8">
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
