<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Administración</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Gestión Determinaciones</h1>
                <p class="mt-2 text-sm text-white/80">Tipos de análisis, precios y parámetros de perfil / derivación.</p>
            </x-vl-hero-heading>
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

    @if ($derivacionEsCatalogo && ! $tieneColumnaDerivacion)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            El centro de derivación predeterminado se guarda en la columna <strong>derivacion</strong>,
            que no existe en esta base. Ejecute el script SQL de migración antes de usarla.
        </div>
    @endif

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
                        <th class="vl-determinaciones-th vl-determinaciones-col--orden" aria-sort="{{ $ordenarPor === 'orden' ? ($direccionOrden === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button"
                                    wire:click="ordenar('orden')"
                                    class="inline-flex items-center justify-center gap-1 border-0 bg-transparent p-0 hover:text-primary-700"
                                    title="Ordenar por orden">
                                Orden
                                @if ($ordenarPor === 'orden')
                                    <span class="text-xs tabular-nums" aria-hidden="true">{{ $direccionOrden === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="vl-determinaciones-th vl-determinaciones-col--nombre" aria-sort="{{ $ordenarPor === 'nombre' ? ($direccionOrden === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button"
                                    wire:click="ordenar('nombre')"
                                    class="inline-flex items-center gap-1 border-0 bg-transparent p-0 hover:text-primary-700"
                                    title="Ordenar por nombre">
                                Nombre de la determinación
                                @if ($ordenarPor === 'nombre')
                                    <span class="text-xs tabular-nums" aria-hidden="true">{{ $direccionOrden === 'asc' ? '▲' : '▼' }}</span>
                                @endif
                            </button>
                        </th>
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
                                </div>
                            </td>
                            <td class="vl-determinaciones-td vl-determinaciones-col--orden">
                                <input type="text"
                                       wire:model.blur="filas.{{ $id }}.orden"
                                       wire:blur="guardarFila({{ $id }})"
                                       class="vl-determinaciones-input vl-determinaciones-input--orden"
                                       inputmode="numeric">
                            </td>
                            <td class="vl-determinaciones-td vl-determinaciones-col--nombre">
                                <input type="text"
                                       wire:model.blur="filas.{{ $id }}.nombre"
                                       wire:blur="guardarFila({{ $id }})"
                                       class="vl-determinaciones-input vl-determinaciones-input--nombre"
                                       maxlength="50">
                            </td>
                            <td class="vl-determinaciones-td vl-determinaciones-col--precio">
                                <input type="text"
                                       wire:model.blur="filas.{{ $id }}.precio"
                                       wire:blur="guardarFila({{ $id }})"
                                       class="vl-determinaciones-input vl-determinaciones-input--precio"
                                       inputmode="decimal">
                            </td>
                            @if ($tienePrecioExtra)
                                <td class="vl-determinaciones-td vl-determinaciones-col--precio">
                                    <input type="text"
                                           wire:model.blur="filas.{{ $id }}.precio2"
                                           wire:blur="guardarFila({{ $id }})"
                                           class="vl-determinaciones-input vl-determinaciones-input--precio"
                                           inputmode="decimal">
                                </td>
                                <td class="vl-determinaciones-td vl-determinaciones-col--precio">
                                    <input type="text"
                                           wire:model.blur="filas.{{ $id }}.precio3"
                                           wire:blur="guardarFila({{ $id }})"
                                           class="vl-determinaciones-input vl-determinaciones-input--precio"
                                           inputmode="decimal">
                                </td>
                            @endif
                            @if ($mostrarColumnaPerfil)
                                <td class="vl-determinaciones-td vl-determinaciones-col--bool">
                                    <select wire:model="filas.{{ $id }}.perfil"
                                            wire:change="guardarFila({{ $id }})"
                                            class="vl-determinaciones-select">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </td>
                            @endif
                            <td class="vl-determinaciones-td vl-determinaciones-col--derivacion">
                                @if ($derivacionEsCatalogo)
                                    <select wire:model="filas.{{ $id }}.destino"
                                            wire:change="guardarFila({{ $id }})"
                                            class="vl-determinaciones-select vl-determinaciones-select--catalogo">
                                        <option value="0">No</option>
                                        @foreach ($centrosDerivacion as $centro)
                                            <option value="{{ $centro->idDerivaciones }}">{{ $centro->derivacion }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select wire:model="filas.{{ $id }}.destino"
                                            wire:change="guardarFila({{ $id }})"
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
