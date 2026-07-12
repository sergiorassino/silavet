<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="vl-eyebrow">Administración</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Gestión Determinaciones</h1>
                <p class="mt-2 text-sm text-white/80">Tipos de análisis, precios y parámetros de perfil / derivación.</p>
            </div>
            <button type="button"
                    wire:click="agregarFila"
                    class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">
                Nueva determinación
            </button>
        </div>
    </div>

    @unless ($tienePrecioExtra)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Las columnas <strong>Precio 2</strong> y <strong>Precio 3</strong> requieren actualizar la base de datos.
            Ejecute el script SQL de migración antes de usarlas.
        </div>
    @endunless

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por orden o nombre…"
                   class="form-input max-w-md">
        </div>

        <div class="overflow-x-auto">
            <table class="vl-determinaciones-grid text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-determinaciones-th vl-determinaciones-col--acciones" title="Acciones"></th>
                        <th class="vl-determinaciones-th vl-determinaciones-col--orden">Orden</th>
                        <th class="vl-determinaciones-th vl-determinaciones-col--nombre">Nombre de la determinación</th>
                        <th class="vl-determinaciones-th vl-determinaciones-col--precio">Precio</th>
                        @if ($tienePrecioExtra)
                            <th class="vl-determinaciones-th vl-determinaciones-col--precio">Precio 2</th>
                            <th class="vl-determinaciones-th vl-determinaciones-col--precio">Precio 3</th>
                        @endif
                        @if ($mostrarColumnaPerfil)
                            <th class="vl-determinaciones-th vl-determinaciones-col--bool">Perfil</th>
                        @endif
                        <th class="vl-determinaciones-th vl-determinaciones-col--derivacion">
                            {{ $derivacionEsCatalogo ? 'Centro de derivación' : 'Derivación' }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($idsVisibles as $id)
                        <tr class="vl-determinaciones-row" wire:key="tipodet-{{ $id }}">
                            <td class="vl-determinaciones-td vl-determinaciones-col--acciones">
                                <div class="flex items-center justify-center gap-0.5">
                                    <button type="button"
                                            title="Eliminar"
                                            aria-label="Eliminar determinación"
                                            class="vl-grid-icon-btn text-red-600 hover:bg-red-50"
                                            x-on:click="window.vlSwalConfirmar('¿Eliminar esta determinación? Esta acción no se puede deshacer.', 'Eliminar determinación', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $id }}))">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            title="Guardar fila"
                                            aria-label="Guardar fila"
                                            wire:click="guardarFila({{ $id }})"
                                            class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            title="Descartar cambios"
                                            aria-label="Descartar cambios"
                                            wire:click="descartarFila({{ $id }})"
                                            class="vl-grid-icon-btn text-neutral-600 hover:bg-neutral-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="vl-determinaciones-td vl-determinaciones-col--orden">
                                <input type="text"
                                       wire:model="filas.{{ $id }}.orden"
                                       class="vl-determinaciones-input vl-determinaciones-input--orden"
                                       inputmode="numeric">
                            </td>
                            <td class="vl-determinaciones-td vl-determinaciones-col--nombre">
                                <input type="text"
                                       wire:model="filas.{{ $id }}.nombre"
                                       class="vl-determinaciones-input vl-determinaciones-input--nombre"
                                       maxlength="50">
                            </td>
                            <td class="vl-determinaciones-td vl-determinaciones-col--precio">
                                <input type="text"
                                       wire:model="filas.{{ $id }}.precio"
                                       class="vl-determinaciones-input vl-determinaciones-input--precio"
                                       inputmode="decimal">
                            </td>
                            @if ($tienePrecioExtra)
                                <td class="vl-determinaciones-td vl-determinaciones-col--precio">
                                    <input type="text"
                                           wire:model="filas.{{ $id }}.precio2"
                                           class="vl-determinaciones-input vl-determinaciones-input--precio"
                                           inputmode="decimal">
                                </td>
                                <td class="vl-determinaciones-td vl-determinaciones-col--precio">
                                    <input type="text"
                                           wire:model="filas.{{ $id }}.precio3"
                                           class="vl-determinaciones-input vl-determinaciones-input--precio"
                                           inputmode="decimal">
                                </td>
                            @endif
                            @if ($mostrarColumnaPerfil)
                                <td class="vl-determinaciones-td vl-determinaciones-col--bool">
                                    <select wire:model="filas.{{ $id }}.perfil"
                                            class="vl-determinaciones-select">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </td>
                            @endif
                            <td class="vl-determinaciones-td vl-determinaciones-col--derivacion">
                                @if ($derivacionEsCatalogo)
                                    <select wire:model="filas.{{ $id }}.destino"
                                            class="vl-determinaciones-select vl-determinaciones-select--catalogo">
                                        <option value="0">No</option>
                                        @foreach ($centrosDerivacion as $centro)
                                            <option value="{{ $centro->idDerivaciones }}">{{ $centro->derivacion }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select wire:model="filas.{{ $id }}.destino"
                                            class="vl-determinaciones-select">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnasVisibles }}" class="vl-determinaciones-td text-center text-neutral-500 py-8">
                                No hay determinaciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
