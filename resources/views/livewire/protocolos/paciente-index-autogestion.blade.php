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
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por protocolo, paciente o tutor…"
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
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Informe PDF">Informe</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">#</th>
                        <th class="vl-pacientes-th">Fechhoy</th>
                        <th class="vl-pacientes-th">Protocolo</th>
                        <th class="vl-pacientes-th">Nombre</th>
                        <th class="vl-pacientes-th">Tutor</th>
                        <th class="vl-pacientes-th">Especie</th>
                        <th class="vl-pacientes-th">Raza</th>
                        <th class="vl-pacientes-th">Sexo</th>
                        <th class="vl-pacientes-th">Edad</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Precio</th>
                        <th class="vl-pacientes-th">Est</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pacientes as $paciente)
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
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->precioFormateado() }}</td>
                            <td class="vl-pacientes-td whitespace-nowrap">
                                {{ $paciente->estado ?: \App\Support\Resultados\ResultadosEstadosCatalog::EN_PROC }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="vl-pacientes-td text-center text-neutral-500 py-10">
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
</div>
