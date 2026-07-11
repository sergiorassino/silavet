<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="vl-eyebrow">Protocolos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Pacientes</h1>
                <p class="mt-2 text-sm text-white/80">
                    @if ($vista === 'hoy')
                        Protocolos con fecha de hoy ({{ now()->format('d/m/Y') }}).
                    @else
                        Historial completo de protocolos del laboratorio.
                    @endif
                </p>
            </div>
            <a href="{{ route('protocolos.create') }}"
               class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">
                Nuevo Paciente
            </a>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por protocolo, paciente, tutor, cliente o email…"
                   class="form-input max-w-xl w-full sm:flex-1">
            <div class="vl-pacientes-vista-toggle shrink-0" role="group" aria-label="Vista del listado">
                <button type="button"
                        wire:click="$set('vista', 'hoy')"
                        class="vl-pacientes-vista-toggle-btn {{ $vista === 'hoy' ? 'is-active' : '' }}"
                        aria-pressed="{{ $vista === 'hoy' ? 'true' : 'false' }}">
                    Pacientes de hoy
                </button>
                <button type="button"
                        wire:click="$set('vista', 'historial')"
                        class="vl-pacientes-vista-toggle-btn {{ $vista === 'historial' ? 'is-active' : '' }}"
                        aria-pressed="{{ $vista === 'historial' ? 'true' : 'false' }}">
                    Historial
                </button>
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
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Determinaciones">Determ</th>
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
                    @forelse ($pacientes as $paciente)
                        <tr class="vl-pacientes-row {{ $paciente->filaClaseCss() }}">
                            <td class="vl-pacientes-td vl-pacientes-td--num">
                                {{ ($pacientes->currentPage() - 1) * $pacientes->perPage() + $loop->iteration }}
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
                                <a href="{{ route('protocolos.determinaciones', $paciente->idPacientes) }}"
                                   title="Determinaciones solicitadas"
                                   aria-label="Determinaciones solicitadas"
                                   class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </a>
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->precioFormateado() }}</td>
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
                                    <a href="{{ route('protocolos.resultados', $paciente->idPacientes) }}"
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
                            <td class="vl-pacientes-td vl-pacientes-td--icon">
                                <x-vl-grid-icon-btn title="Adjunto" variant="warning">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                    </svg>
                                </x-vl-grid-icon-btn>
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--icon">
                                <x-vl-grid-icon-btn title="Notificaciones">
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
                                <x-vl-grid-icon-btn title="Asistente IA" variant="primary">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                              d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                                    </svg>
                                </x-vl-grid-icon-btn>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="22" class="vl-pacientes-td text-center text-neutral-500 py-10">
                                @if ($vista === 'hoy')
                                    No hay protocolos registrados para hoy.
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
            <div class="vl-matriz-list-footer border-t border-accent-200 px-5 py-3">
                {{ $pacientes->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>

    @if ($modalEnvioAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalEnvio">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalEnvio"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-envio-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-envio-titulo" class="text-lg font-bold text-neutral-900">Enviar informe</h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Protocolo <strong>{{ $envioProtocolo }}</strong>
                            · {{ $envioNombrePaciente }}
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-3">
                        <div class="vl-form--compact space-y-2">
                            <section class="rounded-lg border border-accent-200 bg-accent-50/40 px-3 py-2">
                                <div class="flex min-w-0 items-baseline gap-2">
                                    <h4 class="shrink-0 text-[10px] font-bold uppercase tracking-[0.08em] text-primary-700">Cliente</h4>
                                    <p class="min-w-0 truncate text-xs font-medium text-neutral-900" title="{{ $envioClienteNombre }}">{{ $envioClienteNombre }}</p>
                                </div>
                                <div class="mt-1.5 grid grid-cols-[1fr_7.5rem] gap-1.5">
                                    <div class="vl-form-field">
                                        <label class="sr-only" for="envioClienteEmail">Email del cliente</label>
                                        <input wire:model.blur="envioClienteEmail"
                                               id="envioClienteEmail"
                                               type="email"
                                               maxlength="150"
                                               class="form-input"
                                               placeholder="Email"
                                               autocomplete="off">
                                        @error('envioClienteEmail') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="vl-form-field">
                                        <label class="sr-only" for="envioClienteWhatsapp">WhatsApp del cliente</label>
                                        <input wire:model.blur="envioClienteWhatsapp"
                                               id="envioClienteWhatsapp"
                                               type="text"
                                               maxlength="20"
                                               class="form-input"
                                               placeholder="WhatsApp"
                                               autocomplete="off">
                                        @error('envioClienteWhatsapp') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-accent-200 bg-accent-50/40 px-3 py-2">
                                <div class="flex min-w-0 items-baseline gap-2">
                                    <h4 class="shrink-0 text-[10px] font-bold uppercase tracking-[0.08em] text-primary-700">Paciente</h4>
                                    <p class="min-w-0 truncate text-xs font-medium text-neutral-900" title="{{ $envioNombrePaciente }}">{{ $envioNombrePaciente }}</p>
                                </div>
                                <div class="mt-1.5 grid grid-cols-[1fr_7.5rem] gap-1.5">
                                    <div class="vl-form-field">
                                        <label class="sr-only" for="envioPacienteEmail">Email del paciente</label>
                                        <input wire:model.blur="envioPacienteEmail"
                                               id="envioPacienteEmail"
                                               type="email"
                                               maxlength="150"
                                               class="form-input"
                                               placeholder="Email"
                                               autocomplete="off">
                                        @error('envioPacienteEmail') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="vl-form-field">
                                        <label class="sr-only" for="envioPacienteWhatsapp">WhatsApp del paciente</label>
                                        <input wire:model.blur="envioPacienteWhatsapp"
                                               id="envioPacienteWhatsapp"
                                               type="text"
                                               maxlength="20"
                                               class="form-input"
                                               placeholder="WhatsApp"
                                               autocomplete="off">
                                        @error('envioPacienteWhatsapp') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="vl-form--compact grid gap-2 sm:grid-cols-2">
                            <div class="vl-form-field">
                                <label class="form-label" for="envioDestinatario">Destinatario</label>
                                <select wire:model="envioDestinatario" id="envioDestinatario" class="form-select">
                                    <option value="">Seleccionar</option>
                                    <option value="cliente">Cliente</option>
                                    <option value="paciente">Paciente</option>
                                </select>
                                @error('envioDestinatario') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="vl-form-field">
                                <label class="form-label" for="envioForma">Forma de envío</label>
                                <select wire:model="envioForma" id="envioForma" class="form-select">
                                    <option value="">Seleccionar</option>
                                    <option value="mail">Mail</option>
                                    <option value="whatsapp">WhatsApp</option>
                                </select>
                                @error('envioForma') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <p class="text-[11px] leading-snug text-neutral-500">
                            Mail: usa la cuenta de Parámetros del Sistema.
                            WhatsApp: abre WhatsApp Web.
                        </p>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalEnvio"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="confirmarEnvio"
                                wire:loading.attr="disabled"
                                wire:target="confirmarEnvio"
                                class="btn-primary rounded-xl px-4 py-2 text-sm">
                            <span wire:loading.remove wire:target="confirmarEnvio">Enviar</span>
                            <span wire:loading wire:target="confirmarEnvio">Enviando…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($modalEdInfAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalEdInf">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalEdInf"
                        aria-label="Cerrar"></button>
                <div class="vl-edinf-modal relative z-10 flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-edinf-titulo">
                    <div class="border-b border-accent-200 px-5 py-3">
                        <h3 id="modal-edinf-titulo" class="text-base font-bold text-neutral-900">Editar informe</h3>
                        <p class="mt-0.5 text-xs text-neutral-600">
                            Protocolo <strong>{{ $edInfProtocolo }}</strong>
                            · {{ $edInfNombrePaciente }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-neutral-500">
                            Visibilidad de los renglones en el informe (Sí = mostrar, No = ocultar).
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-2">
                        @if (count($edInfRenglones) === 0)
                            <p class="py-8 text-center text-sm text-neutral-500">
                                No hay renglones de informe para este protocolo.
                                Agregue determinaciones primero.
                            </p>
                        @else
                            <table class="vl-edinf-table">
                                <thead>
                                    <tr>
                                        <th class="vl-edinf-th vl-edinf-th--grupo">Grupo</th>
                                        <th class="vl-edinf-th">Ítem</th>
                                        <th class="vl-edinf-th vl-edinf-th--visible">Visible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $grupoActual = null; @endphp
                                    @foreach ($edInfRenglones as $renglon)
                                        @php
                                            $nombreItem = $renglon['nombreItem'] !== '' ? $renglon['nombreItem'] : '—';
                                            $oculto = (int) $renglon['mostrar'] !== 1;
                                        @endphp
                                        <tr class="vl-edinf-row {{ $oculto ? 'is-oculto' : '' }}">
                                            <td class="vl-edinf-td vl-edinf-td--grupo">
                                                @if ($grupoActual !== $renglon['idGrupos'])
                                                    @php $grupoActual = $renglon['idGrupos']; @endphp
                                                    <span class="vl-edinf-grupo" title="{{ $renglon['nombreGrupo'] ?: '—' }}">
                                                        {{ $renglon['nombreGrupo'] ?: '—' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="vl-edinf-td vl-edinf-td--item">
                                                <span class="vl-edinf-item" title="{{ $nombreItem }}">{{ $nombreItem }}</span>
                                            </td>
                                            <td class="vl-edinf-td vl-edinf-td--visible">
                                                <label class="sr-only" for="edinf-mostrar-{{ $renglon['idRenglones'] }}">
                                                    Visibilidad de {{ $nombreItem }}
                                                </label>
                                                <select id="edinf-mostrar-{{ $renglon['idRenglones'] }}"
                                                        class="vl-edinf-select"
                                                        wire:change="setMostrarRenglon({{ $renglon['idRenglones'] }}, $event.target.value)"
                                                        wire:loading.attr="disabled">
                                                    <option value="1" @selected(! $oculto)>Sí</option>
                                                    <option value="0" @selected($oculto)>No</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-accent-200 px-5 py-2.5">
                        <button type="button"
                                wire:click="cerrarModalEdInf"
                                class="rounded-lg border border-accent-200 px-3.5 py-1.5 text-xs font-medium text-neutral-700 hover:bg-accent-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($modalObsAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalObs">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalObs"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-obs-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-obs-titulo" class="text-lg font-bold text-neutral-900">Observaciones</h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Protocolo <strong>{{ $obsProtocolo }}</strong>
                            · {{ $obsNombrePaciente }}
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <div class="vl-form-field">
                            <label class="form-label" for="obsTexto">Observaciones del protocolo</label>
                            <textarea wire:model="obsTexto"
                                      id="obsTexto"
                                      rows="8"
                                      class="form-input"
                                      placeholder="Escriba las observaciones…"></textarea>
                            @error('obsTexto') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalObs"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="guardarObservaciones"
                                wire:loading.attr="disabled"
                                wire:target="guardarObservaciones"
                                class="btn-primary rounded-xl px-4 py-2 text-sm">
                            <span wire:loading.remove wire:target="guardarObservaciones">Guardar</span>
                            <span wire:loading wire:target="guardarObservaciones">Guardando…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
