<a href="{{ route('dashboard') }}"
   class="vl-sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
   title="Panel principal">
    <x-vl-sidebar-icon name="inicio" class="h-5 w-5 shrink-0 opacity-80" />
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Inicio</span>
</a>

@include('layouts.partials.sidebar-grupos-menu')
