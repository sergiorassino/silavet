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
@php
    /** En desktop el menú usa rail colapsado salvo el panel de inicio; hover/focus lo expanden (modo dinámico). */
    $isSidebarPeekMode = ! request()->routeIs('dashboard', 'admin.dashboard');
@endphp
<body class="h-full antialiased text-neutral-800">

<div id="vl-shell"
     class="h-full"
     x-data="{
        sidebarOpen: false,
        peekMenuMode: @json($isSidebarPeekMode),
        /** dynamic = rail + expandir con hover. manual = ancho fijo con botón. */
        sidebarControlMode: 'dynamic',
        sidebarCollapsed: false,
        _sidebarNavScrollTop: 0,
        _sidebarPeekTimer: null,
        _sidebarControlStorageKey: 'vlStaffSidebarControl',
        _sidebarManualCollapsedKey: 'vlStaffSidebarCollapsed',
        groups: {
            gestion: {{ request()->routeIs('protocolos.*', 'derivaciones.*') ? 'true' : 'false' }},
            clientes: {{ request()->routeIs('abm.clientes.*', 'clientes.cuenta-corriente*') ? 'true' : 'false' }},
            tesoreria: false,
            gestionStock: false,
            parametrosGenerales: {{ request()->routeIs('admin.parametros-sistema.*') ? 'true' : 'false' }},
            parametrosDeterminaciones: {{ request()->routeIs('admin.determinaciones.*', 'admin.grupos.*', 'admin.det-por-grupo.*', 'admin.items-informe.*', 'admin.automatizacion.*') ? 'true' : 'false' }},
            listadosEstadisticos: false,
            procedimientosTomaMuestras: false,
        },
        isDesktopPeekLayout() {
            return window.matchMedia && window.matchMedia('(min-width: 768px)').matches;
        },
        isSidebarPeekActive() {
            return this.peekMenuMode && this.sidebarControlMode === 'dynamic' && this.isDesktopPeekLayout();
        },
        persistManualCollapsed() {
            if (this.sidebarControlMode !== 'manual') return;
            try {
                localStorage.setItem(this._sidebarManualCollapsedKey, this.sidebarCollapsed ? '1' : '0');
            } catch (e) {}
        },
        applyManualSidebarBootState() {
            if (! this.isDesktopPeekLayout()) {
                this.sidebarCollapsed = false;
                return;
            }
            try {
                const raw = localStorage.getItem(this._sidebarManualCollapsedKey);
                if (raw === '1') this.sidebarCollapsed = true;
                else if (raw === '0') this.sidebarCollapsed = false;
                else this.sidebarCollapsed = false;
            } catch (e) {
                this.sidebarCollapsed = false;
            }
        },
        toggleSidebarControlMode() {
            const next = this.sidebarControlMode === 'dynamic' ? 'manual' : 'dynamic';
            clearTimeout(this._sidebarPeekTimer);
            const el = this.$refs.vlSidebar;
            if (el) el.classList.remove('is-narrowing');
            this.sidebarControlMode = next;
            try {
                localStorage.setItem(this._sidebarControlStorageKey, next);
            } catch (e) {}
            if (next === 'manual') {
                this.persistManualCollapsed();
            } else {
                this.applyPeekSidebarBootState(false);
            }
        },
        toggleSidebarManual() {
            if (this.sidebarControlMode !== 'manual' || ! this.isDesktopPeekLayout()) return;
            this.sidebarCollapsed = ! this.sidebarCollapsed;
            if (! this.sidebarCollapsed) {
                this.restoreSidebarNavScroll();
            } else {
                this.saveSidebarNavScroll();
            }
            this.persistManualCollapsed();
        },
        saveSidebarNavScroll() {
            const nav = this.$refs.vlSidebarNav;
            if (! nav) return;
            this._sidebarNavScrollTop = nav.scrollTop;
            try {
                sessionStorage.setItem('vlSidebarNavScrollTop', String(this._sidebarNavScrollTop));
            } catch (e) {}
        },
        loadSidebarNavScroll() {
            try {
                const raw = sessionStorage.getItem('vlSidebarNavScrollTop');
                if (raw === null || raw === '') return;
                const n = parseInt(raw, 10);
                if (! Number.isNaN(n) && n >= 0) this._sidebarNavScrollTop = n;
            } catch (e) {}
        },
        restoreSidebarNavScroll() {
            const nav = this.$refs.vlSidebarNav;
            if (! nav) return;
            const top = this._sidebarNavScrollTop;
            let tries = 0;
            const apply = () => {
                nav.scrollTop = top;
                if (Math.abs(nav.scrollTop - top) > 2 && tries++ < 20) {
                    requestAnimationFrame(apply);
                }
            };
            this.$nextTick(() => requestAnimationFrame(apply));
        },
        onSidebarNavScroll() {
            if (! this.sidebarCollapsed) this.saveSidebarNavScroll();
        },
        peekSidebarExpandNow() {
            if (! this.isSidebarPeekActive()) return;
            clearTimeout(this._sidebarPeekTimer);
            const el = this.$refs.vlSidebar;
            if (el) el.classList.remove('is-narrowing');
            this.sidebarCollapsed = false;
        },
        peekSidebarMaybeCollapseLater() {
            if (! this.isSidebarPeekActive()) return;
            clearTimeout(this._sidebarPeekTimer);
            const el = this.$refs.vlSidebar;
            if (el) el.classList.add('is-narrowing');
            this._sidebarPeekTimer = window.setTimeout(() => {
                if (! el) return;
                if (el.matches(':hover') || el.contains(document.activeElement)) {
                    el.classList.remove('is-narrowing');
                    return;
                }
                el.classList.remove('is-narrowing');
                this.saveSidebarNavScroll();
                this.sidebarCollapsed = true;
            }, 200);
        },
        peekSidebarFocusOut(ev) {
            if (! this.isSidebarPeekActive()) return;
            const sidebar = this.$refs.vlSidebar;
            const rt = ev.relatedTarget;
            if (sidebar && rt && sidebar.contains(rt)) return;
            this.peekSidebarMaybeCollapseLater();
        },
        applyPeekSidebarBootState(respectInteraction = true) {
            if (this.sidebarControlMode === 'manual') {
                this.applyManualSidebarBootState();
                return;
            }
            if (! this.peekMenuMode || ! this.isDesktopPeekLayout()) {
                this.sidebarCollapsed = false;
                return;
            }
            if (respectInteraction) {
                const el = this.$refs.vlSidebar;
                if (el && (el.matches(':hover') || el.contains(document.activeElement))) return;
            }
            this.sidebarCollapsed = true;
        },
        init() {
            const raw = localStorage.getItem('vlSidebarGroups');
            if (raw) {
                try {
                    const parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        this.groups = { ...this.groups, ...parsed };
                    }
                } catch (e) {}
            }
            this.loadSidebarNavScroll();
            try {
                const mode = localStorage.getItem(this._sidebarControlStorageKey);
                if (mode === 'manual' || mode === 'dynamic') this.sidebarControlMode = mode;
            } catch (e) {}
            this.applyPeekSidebarBootState(false);
            this.$watch('sidebarCollapsed', (collapsed) => {
                if (this.sidebarControlMode === 'manual') {
                    this.persistManualCollapsed();
                }
                if (! collapsed && this.isSidebarPeekActive()) {
                    this.restoreSidebarNavScroll();
                }
            });
            if (! this._vlPeekResizeBound) {
                this._vlPeekResizeBound = true;
                window.addEventListener('resize', () => this.applyPeekSidebarBootState(true));
            }
        },
        toggleGroup(key) {
            this.groups[key] = ! this.groups[key];
            localStorage.setItem('vlSidebarGroups', JSON.stringify(this.groups));
        },
     }">

    <div x-show="sidebarOpen"
         x-transition.opacity
         class="fixed inset-0 z-30 bg-neutral-900/50 md:hidden"
         @click="sidebarOpen = false"
         style="display:none"></div>

    <aside x-ref="vlSidebar"
           class="vl-sidebar vl-sidebar--bosque vl-sidebar--active-typography fixed inset-y-0 left-0 z-[1000] flex flex-col overflow-hidden shadow-lg transition-transform duration-200 ease-in-out md:translate-x-0 md:transition-[width]"
           :class="[
               sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
               sidebarCollapsed ? 'is-collapsed' : ''
           ]"
           @mouseenter="peekSidebarExpandNow()"
           @mouseleave="saveSidebarNavScroll(); peekSidebarMaybeCollapseLater()"
           @focusin="peekSidebarExpandNow()"
           @focusout="peekSidebarFocusOut($event)">

        <div class="vl-sidebar-brand relative z-10 shrink-0"
             :class="sidebarCollapsed ? 'is-collapsed' : ''">
            <a href="{{ $homeRoute }}"
               class="vl-sidebar-brand__link"
               title="{{ config('tenant.nombre') }} — {{ $menuLabel }}">
                <x-vl-lab-logo variant="sidebar" />
                <span x-show="!sidebarCollapsed" x-cloak class="vl-sidebar-brand__copy">
                    <span class="vl-sidebar-brand__name">{{ config('tenant.nombre') }}</span>
                </span>
            </a>
        </div>

        <nav x-ref="vlSidebarNav"
             class="relative z-[1] min-h-0 flex-1 space-y-0.5 overflow-y-auto px-2.5 py-3"
             :class="sidebarCollapsed ? '!px-1 !py-2' : ''"
             @scroll.passive="onSidebarNavScroll()">
            @include($navPartial)
        </nav>

        @include('layouts.partials.staff-sidebar-controls')
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
