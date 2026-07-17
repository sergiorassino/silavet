@php
    $hoy = $metricas['hoy'];
    $estados = $hoy['porEstado'];
    $semana = $metricas['semana'];
    $cc = $metricas['cuentaCorriente'];
@endphp

<div class="vl-page">
    <div class="vl-hero vl-dash-hero">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">{{ config('tenant.nombre') }}</p>
                <h1 class="font-bold">Autogestión</h1>
                <p class="mt-1.5 max-w-xl text-sm text-white/80">
                    Hola, {{ $nombreUsuario }}.
                    @if ($nombreCliente !== '')
                        Resumen operativo de
                        <span class="font-semibold text-white">{{ $nombreCliente }}</span>
                        · {{ $metricas['fechaFormato'] }}.
                    @else
                        Resumen de tus protocolos · {{ $metricas['fechaFormato'] }}.
                    @endif
                </p>
            </x-vl-hero-heading>
            <div class="vl-dash-hero-actions">
                <x-vl-cli-avisos-campana />
                <a href="{{ route('cliente.pacientes') }}" class="vl-dash-cta">
                    <span class="vl-dash-cta-kicker">Acceso principal</span>
                    <span class="vl-dash-cta-title">Pacientes</span>
                    <span class="vl-dash-cta-desc">Protocolos e informes de tu clínica</span>
                </a>
            </div>
        </div>
    </div>

    <div class="vl-dash-grid">
        {{-- 1. Estado de mis casos --}}
        <section class="vl-card vl-dash-metric vl-dash-metric--wide" aria-labelledby="cli-dash-hoy-title">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Hoy</p>
                    <h2 id="cli-dash-hoy-title" class="vl-dash-metric-title">Estado de mis casos</h2>
                </div>
            </div>

            <div class="vl-dash-hoy">
                <div class="vl-dash-donut-wrap" role="img" aria-label="Distribución por estado de hoy">
                    <div class="vl-dash-donut" style="background: {{ $hoy['conic'] }}">
                        <div class="vl-dash-donut-hole">
                            <span class="vl-dash-donut-num">{{ $hoy['total'] }}</span>
                            <span class="vl-dash-donut-cap">hoy</span>
                        </div>
                    </div>
                </div>

                <ul class="vl-dash-estado-list">
                    @foreach ($estados as $estado)
                        <li class="vl-dash-estado-item">
                            <span class="vl-dash-estado-swatch" style="background: {{ $estado['color'] }}"></span>
                            <span class="vl-dash-estado-name">{{ $estado['etiqueta'] }}</span>
                            <span class="vl-dash-estado-bar" aria-hidden="true">
                                <span class="vl-dash-estado-bar-fill" style="width: {{ $estado['porcentaje'] }}%; background: {{ $estado['color'] }}"></span>
                            </span>
                            <span class="vl-dash-estado-stats">
                                <strong>{{ $estado['cantidad'] }}</strong>
                                <span>{{ number_format($estado['porcentaje'], 1, ',', '') }}%</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="vl-dash-cli-atajos mt-4">
                <a href="{{ route('cliente.pacientes', ['vista' => 'historial', 'filtroEstado' => 'pendientes']) }}"
                   class="vl-dash-cli-atajo">
                    <span class="vl-dash-cli-atajo-num">{{ $metricas['pendientesResultado'] }}</span>
                    <span class="vl-dash-cli-atajo-label">Pendientes de resultado</span>
                </a>
                <a href="{{ route('cliente.pacientes', ['vista' => 'hoy', 'filtroEstado' => 'listos']) }}"
                   class="vl-dash-cli-atajo">
                    <span class="vl-dash-cli-atajo-num">{{ $metricas['informesListos'] }}</span>
                    <span class="vl-dash-cli-atajo-label">Informes listos hoy</span>
                </a>
                <div class="vl-dash-cli-atajo vl-dash-cli-atajo--static" title="{{ $semana['desde'] }} – {{ $semana['hasta'] }}">
                    <span class="vl-dash-cli-atajo-num">{{ $semana['total'] }}</span>
                    <span class="vl-dash-cli-atajo-label">Esta semana</span>
                </div>
            </div>
        </section>

        {{-- 3. Avisos del laboratorio --}}
        <section class="vl-card vl-dash-metric" aria-labelledby="cli-dash-avisos-title">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Laboratorio</p>
                    <h2 id="cli-dash-avisos-title" class="vl-dash-metric-title">Avisos</h2>
                </div>
                @if ($metricas['avisosNoLeidos'] > 0)
                    <span class="vl-dash-cli-badge" aria-label="{{ $metricas['avisosNoLeidos'] }} sin leer">
                        {{ $metricas['avisosNoLeidos'] }}
                    </span>
                @endif
            </div>

            @if ($metricas['avisos'] === [])
                <p class="vl-dash-metric-hint mt-3">No tenés avisos pendientes.</p>
            @else
                <ul class="vl-dash-cli-feed mt-3">
                    @foreach ($metricas['avisos'] as $aviso)
                        <li class="vl-dash-cli-feed-item" wire:key="aviso-{{ $aviso['id'] }}">
                            <div class="vl-dash-cli-feed-main">
                                <p class="vl-dash-cli-feed-title">
                                    {{ $aviso['protocolo'] }}
                                    <span class="text-neutral-400">·</span>
                                    {{ $aviso['nombre'] }}
                                </p>
                                <p class="vl-dash-cli-feed-text">{{ $aviso['texto'] }}</p>
                                <p class="vl-dash-cli-feed-meta">{{ $aviso['fecha'] }}</p>
                            </div>
                            <div class="vl-dash-cli-feed-actions">
                                @if ($aviso['urlInforme'])
                                    <a href="{{ $aviso['urlInforme'] }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="vl-dash-cli-link-sm">PDF</a>
                                @endif
                                <button type="button"
                                        wire:click="marcarAvisoLeido({{ $aviso['id'] }})"
                                        class="vl-dash-cli-link-sm">
                                    Leído
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($metricas['avisosNoLeidos'] > 1)
                    <button type="button"
                            wire:click="marcarTodosAvisosLeidos"
                            class="vl-dash-metric-link">
                        Marcar todos como leídos
                    </button>
                @endif
            @endif
            <div class="vl-dash-metric-accent vl-dash-metric-accent--amber" aria-hidden="true"></div>
        </section>

        {{-- 4. Cuenta corriente --}}
        <section class="vl-card vl-dash-metric" aria-labelledby="cli-dash-cc-title">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Cuenta corriente</p>
                    <h2 id="cli-dash-cc-title" class="vl-dash-metric-title">Saldo actual</h2>
                </div>
            </div>
            <p class="vl-dash-kpi">$ {{ $cc['saldoFormateado'] }}</p>
            <p class="vl-dash-metric-hint">Resumen de tu cuenta con el laboratorio.</p>

            @if ($cc['pendientes'] !== [])
                <ul class="vl-dash-cli-feed mt-3">
                    @foreach ($cc['pendientes'] as $mov)
                        <li class="vl-dash-cli-feed-item" wire:key="cc-{{ $mov['idPacientes'] }}">
                            <div class="vl-dash-cli-feed-main">
                                <p class="vl-dash-cli-feed-title">
                                    {{ $mov['protocolo'] }}
                                    <span class="text-neutral-400">·</span>
                                    {{ $mov['nombre'] }}
                                </p>
                                <p class="vl-dash-cli-feed-meta">{{ $mov['fecha'] }}</p>
                            </div>
                            <span class="vl-dash-cli-feed-amount">$ {{ $mov['saldoPendienteFormateado'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="vl-dash-metric-hint mt-2">Sin protocolos con saldo pendiente reciente.</p>
            @endif

            <a href="{{ route('cliente.pacientes', ['vista' => 'historial']) }}" class="vl-dash-metric-link">
                Ver pacientes
            </a>
            <div class="vl-dash-metric-accent vl-dash-metric-accent--sky" aria-hidden="true"></div>
        </section>

        {{-- 2. Últimos informes --}}
        <section class="vl-card vl-dash-metric vl-dash-metric--wide" aria-labelledby="cli-dash-inf-title">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Informes</p>
                    <h2 id="cli-dash-inf-title" class="vl-dash-metric-title">Últimos disponibles</h2>
                </div>
                <a href="{{ route('cliente.pacientes', ['vista' => 'historial', 'filtroEstado' => 'listos']) }}"
                   class="vl-dash-metric-link !mt-0">
                    Ver todos
                </a>
            </div>

            @if ($metricas['ultimosInformes'] === [])
                <p class="vl-dash-metric-hint mt-3">Todavía no hay informes finalizados.</p>
            @else
                <ul class="vl-dash-cli-feed vl-dash-cli-feed--table mt-3">
                    @foreach ($metricas['ultimosInformes'] as $inf)
                        <li class="vl-dash-cli-feed-item" wire:key="inf-{{ $inf['idPacientes'] }}">
                            <div class="vl-dash-cli-feed-main">
                                <p class="vl-dash-cli-feed-title">
                                    @if ($inf['esNuevo'])
                                        <span class="vl-dash-cli-nuevo" title="Nuevo o con aviso pendiente">Nuevo</span>
                                    @endif
                                    {{ $inf['protocolo'] }}
                                    <span class="text-neutral-400">·</span>
                                    {{ $inf['nombre'] }}
                                </p>
                                <p class="vl-dash-cli-feed-meta">
                                    {{ $inf['fecha'] }} · {{ $inf['tutor'] }} · {{ $inf['estado'] }}
                                </p>
                            </div>
                            @if ($inf['urlInforme'])
                                <a href="{{ $inf['urlInforme'] }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="btn-secondary btn-sm whitespace-nowrap">
                                    PDF
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- 5. Actividad reciente --}}
        <section class="vl-card vl-dash-metric vl-dash-metric--wide" aria-labelledby="cli-dash-act-title">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Actividad</p>
                    <h2 id="cli-dash-act-title" class="vl-dash-metric-title">Reciente</h2>
                </div>
                <a href="{{ route('cliente.pacientes', ['vista' => 'hoy']) }}" class="vl-dash-metric-link !mt-0">
                    Pacientes del día
                </a>
            </div>

            @if ($metricas['actividadReciente'] === [])
                <p class="vl-dash-metric-hint mt-3">No hay protocolos recientes.</p>
            @else
                <div class="vl-dash-cli-table-wrap mt-3">
                    <table class="vl-dash-cli-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Protocolo</th>
                                <th>Paciente</th>
                                <th>Tutor</th>
                                <th>Estado</th>
                                <th class="text-right">Precio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($metricas['actividadReciente'] as $act)
                                <tr wire:key="act-{{ $act['idPacientes'] }}">
                                    <td class="whitespace-nowrap">{{ $act['fecha'] }}</td>
                                    <td class="font-semibold whitespace-nowrap">{{ $act['protocolo'] }}</td>
                                    <td>{{ $act['nombre'] }}</td>
                                    <td>{{ $act['tutor'] }}</td>
                                    <td class="whitespace-nowrap">{{ $act['estado'] }}</td>
                                    <td class="text-right tabular-nums whitespace-nowrap">$ {{ $act['precioFormateado'] }}</td>
                                    <td class="text-right">
                                        @if ($act['urlInforme'])
                                            <a href="{{ $act['urlInforme'] }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="vl-dash-cli-link-sm">PDF</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Accesos rápidos --}}
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
