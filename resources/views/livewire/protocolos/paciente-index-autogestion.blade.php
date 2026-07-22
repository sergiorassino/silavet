<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Autogestión</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Pacientes</h1>
                <p class="mt-2 text-sm text-white/80">
                    @if ($vista === 'hoy')
                        @php
                            $fechaEfectiva = $this->fechaVistaEfectiva();
                            $esHoy = $fechaEfectiva === now()->toDateString();
                        @endphp
                        Protocolos con fecha
                        {{ $esHoy ? 'de hoy' : 'del día' }}
                        ({{ \Illuminate\Support\Carbon::parse($fechaEfectiva)->format('d/m/Y') }}).
                    @else
                        Historial de protocolos de tu clínica.
                    @endif
                </p>
            </x-vl-hero-heading>
            <x-vl-cli-avisos-campana />
        </div>
    </div>

    @if ($encabezadoDescuento)
        <div class="mb-4 px-2 text-center text-sm leading-relaxed text-primary-800 sm:text-base">
            <p class="font-bold">
                Saldo Cuenta Corriente: $ {{ $encabezadoDescuento['saldoFormateado'] }}
            </p>
            @if (! empty($encabezadoDescuento['mostrarDetalleVolumen']))
                <p class="mt-1">
                    Descuentos obtenidos durante el mes: $ {{ $encabezadoDescuento['descuentosMesFormateado'] }}
                </p>
                <p class="mt-1">
                    Cantidad de Perfiles solicitados el mes anterior:
                    {{ $encabezadoDescuento['perfilesMesAnterior'] }}
                    (Desc. para este mes: {{ $encabezadoDescuento['porcentajeEsteMesFormateado'] }})
                </p>
                <p class="mt-1">
                    Cantidad de Perfiles solicitados este mes:
                    {{ $encabezadoDescuento['perfilesMesActual'] }}
                </p>
                <p class="mt-1">
                    {{ $encabezadoDescuento['mensajeProximoUmbral'] }}
                </p>
            @endif
        </div>
    @endif

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por protocolo, paciente o tutor…"
                   class="form-input max-w-xl w-full sm:flex-1">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 shrink-0">
                @if ($this->filtroEstadoEfectivo() !== '')
                    <div class="inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-800">
                        <span>{{ $this->etiquetaFiltroEstado() }}</span>
                        <button type="button"
                                wire:click="limpiarFiltroEstado"
                                class="text-primary-600 hover:text-primary-800"
                                title="Quitar filtro"
                                aria-label="Quitar filtro de estado">
                            ×
                        </button>
                    </div>
                @endif
                @if ($vista === 'hoy')
                    <div class="vl-pacientes-fecha-filtro inline-flex items-center gap-2 text-xs font-semibold text-neutral-600">
                        <label for="fechaVista" class="whitespace-nowrap">Día</label>
                        <input wire:model.live="fechaVista"
                               id="fechaVista"
                               type="date"
                               class="form-input py-1.5 text-xs w-auto"
                               aria-label="Filtrar pacientes por día">
                        @if ($this->fechaVistaEfectiva() !== now()->toDateString())
                            <button type="button"
                                    wire:click="verPacientesDeHoy"
                                    class="btn-secondary btn-sm whitespace-nowrap">
                                Hoy
                            </button>
                        @endif
                    </div>
                @endif
                <div class="vl-pacientes-vista-toggle" role="group" aria-label="Vista del listado">
                    <button type="button"
                            wire:click="$set('vista', 'hoy')"
                            class="vl-pacientes-vista-toggle-btn {{ $vista === 'hoy' ? 'is-active' : '' }}"
                            aria-pressed="{{ $vista === 'hoy' ? 'true' : 'false' }}">
                        Por día
                    </button>
                    <button type="button"
                            wire:click="$set('vista', 'historial')"
                            class="vl-pacientes-vista-toggle-btn {{ $vista === 'historial' ? 'is-active' : '' }}"
                            aria-pressed="{{ $vista === 'historial' ? 'true' : 'false' }}">
                        Historial
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="vl-pacientes-grid min-w-full text-xs">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Informe PDF">INFOR.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">#</th>
                        <th class="vl-pacientes-th">Fechhoy</th>
                        <th class="vl-pacientes-th">Protocolo</th>
                        <th class="vl-pacientes-th">Nombre</th>
                        <th class="vl-pacientes-th">Tutor</th>
                        <th class="vl-pacientes-th">Especie</th>
                        <th class="vl-pacientes-th">Raza</th>
                        <th class="vl-pacientes-th">Sexo</th>
                        <th class="vl-pacientes-th">Edad</th>
                        <th class="vl-pacientes-th vl-pacientes-th--estado" title="Estado">ESTADO</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num" title="Precio de lista">Precio Lista</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num" title="Descuento">Desc.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num" title="Precio con descuento">Precio c/desc</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Pagado</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num" title="Saldo acumulado del cliente tras este movimiento">Saldo</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Asistente IA">IA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pacientes as $paciente)
                        @php
                            $saldoFila = $saldosAcumulados[(int) $paciente->idPacientes] ?? 0.0;
                        @endphp
                        @if ($paciente->esPagoGlobal())
                            <tr class="vl-pacientes-row {{ $paciente->filaClaseCss() }}" wire:key="pac-cli-{{ $paciente->idPacientes }}">
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--num">
                                    {{ ($pacientes->currentPage() - 1) * $pacientes->perPage() + $loop->iteration }}
                                </td>
                                <td class="vl-pacientes-td whitespace-nowrap">{{ $paciente->fechhoyFormateada() }}</td>
                                <td class="vl-pacientes-td font-semibold whitespace-nowrap">—</td>
                                <td class="vl-pacientes-td">
                                    <span class="vl-pacientes-pago-global-badge">Pago global</span>
                                </td>
                                <td class="vl-pacientes-td">—</td>
                                <td class="vl-pacientes-td">—</td>
                                <td class="vl-pacientes-td">—</td>
                                <td class="vl-pacientes-td">—</td>
                                <td class="vl-pacientes-td">—</td>
                                <td class="vl-pacientes-td vl-pacientes-td--estado">Pago</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->precioListaFormateado() }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->descuentoFormateado() }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->precioConDescuentoFormateado() }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap font-semibold">{{ $paciente->importePagadoMovimientoFormateado() }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap font-semibold tabular-nums">
                                    {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda((float) $saldoFila) }}
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                            </tr>
                        @else
                            <tr class="vl-pacientes-row {{ $paciente->filaClaseCss() }}" wire:key="pac-cli-{{ $paciente->idPacientes }}">
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    @if (tienePermiso(\App\Support\PermisosIaCatalog::INFORMES))
                                        <a href="{{ route($rutaInforme, ['ref' => \App\Support\Security\OpaqueRouteToken::forInformePaciente((int) $paciente->idPacientes)]) }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           title="Informe PDF"
                                           aria-label="Abrir informe PDF"
                                           class="vl-grid-icon-btn text-red-600 hover:bg-red-50">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5h7v1.5h-7v-1.5zm0 3h7v1.5h-7v-1.5z"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span class="inline-flex h-8 w-8 items-center justify-center text-neutral-300" title="Sin permiso de informes">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5h7v1.5h-7v-1.5zm0 3h7v1.5h-7v-1.5z"/>
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--num">
                                    {{ ($pacientes->currentPage() - 1) * $pacientes->perPage() + $loop->iteration }}
                                </td>
                                <td class="vl-pacientes-td whitespace-nowrap">{{ $paciente->fechhoyFormateada() }}</td>
                                <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $paciente->nombreProtocolo ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->propietario ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->especie?->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->raza?->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->sexo ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->edad ?: '—' }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--estado">
                                    {{ $paciente->estado ?: \App\Support\Resultados\ResultadosEstadosCatalog::EN_PROC }}
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->precioListaFormateado() }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->descuentoFormateado() }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->precioConDescuentoFormateado() }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->pagadoFormateado() }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap font-semibold tabular-nums">
                                    {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda((float) $saldoFila) }}
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <x-vl-grid-icon-btn
                                        title="Asistente IA"
                                        variant="primary"
                                        wire:click="abrirModalIa({{ $paciente->idPacientes }})"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="17" class="vl-pacientes-td text-center text-neutral-500 py-10">
                                @if ($vista === 'hoy')
                                    @php
                                        $fechaEfectiva = $this->fechaVistaEfectiva();
                                        $esHoy = $fechaEfectiva === now()->toDateString();
                                    @endphp
                                    No hay protocolos registrados
                                    {{ $esHoy ? 'para hoy' : 'para el '. \Illuminate\Support\Carbon::parse($fechaEfectiva)->format('d/m/Y') }}.
                                @else
                                    No hay protocolos registrados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pacientes->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $pacientes->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>

    @include('livewire.protocolos.partials.paciente-protocolo-modales')
</div>
