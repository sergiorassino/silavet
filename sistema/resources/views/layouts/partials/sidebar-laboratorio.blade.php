<aside class="hidden w-64 shrink-0 border-r border-accent-200 bg-neutral-800 text-white lg:block">
    <div class="px-5 py-6 border-b border-white/10">
        <p class="text-xs uppercase tracking-widest text-white/60">Menú de Laboratorio</p>
        <p class="mt-1 font-bold">{{ config('tenant.nombre') }}</p>
    </div>
    <nav class="p-4 space-y-1 text-sm">
        <a href="{{ route('dashboard') }}"
           class="block rounded-xl px-3 py-2 hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/15 font-semibold' : '' }}"
           title="Panel principal (v1.0)">
            Inicio
        </a>
        @if (tienePermiso(\App\Support\PermisosIaCatalog::CLIENTES))
            <a href="{{ route('abm.clientes.index') }}"
               class="block rounded-xl px-3 py-2 hover:bg-white/10 {{ request()->routeIs('abm.clientes.*') ? 'bg-white/15 font-semibold' : '' }}"
               title="ABM Clientes (v1.0)">
                Clientes
            </a>
        @endif
    </nav>
</aside>
