<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="vl-eyebrow">Protocolos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Pacientes</h1>
                <p class="mt-2 text-sm text-white/80">Listado de protocolos analíticos del laboratorio.</p>
            </div>
            <a href="{{ route('protocolos.create') }}"
               class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">
                Nuevo Paciente
            </a>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por protocolo, paciente, tutor, cliente o email…"
                   class="form-input max-w-xl">
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
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Ver">Ver</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Precio</th>
                        <th class="vl-pacientes-th">Est</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Cargar resultados">Cargar</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Editar informe">Ed.Inf</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Observaciones">Obs.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Informe PDF">Informe</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Adjunto">Adj.</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Notificaciones">Avisos</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Enviar informe">Enviar</th>
                        <th class="vl-pacientes-th">Email</th>
                        <th class="vl-pacientes-th">Whatsapp</th>
                        <th class="vl-pacientes-th vl-pacientes-th--icon" title="Asistente IA">IA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
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
                                <x-vl-grid-icon-btn title="Ver protocolo" variant="primary">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </x-vl-grid-icon-btn>
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">{{ $paciente->precioFormateado() }}</td>
                            <td class="vl-pacientes-td whitespace-nowrap">{{ $paciente->estado ?: '—' }}</td>
                            <td class="vl-pacientes-td vl-pacientes-td--icon">
                                <x-vl-grid-icon-btn title="Cargar resultados">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </x-vl-grid-icon-btn>
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--icon">
                                <x-vl-grid-icon-btn title="Editar informe" variant="danger">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </x-vl-grid-icon-btn>
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--icon">
                                <x-vl-grid-icon-btn title="Observaciones" variant="info">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </x-vl-grid-icon-btn>
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--icon">
                                <x-vl-grid-icon-btn title="Informe PDF" variant="danger">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5h7v1.5h-7v-1.5zm0 3h7v1.5h-7v-1.5z"/>
                                    </svg>
                                </x-vl-grid-icon-btn>
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
                                <x-vl-grid-icon-btn title="Enviar informe" variant="success">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </x-vl-grid-icon-btn>
                            </td>
                            <td class="vl-pacientes-td max-w-[10rem] truncate" title="{{ $paciente->email }}">{{ $paciente->email ?: '—' }}</td>
                            <td class="vl-pacientes-td whitespace-nowrap">{{ $paciente->whatsapp ?: '—' }}</td>
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
                            <td colspan="24" class="vl-pacientes-td text-center text-neutral-500 py-10">
                                No hay protocolos registrados.
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
</div>
