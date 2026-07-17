<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Gestión de Proveedores</h1>
                <p class="mt-2 text-sm text-white/80">Proveedores asociados a conceptos de egreso para movimientos de caja.</p>
            </x-vl-hero-heading>
            <a href="{{ route('tesoreria.proveedores.create') }}" class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">Nuevo proveedor</a>
        </div>
    </div>

    <div class="vl-card mx-auto max-w-3xl overflow-hidden">
        <div class="vl-toolbar flex flex-wrap items-center gap-2 border-b border-accent-200 px-3 py-2">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por proveedor, CUIT o concepto…"
                   class="form-input max-w-xs py-1.5 text-sm">
            <select wire:model.live="filtroConcepto" class="form-input max-w-[14rem] py-1.5 text-sm">
                <option value="">Todos los conceptos</option>
                @foreach ($conceptos as $concepto)
                    <option value="{{ $concepto->id }}">{{ $concepto->concepto }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-center overflow-x-auto px-2 py-1">
            <table class="vl-grid-pocos-campos vl-grid-angosta-wrap text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-neutral-700">Concepto</th>
                        <th class="px-3 py-2 text-left font-semibold text-neutral-700">Proveedor</th>
                        <th class="px-3 py-2 text-left font-semibold text-neutral-700">CUIT</th>
                        <th class="px-3 py-2 text-center font-semibold text-neutral-700 w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($proveedores as $proveedor)
                        <tr wire:key="proveedor-{{ $proveedor->id }}">
                            <td class="px-3 py-2">{{ $proveedor->concepto?->concepto ?? '—' }}</td>
                            <td class="px-3 py-2 font-medium">{{ $proveedor->proveedor }}</td>
                            <td class="px-3 py-2 tabular-nums">{{ $proveedor->cuit ? \App\Support\CuitInput::format((string) $proveedor->cuit) : '—' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="{{ route('tesoreria.proveedores.edit', $proveedor->id) }}"
                                       title="Editar proveedor"
                                       aria-label="Editar proveedor"
                                       class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <x-vl-grid-icon-btn title="Eliminar proveedor"
                                                        variant="danger"
                                                        wire:loading.attr="disabled"
                                                        x-on:click="window.vlSwalConfirmar('¿Eliminar este proveedor? Esta acción no se puede deshacer.', 'Eliminar proveedor', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $proveedor->id }}))">
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
                            <td colspan="4" class="px-3 py-4 text-center text-neutral-500">
                                No hay proveedores registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($proveedores->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $proveedores->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
