<div class="vl-page vl-page--wide"
     x-data="{
        enfocarFila(id) {
            this.$nextTick(() => {
                const row = document.getElementById('pac-row-' + id);
                if (!row) {
                    return;
                }

                row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                row.classList.add('vl-pacientes-row-focus');
                row.focus({ preventScroll: true });

                window.setTimeout(() => row.classList.remove('vl-pacientes-row-focus'), 2500);

                try {
                    const url = new URL(window.location.href);
                    if (url.searchParams.has('foco')) {
                        url.searchParams.delete('foco');
                        const qs = url.searchParams.toString();
                        window.history.replaceState({}, '', url.pathname + (qs ? '?' + qs : '') + url.hash);
                    }
                } catch (e) {}
            });
        }
     }"
     x-init="@if ($focoIdPaciente) enfocarFila({{ (int) $focoIdPaciente }}) @endif"
     @pacientes-enfocar-fila.window="enfocarFila($event.detail.id)">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Protocolos</p>
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
                        Historial completo de protocolos del laboratorio.
                    @endif
                </p>
            </x-vl-hero-heading>
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                <button type="button"
                        wire:click="abrirModalPagoGlobal"
                        class="btn-secondary shrink-0 border-white/40 bg-white/15 text-white hover:bg-white/25">
                    Pago global
                </button>
                <a href="{{ route('protocolos.create', $this->filtrosListadoParaUrl()) }}"
                   class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">
                    Nuevo Paciente
                </a>
            </div>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por protocolo, paciente, tutor o cliente…"
                   class="form-input max-w-xl w-full sm:flex-1">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 shrink-0">
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
                        <th class="vl-pacientes-th vl-pacientes-th--num">#</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Editar paciente">Ed.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Etiquetas de tubos">Etiq</th>
                        <th class="vl-pacientes-th">Cliente</th>
                        <th class="vl-pacientes-th">Fechhoy</th>
                        <th class="vl-pacientes-th">Protocolo</th>
                        <th class="vl-pacientes-th">Nombre</th>
                        <th class="vl-pacientes-th">Tutor</th>
                        <th class="vl-pacientes-th">Especie</th>
                        <th class="vl-pacientes-th">Raza</th>
                        <th class="vl-pacientes-th">Sexo</th>
                        <th class="vl-pacientes-th">Edad</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Determinaciones">Determ</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Precio</th>
                        @if ($mostrarCadete)
                            <th class="vl-pacientes-th vl-pacientes-th--num" title="Cadetería">Cadete</th>
                        @endif
                        <th class="vl-pacientes-th">Est</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Cargar resultados">Cargar</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Editar informe">Ed.Inf</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Observaciones">Obs.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Informe PDF">Informe</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Adjunto">Adj.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Notificaciones">Avisos</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Enviar informe">Enviar</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Asistente IA">IA</th>
                        @if ($mostrarColumnaAfip)
                            <th class="vl-pacientes-th vl-pacientes-th--icon" title="Comprobantes AFIP">AFIP</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pacientes as $paciente)
                        @if ($paciente->esPagoGlobal())
                            <tr id="pac-row-{{ $paciente->idPacientes }}"
                                tabindex="-1"
                                class="vl-pacientes-row {{ $paciente->filaClaseCss() }}"
                                wire:key="pac-{{ $paciente->idPacientes }}">
                                <td class="vl-pacientes-td vl-pacientes-td--num">
                                    {{ ($pacientes->currentPage() - 1) * $pacientes->perPage() + $loop->iteration }}
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <button type="button"
                                            wire:click="abrirModalEditarPagoGlobal({{ $paciente->idPacientes }})"
                                            title="Editar pago global"
                                            aria-label="Editar pago global"
                                            class="vl-grid-icon-btn text-neutral-600 hover:bg-neutral-100">
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td">{{ $paciente->cliente?->nombre ?: '—' }}</td>
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
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap font-semibold">
                                    {{ $paciente->pagadoFormateado() }}
                                </td>
                                @if ($mostrarCadete)
                                    <td class="vl-pacientes-td vl-pacientes-td--num">—</td>
                                @endif
                                <td class="vl-pacientes-td"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon"></td>
                                @if ($mostrarColumnaAfip)
                                    <td class="vl-pacientes-td vl-pacientes-td--icon">
                                        @php
                                            $afipEmitido = isset($afipEmitidos[(int) $paciente->idPacientes]);
                                        @endphp
                                        <a href="{{ route('facturacion.afip.comprobantes', array_merge(
                                                ['ref' => \App\Support\Security\OpaqueRouteToken::forCompAfipPaciente((int) $paciente->idPacientes)],
                                                $this->filtrosListadoParaUrl()
                                            )) }}"
                                           title="{{ $afipEmitido ? 'Comprobantes AFIP (emitido)' : 'Comprobantes AFIP' }}"
                                           aria-label="{{ $afipEmitido ? 'Comprobantes AFIP (emitido)' : 'Comprobantes AFIP' }}"
                                           class="vl-grid-icon-btn {{ $afipEmitido ? 'bg-orange-500 text-white ring-2 ring-orange-300 hover:bg-orange-600' : 'text-sky-700 hover:bg-sky-50' }}">
                                            @if ($afipEmitido)
                                                {{-- Círculo con tilde sólido: comprobante emitido --}}
                                                <svg class="h-[26px] w-[26px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                          d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"/>
                                                </svg>
                                            @else
                                                <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            @endif
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @else
                            <tr id="pac-row-{{ $paciente->idPacientes }}"
                                tabindex="-1"
                                class="vl-pacientes-row {{ $paciente->filaClaseCss() }}"
                                wire:key="pac-{{ $paciente->idPacientes }}">
                                <td class="vl-pacientes-td vl-pacientes-td--num">
                                    {{ ($pacientes->currentPage() - 1) * $pacientes->perPage() + $loop->iteration }}
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <a href="{{ route('protocolos.edit', array_merge(['id' => $paciente->idPacientes], $this->filtrosListadoParaUrl())) }}"
                                       title="Editar paciente"
                                       aria-label="Editar paciente"
                                       class="vl-grid-icon-btn text-neutral-600 hover:bg-neutral-100">
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <button type="button"
                                            title="Imprimir etiquetas de tubos"
                                            aria-label="Imprimir etiquetas de tubos"
                                            class="vl-grid-icon-btn text-neutral-600 hover:bg-neutral-100"
                                            x-data
                                            @click.prevent="
                                                const c = await window.vlSwalPedirCantidad({
                                                    titulo: 'Etiquetas de tubos',
                                                    mensaje: '¿Cuántas etiquetas desea imprimir? (todas iguales)',
                                                    valor: 2,
                                                    min: 1,
                                                    max: 99,
                                                });
                                                if (c !== null) {
                                                    $wire.abrirEtiquetasTubo({{ (int) $paciente->idPacientes }}, c);
                                                }
                                            ">
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                    </button>
                                </td>
                                <td class="vl-pacientes-td">{{ $paciente->cliente?->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td whitespace-nowrap">{{ $paciente->fechhoyFormateada() }}</td>
                                <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $paciente->nombreProtocolo ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->propietario ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->especie?->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->raza?->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->sexo ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->edad ?: '—' }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <a href="{{ route('protocolos.determinaciones', array_merge(['id' => $paciente->idPacientes], $this->filtrosListadoParaUrl())) }}"
                                       title="Determinaciones solicitadas"
                                       aria-label="Determinaciones solicitadas"
                                       class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </a>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->precioFormateado() }}</td>
                                @if ($mostrarCadete)
                                    <td class="vl-pacientes-td vl-pacientes-td--num">
                                        <input type="text"
                                               value="{{ number_format((float) ($paciente->cadete ?? 0), 2, ',', '.') }}"
                                               wire:blur="guardarCadete({{ $paciente->idPacientes }}, $event.target.value)"
                                               wire:keydown.enter.prevent="$event.target.blur()"
                                               class="vl-determinaciones-input vl-determinaciones-input--precio"
                                               inputmode="decimal"
                                               aria-label="Cadete del protocolo {{ $paciente->nombreProtocolo }}">
                                    </td>
                                @endif
                                <td class="vl-pacientes-td whitespace-nowrap">
                                    <button type="button"
                                            wire:click="avanzarEstado({{ $paciente->idPacientes }})"
                                            wire:loading.attr="disabled"
                                            title="Cambiar estado (clic para avanzar)"
                                            class="vl-pacientes-estado-btn">
                                        {{ $paciente->estado ?: \App\Support\Resultados\ResultadosEstadosCatalog::EN_PROC }}
                                    </button>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    @if (tienePermiso(\App\Support\PermisosIaCatalog::RESULTADOS))
                                        <a href="{{ route('protocolos.resultados', array_merge(['id' => $paciente->idPacientes], $this->filtrosListadoParaUrl())) }}"
                                           title="Cargar resultados"
                                           aria-label="Cargar resultados"
                                           class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                            <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                      d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span class="inline-flex h-8 w-8 items-center justify-center text-neutral-300" title="Sin permiso de resultados">
                                            <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                      d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <x-vl-grid-icon-btn
                                        title="Editar informe"
                                        variant="danger"
                                        wire:click="abrirModalEdInf({{ $paciente->idPacientes }})"
                                    >
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <x-vl-grid-icon-btn
                                        title="Observaciones"
                                        variant="info"
                                        wire:click="abrirModalObs({{ $paciente->idPacientes }})"
                                    >
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    @if (tienePermiso(\App\Support\PermisosIaCatalog::INFORMES))
                                        <a href="{{ route('protocolos.informe', ['ref' => \App\Support\Security\OpaqueRouteToken::forInformePaciente((int) $paciente->idPacientes)]) }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           title="Informe PDF"
                                           aria-label="Abrir informe PDF"
                                           class="vl-grid-icon-btn text-red-600 hover:bg-red-50">
                                            <svg class="h-[26px] w-[26px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5h7v1.5h-7v-1.5zm0 3h7v1.5h-7v-1.5z"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span class="inline-flex h-8 w-8 items-center justify-center text-neutral-300" title="Sin permiso de informes">
                                            <svg class="h-[26px] w-[26px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5h7v1.5h-7v-1.5zm0 3h7v1.5h-7v-1.5z"/>
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon {{ $paciente->tieneAdjunto() ? 'vl-pacientes-td--carga-ok' : '' }}">
                                    <x-vl-grid-icon-btn
                                        :title="$paciente->tieneAdjunto() ? 'Adjunto PDF (cargado)' : 'Adjuntar PDF'"
                                        :variant="$paciente->tieneAdjunto() ? 'success' : 'neutral'"
                                        wire:click="abrirModalAdjunto({{ $paciente->idPacientes }})"
                                    >
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon {{ $paciente->tieneNotificacion() ? 'vl-pacientes-td--carga-ok' : '' }}">
                                    <x-vl-grid-icon-btn
                                        :title="$paciente->tieneNotificacion() ? 'Notificación (cargada)' : 'Notificaciones'"
                                        :variant="$paciente->tieneNotificacion() ? 'success' : 'neutral'"
                                        wire:click="abrirModalAviso({{ $paciente->idPacientes }})"
                                    >
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <x-vl-grid-icon-btn
                                        title="Enviar informe"
                                        variant="success"
                                        wire:click="abrirModalEnvio({{ $paciente->idPacientes }})"
                                    >
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <x-vl-grid-icon-btn
                                        title="Asistente IA"
                                        variant="primary"
                                        wire:click="abrirModalIa({{ $paciente->idPacientes }})"
                                    >
                                        <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </td>
                                @if ($mostrarColumnaAfip)
                                    <td class="vl-pacientes-td vl-pacientes-td--icon">
                                        @php
                                            $afipEmitido = isset($afipEmitidos[(int) $paciente->idPacientes]);
                                        @endphp
                                        <a href="{{ route('facturacion.afip.comprobantes', array_merge(
                                                ['ref' => \App\Support\Security\OpaqueRouteToken::forCompAfipPaciente((int) $paciente->idPacientes)],
                                                $this->filtrosListadoParaUrl()
                                            )) }}"
                                           title="{{ $afipEmitido ? 'Comprobantes AFIP (emitido)' : 'Comprobantes AFIP' }}"
                                           aria-label="{{ $afipEmitido ? 'Comprobantes AFIP (emitido)' : 'Comprobantes AFIP' }}"
                                           class="vl-grid-icon-btn {{ $afipEmitido ? 'bg-orange-500 text-white ring-2 ring-orange-300 hover:bg-orange-600' : 'text-sky-700 hover:bg-sky-50' }}">
                                            @if ($afipEmitido)
                                                {{-- Círculo con tilde sólido: comprobante emitido --}}
                                                <svg class="h-[26px] w-[26px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                          d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"/>
                                                </svg>
                                            @else
                                                <svg class="h-[26px] w-[26px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            @endif
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ ($mostrarCadete ? 24 : 23) + ($mostrarColumnaAfip ? 1 : 0) }}" class="vl-pacientes-td text-center text-neutral-500 py-10">
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

    @if ($modalPagoGlobalAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalPagoGlobal">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalPagoGlobal"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-pago-global-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-pago-global-titulo" class="text-lg font-bold text-neutral-900">
                            {{ $pagoGlobalIdPacientes ? 'Editar pago global' : 'Pago global' }}
                        </h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Registro de pago del cliente para el día
                            {{ \Illuminate\Support\Carbon::parse($this->fechaVistaEfectiva())->format('d/m/Y') }}.
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-4">
                        <div class="vl-form--compact space-y-3">
                            <div class="vl-form-field">
                                <label class="form-label" for="pagoGlobalIdClientes">Cliente</label>
                                <select wire:model="pagoGlobalIdClientes"
                                        id="pagoGlobalIdClientes"
                                        class="form-select"
                                        @disabled(labCtx()->esCliente())>
                                    <option value="">Seleccionar</option>
                                    @foreach ($clientesPagoGlobal as $cliente)
                                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('pagoGlobalIdClientes') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="vl-form-field">
                                <label class="form-label" for="pagoGlobalImporte">Importe</label>
                                <input wire:model="pagoGlobalImporte"
                                       id="pagoGlobalImporte"
                                       type="text"
                                       inputmode="decimal"
                                       class="form-input"
                                       placeholder="0,00"
                                       autocomplete="off">
                                @error('pagoGlobalImporte') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="vl-form-field">
                                <label class="form-label" for="pagoGlobalIdMediodepago">Medio de pago</label>
                                <select wire:model="pagoGlobalIdMediodepago"
                                        id="pagoGlobalIdMediodepago"
                                        class="form-select">
                                    <option value="">Seleccionar</option>
                                    @foreach ($mediosPago as $medio)
                                        <option value="{{ $medio->id }}">{{ $medio->nombreMedioPago }}</option>
                                    @endforeach
                                </select>
                                @error('pagoGlobalIdMediodepago') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalPagoGlobal"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="guardarPagoGlobal"
                                wire:loading.attr="disabled"
                                class="btn-primary">
                            {{ $pagoGlobalIdPacientes ? 'Guardar cambios' : 'Guardar pago' }}
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
