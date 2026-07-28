<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Administración</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Valores de referencia</h1>
                <p class="mt-2 max-w-2xl text-sm text-white/80">
                    Configure los rangos (mínimo / máximo) por ítem de informe, especie y sexo.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-rango-layout">
        {{-- ─── Panel izquierdo: especies ─────────────────────────── --}}
        <aside class="vl-card vl-det-grupo-master overflow-hidden">
            <div class="border-b border-accent-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-neutral-800">Especies</h2>
                <p class="mt-0.5 text-xs text-neutral-500">Seleccione una para ver sus rangos.</p>
            </div>
            <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
                <input wire:model.live="busquedaEspecie"
                       type="search"
                       placeholder="Buscar especie…"
                       class="form-input w-full py-1.5 text-sm">
            </div>
            <div class="vl-det-grupo-master-list">
                @forelse ($especies as $especie)
                    <button type="button"
                            wire:key="esp-{{ $especie->idEspecies }}"
                            wire:click="seleccionarEspecie({{ $especie->idEspecies }})"
                            class="vl-det-grupo-master-item {{ (int) $idEspeciesSeleccionada === (int) $especie->idEspecies ? 'is-active' : '' }}">
                        <span class="min-w-0 flex-1 truncate text-left font-medium">{{ $especie->nombre }}</span>
                        @if (($conteosPorEspecie[$especie->idEspecies] ?? 0) > 0)
                            <span class="vl-pill shrink-0 text-[10px]">{{ $conteosPorEspecie[$especie->idEspecies] }}</span>
                        @endif
                    </button>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-neutral-500">No hay especies registradas.</p>
                @endforelse
            </div>
        </aside>

        {{-- ─── Panel derecho: detalle ────────────────────────────── --}}
        <section class="vl-card overflow-hidden">
            @if ($especieActiva)
                {{-- Encabezado del panel --}}
                <div class="flex flex-col gap-3 border-b border-accent-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">Rangos de referencia</p>
                        <h2 class="truncate text-lg font-bold text-neutral-900">{{ $especieActiva->nombre }}</h2>
                        <p class="text-xs text-neutral-500">
                            {{ count($filas) }} registro{{ count($filas) === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <button type="button"
                                class="btn-primary shrink-0"
                                wire:click="abrirForm">
                            Agregar
                        </button>
                    </div>
                </div>

                {{-- ─── Formulario alta / edición en bloque ─── --}}
                @if ($formVisible)
                    <div class="border-b border-accent-200 bg-accent-50/50 px-4 py-4">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-neutral-800">Agregar / editar rango</p>
                            <button type="button"
                                    wire:click="cerrarForm"
                                    class="rounded p-1 text-neutral-400 hover:bg-accent-100 hover:text-neutral-700"
                                    title="Cerrar formulario">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {{-- 1. Determinación / ítem --}}
                            <div class="sm:col-span-2 lg:col-span-1">
                                <label class="form-label mb-1" for="vl-rango-idItems">Ítem del informe *</label>
                                <select wire:model.live="formIdItems"
                                        id="vl-rango-idItems"
                                        class="form-input py-1.5 text-sm">
                                    <option value="">— Seleccione —</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->idItems }}">{{ $item->nombreItem }}</option>
                                    @endforeach
                                </select>
                                @error('formIdItems') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- 2. Especie (readonly, ya fijada por selección) --}}
                            <div>
                                <label class="form-label mb-1">Especie</label>
                                <input type="text"
                                       class="form-input py-1.5 text-sm vl-carga-input--readonly"
                                       value="{{ $especieActiva->nombre }}"
                                       readonly
                                       tabindex="-1">
                            </div>

                            {{-- 3. Min / Max --}}
                            <div class="flex gap-3">
                                <div class="flex-1">
                                    <label class="form-label mb-1" for="vl-rango-min">Mín. *</label>
                                    <input wire:model="formValorMin"
                                           id="vl-rango-min"
                                           type="text"
                                           inputmode="decimal"
                                           class="form-input py-1.5 text-sm"
                                           placeholder="0">
                                    @error('formValorMin') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex-1">
                                    <label class="form-label mb-1" for="vl-rango-max">Máx. *</label>
                                    <input wire:model="formValorMax"
                                           id="vl-rango-max"
                                           type="text"
                                           inputmode="decimal"
                                           class="form-input py-1.5 text-sm"
                                           placeholder="0">
                                    @error('formValorMax') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- 3. Sexos --}}
                        <div class="mt-3">
                            <div class="mb-1.5 flex items-center gap-3">
                                <label class="form-label">Sexos *</label>
                                <button type="button"
                                        wire:click="seleccionarTodosLosSexos"
                                        class="text-[11px] text-primary-600 hover:underline">
                                    Todos
                                </button>
                                <button type="button"
                                        wire:click="deseleccionarTodosLosSexos"
                                        class="text-[11px] text-neutral-500 hover:underline">
                                    Ninguno
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-x-5 gap-y-2">
                                @foreach ($sexosDisponibles as $sexo)
                                    <label class="flex cursor-pointer items-center gap-1.5 text-sm select-none">
                                        <input type="checkbox"
                                               wire:model="formSexos"
                                               value="{{ $sexo['id'] }}"
                                               class="rounded border-accent-300 text-primary-600">
                                        <span>{{ $sexo['nombre'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('formSexos') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <button type="button"
                                    class="btn-primary text-sm"
                                    wire:click="guardarForm"
                                    wire:loading.attr="disabled">
                                Guardar
                            </button>
                            <button type="button"
                                    class="btn-secondary text-sm"
                                    wire:click="cerrarForm">
                                Cancelar
                            </button>
                            <p class="text-[11px] text-neutral-500">
                                Si el ítem+sexo ya existe, se actualizará. Si el sexo se desmarca, se eliminará.
                            </p>
                        </div>
                    </div>
                @endif

                {{-- ─── Grilla de rangos ─── --}}
                @php
                    // Agrupar filas por idItems para mostrar encabezado por ítem.
                    $filasPorItem = [];
                    foreach ($filas as $id => $fila) {
                        $filasPorItem[$fila['idItems']][] = ['id' => $id] + $fila;
                    }
                @endphp
                <div class="vl-rango-grid-wrap">
                    <table class="vl-rango-grid text-sm">
                        <thead class="bg-accent-50/80">
                            <tr>
                                <th class="vl-rango-th vl-rango-col--acciones" title="Acciones"></th>
                                <th class="vl-rango-th vl-rango-col--item">Ítem del informe</th>
                                <th class="vl-rango-th vl-rango-col--sexo">Sexo</th>
                                <th class="vl-rango-th vl-rango-col--valor">Mínimo</th>
                                <th class="vl-rango-th vl-rango-col--valor">Máximo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($filasPorItem as $idItemsGrupo => $filasGrupo)
                                {{-- Encabezado de grupo por ítem --}}
                                <tr class="vl-rango-grupo-header" wire:key="grupo-{{ $idItemsGrupo }}">
                                    <td class="vl-rango-grupo-th vl-rango-col--acciones">
                                        <button type="button"
                                                class="vl-rango-grupo-del-btn"
                                                title="Borrar todas las variantes de {{ $filasGrupo[0]['nombreItem'] }}"
                                                x-on:click="window.vlSwalConfirmar(
                                                    'Se eliminarán todas las variantes de {{ addslashes($filasGrupo[0]['nombreItem']) }} para esta especie.',
                                                    'Borrar ítem completo',
                                                    { confirmButtonText: 'Sí, eliminar', icon: 'warning' }
                                                ).then(ok => ok && $wire.eliminarItemDeEspecie({{ $idItemsGrupo }}))">
                                            Borrar todas las variantes
                                        </button>
                                    </td>
                                    <td colspan="4" class="vl-rango-grupo-th">
                                        {{ $filasGrupo[0]['nombreItem'] }}
                                    </td>
                                </tr>
                                {{-- Filas del grupo --}}
                                @foreach ($filasGrupo as $fila)
                                    @php $id = $fila['id']; @endphp
                                    <tr class="vl-rango-row divide-y divide-accent-100" wire:key="rv-{{ $id }}">
                                        <td class="vl-rango-td vl-rango-col--acciones">
                                            <div class="flex items-center justify-center gap-0.5">
                                                <x-vl-grid-icon-btn title="Eliminar"
                                                                    variant="danger"
                                                                    x-on:click="window.vlSwalConfirmar('¿Eliminar este rango de referencia?', 'Eliminar', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminarFila({{ $id }}))">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </x-vl-grid-icon-btn>
                                            </div>
                                        </td>
                                        <td class="vl-rango-td vl-rango-col--item">
                                            {{-- vacío: el nombre ya está en el encabezado de grupo --}}
                                        </td>
                                        <td class="vl-rango-td vl-rango-col--sexo">
                                            <span class="text-neutral-600">{{ $fila['nombreSexo'] }}</span>
                                        </td>
                                        <td class="vl-rango-td vl-rango-col--valor">
                                            <input type="text"
                                                   wire:model.blur="filas.{{ $id }}.valorMin"
                                                   wire:blur="guardarFila({{ $id }})"
                                                   inputmode="decimal"
                                                   class="vl-rango-input"
                                                   placeholder="—">
                                        </td>
                                        <td class="vl-rango-td vl-rango-col--valor">
                                            <input type="text"
                                                   wire:model.blur="filas.{{ $id }}.valorMax"
                                                   wire:blur="guardarFila({{ $id }})"
                                                   inputmode="decimal"
                                                   class="vl-rango-input"
                                                   placeholder="—">
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="vl-rango-td py-10 text-center text-neutral-500">
                                        <p class="font-medium">Esta especie aún no tiene rangos configurados.</p>
                                        <p class="mt-1 text-xs">Use <strong>Agregar</strong> para cargar los valores de referencia.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            @else
                <div class="flex h-64 items-center justify-center">
                    <p class="text-sm text-neutral-500">Seleccione una especie del panel izquierdo.</p>
                </div>
            @endif
        </section>
    </div>
</div>
