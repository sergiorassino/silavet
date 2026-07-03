<div class="vl-page">
    <div class="vl-hero mb-6">
        <div class="vl-hero-inner">
            <h1 class="text-2xl font-bold text-neutral-800">Panel de laboratorio</h1>
            <p class="mt-1 text-sm text-neutral-600">
                Bienvenido, {{ labCtx()->usuario()?->apenom ?? 'usuario' }}.
            </p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @if (tienePermiso(\App\Support\PermisosIaCatalog::CLIENTES))
            <a href="{{ route('abm.clientes.index') }}" class="vl-dash-access">
                <span class="vl-dash-access-icon">CL</span>
                <span>
                    <strong class="block text-neutral-800">Clientes</strong>
                    <span class="text-sm text-neutral-600">Veterinarias y clínicas</span>
                </span>
            </a>
        @endif
    </div>
</div>
