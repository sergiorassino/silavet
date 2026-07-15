<div class="vl-page">
    <div class="vl-hero vl-dash-hero">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">{{ config('tenant.nombre') }}</p>
                <h1 class="font-bold">Autogestión</h1>
                <p class="mt-1.5 max-w-xl text-sm text-white/80">
                    Hola, {{ $nombreUsuario }}.
                    @if ($nombreCliente !== '')
                        Consultá protocolos, lista de precios y estimación de costos de
                        <span class="font-semibold text-white">{{ $nombreCliente }}</span>.
                    @else
                        Consultá tus protocolos, lista de precios y estimación de costos.
                    @endif
                </p>
            </x-vl-hero-heading>
            <a href="{{ route('cliente.pacientes') }}" class="vl-dash-cta">
                <span class="vl-dash-cta-kicker">Acceso principal</span>
                <span class="vl-dash-cta-title">Pacientes</span>
                <span class="vl-dash-cta-desc">Protocolos e informes de tu clínica</span>
            </a>
        </div>
    </div>

    <div class="vl-dash-grid">
        <a href="{{ route('cliente.pacientes') }}"
           class="vl-card vl-dash-metric block transition hover:ring-2 hover:ring-primary-200">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Consulta</p>
                    <h2 class="vl-dash-metric-title">Pacientes</h2>
                </div>
                <x-vl-sidebar-icon name="pacientes" class="h-7 w-7 text-primary-600 opacity-80" />
            </div>
            <p class="mt-2 text-sm text-neutral-600">Protocolos e informes PDF de tu cliente.</p>
        </a>

        <a href="{{ route('cliente.lista-precios') }}"
           class="vl-card vl-dash-metric block transition hover:ring-2 hover:ring-primary-200">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Tarifario</p>
                    <h2 class="vl-dash-metric-title">Lista de precios</h2>
                </div>
                <x-vl-sidebar-icon name="lista-precios" class="h-7 w-7 text-primary-600 opacity-80" />
            </div>
            <p class="mt-2 text-sm text-neutral-600">PDF de precios vigente del laboratorio.</p>
        </a>

        <a href="{{ route('cliente.estimacion-costos') }}"
           class="vl-card vl-dash-metric block transition hover:ring-2 hover:ring-primary-200">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Presupuesto</p>
                    <h2 class="vl-dash-metric-title">Estimación de costos</h2>
                </div>
                <x-vl-sidebar-icon name="estimacion-costos" class="h-7 w-7 text-primary-600 opacity-80" />
            </div>
            <p class="mt-2 text-sm text-neutral-600">Armá una estimación con tus precios y descuentos.</p>
        </a>
    </div>
</div>
