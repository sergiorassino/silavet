<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Autogestión del tutor</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Pacientes</h1>
                <p class="mt-2 text-sm text-white/80">
                    Estudios finalizados de {{ $nombreTutor !== '' ? $nombreTutor : 'tus mascotas' }}.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-card vl-pacientes-card">
        <div class="vl-toolbar border-b border-accent-200 px-3 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por protocolo, paciente o tutor…"
                   class="form-input max-w-xl w-full sm:flex-1">
            <div class="vl-pacientes-fecha-filtro flex max-w-full flex-wrap items-center gap-2 text-xs font-semibold text-neutral-600 shrink-0">
                <label for="tutorFechaDesde" class="whitespace-nowrap">Desde</label>
                <input wire:model.live="fechaDesde"
                       id="tutorFechaDesde"
                       type="date"
                       class="form-input py-1.5 text-xs w-auto"
                       aria-label="Filtrar estudios desde">
                <label for="tutorFechaHasta" class="whitespace-nowrap">Hasta</label>
                <input wire:model.live="fechaHasta"
                       id="tutorFechaHasta"
                       type="date"
                       class="form-input py-1.5 text-xs w-auto"
                       aria-label="Filtrar estudios hasta">
            </div>
        </div>

        <div class="vl-pacientes-scroll">
            <table class="vl-pacientes-grid min-w-full text-xs">
                <thead>
                    <tr>
                        <th class="vl-pacientes-th vl-pacientes-th--num">#</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Informe PDF">INFOR.</th>
                        <th class="vl-pacientes-th">Fechhoy</th>
                        <th class="vl-pacientes-th">Protocolo</th>
                        <th class="vl-pacientes-th">Nombre</th>
                        <th class="vl-pacientes-th">Tutor</th>
                        <th class="vl-pacientes-th">Especie</th>
                        <th class="vl-pacientes-th">Raza</th>
                        <th class="vl-pacientes-th">Sexo</th>
                        <th class="vl-pacientes-th">Edad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pacientes as $paciente)
                        <tr class="vl-pacientes-row {{ $paciente->filaClaseCss() }}" wire:key="pac-tutor-{{ $paciente->idPacientes }}">
                            <td class="vl-pacientes-td text-center tabular-nums">
                                {{ ($pacientes->currentPage() - 1) * $pacientes->perPage() + $loop->iteration }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--icon">
                                <a href="{{ $this->urlInforme((int) $paciente->idPacientes) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Informe PDF"
                                   aria-label="Abrir informe PDF"
                                   class="vl-grid-icon-btn text-red-600 hover:bg-red-50">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5h7v1.5h-7v-1.5zm0 3h7v1.5h-7v-1.5z"/>
                                    </svg>
                                </a>
                            </td>
                            <td class="vl-pacientes-td whitespace-nowrap">{{ $paciente->fechhoyFormateada() }}</td>
                            <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $paciente->nombreProtocolo ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $paciente->nombre ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $paciente->propietario ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $paciente->especie?->nombre ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $paciente->raza?->nombre ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $paciente->sexo ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $paciente->edad ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="vl-pacientes-td text-center text-neutral-500 py-10">
                                @if (trim($busqueda) !== '' || $fechaDesde !== '' || $fechaHasta !== '')
                                    No hay estudios finalizados que coincidan con la búsqueda.
                                @else
                                    No hay estudios finalizados para este DNI.
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
