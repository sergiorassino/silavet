<div class="vl-page">
    <div class="vl-hero">
        <div class="vl-hero-inner">
            <div>
                <p class="vl-eyebrow">{{ config('tenant.nombre') }}</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Panel de laboratorio</h1>
                <p class="mt-2 max-w-xl text-sm text-white/80 sm:text-base">
                    Bienvenido, {{ labCtx()->usuario()?->apenom ?? 'usuario' }}.
                </p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('protocolos.index') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">PA</span>
            <span>
                <strong class="block text-neutral-800">Pacientes</strong>
                <span class="text-sm text-neutral-600">Protocolos analíticos</span>
            </span>
        </a>
        <a href="{{ route('abm.clientes.index') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">CL</span>
            <span>
                <strong class="block text-neutral-800">Clientes</strong>
                <span class="text-sm text-neutral-600">Veterinarias y clínicas</span>
            </span>
        </a>
        <a href="{{ route('admin.determinaciones.index') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">DT</span>
            <span>
                <strong class="block text-neutral-800">Gestión Determinaciones</strong>
                <span class="text-sm text-neutral-600">Tipos de análisis y listas de precios</span>
            </span>
        </a>
    </div>
</div>
