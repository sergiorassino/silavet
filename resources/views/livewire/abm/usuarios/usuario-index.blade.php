<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">ABM</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Gestión de Usuarios</h1>
                <p class="mt-2 text-sm text-white/80">Personal del laboratorio y usuarios asociados a clientes.</p>
            </x-vl-hero-heading>
            <a href="{{ route('abm.usuarios.create') }}" class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">Nuevo usuario</a>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por nombre, DNI, rol, cliente o CUIT…"
                   class="form-input max-w-md">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="table-header">Nombre</th>
                        <th class="table-header">DNI</th>
                        <th class="table-header">Rol</th>
                        <th class="table-header">Cliente</th>
                        <th class="table-header text-center">AFIP</th>
                        <th class="table-header text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($usuarios as $usuario)
                        <tr class="hover:bg-accent-50/40" wire:key="usuario-{{ $usuario->idUsuarios }}">
                            <td class="table-cell font-medium">{{ $usuario->apenom }}</td>
                            <td class="table-cell tabular-nums">{{ $usuario->dni }}</td>
                            <td class="table-cell">{{ $usuario->rol?->rol ?: '—' }}</td>
                            <td class="table-cell">{{ $usuario->cliente?->nombre ?: '—' }}</td>
                            <td class="table-cell text-center">
                                @if ((int) $usuario->permisoAfip === 1)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Sí</span>
                                @else
                                    <span class="text-neutral-400">No</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="{{ route('abm.usuarios.edit', $usuario->idUsuarios) }}"
                                       title="Editar usuario"
                                       aria-label="Editar usuario"
                                       class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <x-vl-grid-icon-btn title="Eliminar usuario"
                                                        variant="danger"
                                                        wire:loading.attr="disabled"
                                                        x-on:click="window.vlSwalConfirmar('¿Eliminar este usuario? Esta acción no se puede deshacer.', 'Eliminar usuario', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $usuario->idUsuarios }}))">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-cell text-center text-neutral-500 py-8">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usuarios->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $usuarios->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
