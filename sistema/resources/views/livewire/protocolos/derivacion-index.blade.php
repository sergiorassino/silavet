<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="vl-eyebrow">Protocolos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Gestión de Derivaciones</h1>
                <p class="mt-2 text-sm text-white/80">
                    @if ($incluirFinalizados)
                        Todas las determinaciones derivadas, incluidos Final y Final/Env.
                    @else
                        Determinaciones derivadas (devueltas o no). Se ocultan Final y Final/Env.
                    @endif
                </p>
            </div>
            <button type="button"
                    wire:click="toggleIncluirFinalizados"
                    class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50 {{ $incluirFinalizados ? 'ring-2 ring-white/80' : '' }}"
                    aria-pressed="{{ $incluirFinalizados ? 'true' : 'false' }}">
                {{ $incluirFinalizados ? 'Ocultar finalizados' : 'Ver también finalizados' }}
            </button>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por protocolo, paciente, tutor, cliente, determinación o centro…"
                   class="form-input max-w-xl w-full lg:flex-1">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:flex-wrap shrink-0">
                <div class="vl-pacientes-vista-toggle" role="group" aria-label="Agrupación del listado">
                    <button type="button"
                            wire:click="$set('agrupacion', 'ninguna')"
                            class="vl-pacientes-vista-toggle-btn {{ $agrupacion === 'ninguna' ? 'is-active' : '' }}"
                            aria-pressed="{{ $agrupacion === 'ninguna' ? 'true' : 'false' }}">
                        Sin agrupar
                    </button>
                    <button type="button"
                            wire:click="$set('agrupacion', 'centro')"
                            class="vl-pacientes-vista-toggle-btn {{ $agrupacion === 'centro' ? 'is-active' : '' }}"
                            aria-pressed="{{ $agrupacion === 'centro' ? 'true' : 'false' }}">
                        Por centro
                    </button>
                    <button type="button"
                            wire:click="$set('agrupacion', 'cliente')"
                            class="vl-pacientes-vista-toggle-btn {{ $agrupacion === 'cliente' ? 'is-active' : '' }}"
                            aria-pressed="{{ $agrupacion === 'cliente' ? 'true' : 'false' }}">
                        Por cliente
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="vl-pacientes-grid min-w-full text-xs">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-pacientes-th vl-pacientes-th--num">#</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Paciente">Pac.</th>
                        <th class="vl-pacientes-th">Cliente</th>
                        <th class="vl-pacientes-th">Fechhoy</th>
                        <th class="vl-pacientes-th">Protocolo</th>
                        <th class="vl-pacientes-th">Nombre</th>
                        <th class="vl-pacientes-th">Tutor</th>
                        <th class="vl-pacientes-th">Especie</th>
                        <th class="vl-pacientes-th">Raza</th>
                        <th class="vl-pacientes-th">Sexo</th>
                        <th class="vl-pacientes-th">Edad</th>
                        <th class="vl-pacientes-th">Determinación</th>
                        <th class="vl-pacientes-th">Centro</th>
                        @if ($tieneFechasDerivacion)
                            <th class="vl-pacientes-th" title="Fecha de envío a derivación">F. envío</th>
                            <th class="vl-pacientes-th" title="Fecha de devolución de la determinación">F. devol.</th>
                        @endif
                        <th class="vl-pacientes-th vl-pacientes-th--num">Precio</th>
                        <th class="vl-pacientes-th">Est</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Cargar resultados">Cargar</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Editar informe">Ed.Inf</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Observaciones">Obs.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Informe PDF">Informe</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Adjunto">Adj.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Notificaciones">Avisos</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Enviar informe">Enviar</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Asistente IA">IA</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $colspan = $tieneFechasDerivacion ? 25 : 23;
                        $grupoAnterior = null;
                    @endphp
                    @forelse ($registros as $determinacion)
                        @php
                            $paciente = $determinacion->paciente;
                            $grupoActual = match ($agrupacion) {
                                'centro' => (string) ($determinacion->derivacion?->derivacion ?: 'Sin centro'),
                                'cliente' => (string) ($determinacion->cliente?->nombre ?: ($paciente?->cliente?->nombre ?: 'Sin cliente')),
                                default => null,
                            };
                        @endphp

                        @if ($grupoActual !== null && $grupoActual !== $grupoAnterior)
                            @php $grupoAnterior = $grupoActual; @endphp
                            <tr class="vl-derivaciones-grupo-row">
                                <td colspan="{{ $colspan }}" class="vl-pacientes-td vl-derivaciones-grupo-td">
                                    {{ $agrupacion === 'centro' ? 'Centro' : 'Cliente' }}:
                                    <strong>{{ $grupoActual }}</strong>
                                </td>
                            </tr>
                        @endif

                        @if ($paciente === null)
                            <tr class="vl-pacientes-row">
                                <td colspan="{{ $colspan }}" class="vl-pacientes-td text-neutral-500">
                                    Determinación #{{ $determinacion->idDeterminaciones }} sin protocolo asociado.
                                </td>
                            </tr>
                        @else
                            <tr class="vl-pacientes-row {{ $paciente->filaClaseCss() }}" wire:key="deriv-{{ $determinacion->idDeterminaciones }}">
                                <td class="vl-pacientes-td vl-pacientes-td--num">
                                    {{ ($registros->currentPage() - 1) * $registros->perPage() + $loop->iteration }}
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--icon">
                                    <a href="{{ route('protocolos.edit', $paciente->idPacientes) }}"
                                       title="Editar paciente"
                                       aria-label="Editar paciente"
                                       class="vl-grid-icon-btn text-neutral-600 hover:bg-neutral-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                </td>
                                <td class="vl-pacientes-td">{{ $determinacion->cliente?->nombre ?: ($paciente->cliente?->nombre ?: '—') }}</td>
                                <td class="vl-pacientes-td whitespace-nowrap">{{ $paciente->fechhoyFormateada() }}</td>
                                <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $paciente->nombreProtocolo ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->propietario ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->especie?->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->raza?->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->sexo ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $paciente->edad ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $determinacion->tipodeterminacion?->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td whitespace-nowrap">{{ $determinacion->derivacion?->derivacion ?: '—' }}</td>
                                @if ($tieneFechasDerivacion)
                                    <td class="vl-pacientes-td whitespace-nowrap">
                                        <input type="date"
                                               class="vl-derivaciones-fecha-input"
                                               value="{{ $determinacion->fechaEnvioDeriv?->format('Y-m-d') }}"
                                               wire:change="actualizarFechaEnvioDeriv({{ $determinacion->idDeterminaciones }}, $event.target.value)"
                                               title="Fecha de envío a derivación"
                                               aria-label="Fecha de envío a derivación">
                                    </td>
                                    <td class="vl-pacientes-td whitespace-nowrap">
                                        <input type="date"
                                               class="vl-derivaciones-fecha-input"
                                               value="{{ $determinacion->fechaDevolucDeterm?->format('Y-m-d') }}"
                                               wire:change="actualizarFechaDevolucDeterm({{ $determinacion->idDeterminaciones }}, $event.target.value)"
                                               title="Fecha de devolución"
                                               aria-label="Fecha de devolución">
                                    </td>
                                @endif
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $determinacion->precioFormateado() }}</td>
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
                                        <a href="{{ route('protocolos.resultados', ['id' => $paciente->idPacientes, 'origen' => 'derivaciones']) }}"
                                           title="Cargar resultados"
                                           aria-label="Cargar resultados"
                                           class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                      d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span class="inline-flex h-8 w-8 items-center justify-center text-neutral-300" title="Sin permiso de resultados">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                                <td class="vl-pacientes-td vl-pacientes-td--icon {{ $paciente->tieneAdjunto() ? 'vl-pacientes-td--carga-ok' : '' }}">
                                    <x-vl-grid-icon-btn
                                        :title="$paciente->tieneAdjunto() ? 'Adjunto PDF (cargado)' : 'Adjuntar PDF'"
                                        :variant="$paciente->tieneAdjunto() ? 'success' : 'neutral'"
                                        wire:click="abrirModalAdjunto({{ $paciente->idPacientes }})"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                            <td colspan="{{ $colspan }}" class="vl-pacientes-td text-center text-neutral-500 py-10">
                                No hay determinaciones derivadas con los filtros actuales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($registros->hasPages())
            <div class="vl-matriz-list-footer border-t border-accent-200 px-5 py-3">
                {{ $registros->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>

    @include('livewire.protocolos.partials.paciente-protocolo-modales')
</div>
