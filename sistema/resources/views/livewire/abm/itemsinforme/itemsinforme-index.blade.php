<div class="vl-page vl-page--wide vl-matriz-list-fill"
     x-data="{
        enfocarFila(id) {
            this.$nextTick(() => {
                const row = document.getElementById('itemsinf-row-' + id);
                if (!row) {
                    return;
                }

                row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                row.classList.add('vl-ii-row-focus');
                row.focus({ preventScroll: true });

                window.setTimeout(() => row.classList.remove('vl-ii-row-focus'), 2500);
            });
        }
     }"
     @itemsinf-enfocar-fila.window="enfocarFila($event.detail.id)">
    <div class="vl-hero vl-hero--compact shrink-0">
        <div class="vl-hero-inner flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="vl-eyebrow">Administración</p>
                <h1 class="text-xl font-bold sm:text-2xl">Parametrización de Items</h1>
            </div>
            <button type="button"
                    wire:click="agregarItem"
                    class="btn-primary shrink-0 bg-white px-3 py-1.5 text-xs text-primary-700 hover:bg-accent-50">
                + Nuevo ítem
            </button>
        </div>
    </div>

    <div class="vl-card vl-matriz-list-card">
        <div class="vl-matriz-list-toolbar shrink-0">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar…"
                   class="form-input w-40 max-w-full">
            <select wire:model.live="filtroGrupo" class="form-input vl-ii-filtro-grupo max-w-full">
                <option value="">Todos los grupos</option>
                @foreach ($grupos as $grupo)
                    <option value="{{ $grupo->idGrupos }}">{{ $grupo->nombreGrupo }}</option>
                @endforeach
            </select>
        </div>

        <div class="vl-matriz-list-scroll">
            <table class="vl-matriz-grid vl-itemsinforme-matriz">
                <thead>
                    <tr>
                        <th class="vl-ii-col--acc" title="Acciones"></th>
                        <th class="vl-ii-col--id" title="ID del ítem">ID</th>
                        <th class="vl-ii-col--item" title="Nombre de la determinación">Nombre de la determinación</th>
                        <th class="vl-ii-col--grupo" title="Grupo">Grupo</th>
                        <th class="vl-ii-col--modo vl-ii-th-multiline"
                            title="MODO DE CARGA&#10;(establece qué tipo de control va a usarse para la carga de cada Item)">
                            <span class="block">MODO DE CARGA</span>
                            <span class="vl-ii-th-sub">(establece qué tipo de control va a usarse para la carga de cada Item)</span>
                        </th>
                        <th class="vl-ii-col--textos" title="Textos de cada línea del select, separados por #">Textos del select</th>
                        <th class="vl-ii-col--um" title="Unidad de medida valor 1">U1</th>
                        <th class="vl-ii-col--um" title="Unidad de medida valor 2">U2</th>
                        <th class="vl-ii-col--ref" title="Referencia caninos">Ref. caninos</th>
                        <th class="vl-ii-col--ref" title="Referencia felinos">Ref. felinos</th>
                        <th class="vl-ii-col--ref" title="Referencia equinos">Ref. equinos</th>
                        <th class="vl-ii-col--ref" title="Referencia porcinos">Ref. porcinos</th>
                        <th class="vl-ii-col--ref" title="Referencia bovinos">Ref. bovinos</th>
                        <th class="vl-ii-col--fmt" title="Formato del valor">Formato</th>
                        <th class="vl-ii-col--auto" title="Dispara automatización">Aut</th>
                        <th class="vl-ii-col--an" title="Código analizador">Anal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($idsVisibles as $id)
                        @php($fila = $filas[$id])
                        <tr wire:key="itemsinf-{{ $id }}"
                            id="itemsinf-row-{{ $id }}"
                            tabindex="-1"
                            class="outline-none">
                            <td class="vl-ii-col--acc">
                                <div class="flex items-center justify-center gap-px">
                                    <button type="button"
                                            title="Eliminar"
                                            aria-label="Eliminar ítem"
                                            class="vl-grid-icon-btn vl-grid-icon-btn--xs text-red-600 hover:bg-red-50"
                                            x-on:click="window.vlSwalConfirmar('¿Eliminar este ítem de informe? Esta acción no se puede deshacer.', 'Eliminar ítem', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $id }}))">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="vl-ii-col--id">
                                <span class="vl-grid-readonly tabular-nums text-neutral-600">{{ $fila['id_items'] }}</span>
                            </td>
                            <td class="vl-ii-col--item vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'nombre_item')"
                                title="Clic para editar nombre">
                                <span class="vl-grid-readonly">{{ $fila['nombre_item'] }}</span>
                            </td>
                            <td class="vl-ii-col--grupo vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'id_grupos')"
                                title="Clic para editar grupo">
                                <span class="vl-grid-readonly">{{ $fila['nombre_grupo'] }}</span>
                            </td>
                            <td class="vl-ii-col--modo vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'tipo_item')"
                                title="Clic para editar modo de carga">
                                <span class="vl-grid-readonly">
                                    {{ $fila['tipo_item'] !== '' ? ($modosCarga[(int) $fila['tipo_item']] ?? '—') : '' }}
                                </span>
                            </td>
                            <td class="vl-ii-col--textos vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'textos')"
                                title="Clic para editar textos del select">
                                <span class="vl-grid-readonly">{{ $fila['textos'] }}</span>
                            </td>
                            <td class="vl-ii-col--um vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'unidad_medida')"
                                title="Clic para editar unidad 1">
                                <span class="vl-grid-readonly">{{ $fila['unidad_medida'] }}</span>
                            </td>
                            <td class="vl-ii-col--um vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'unidad_medida2')"
                                title="Clic para editar unidad 2">
                                <span class="vl-grid-readonly">{{ $fila['unidad_medida2'] }}</span>
                            </td>
                            <td class="vl-ii-col--ref vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'ref_caninos')"
                                title="Clic para editar referencia caninos">
                                <span class="vl-grid-readonly">{{ $fila['ref_caninos'] }}</span>
                            </td>
                            <td class="vl-ii-col--ref vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'ref_felinos')"
                                title="Clic para editar referencia felinos">
                                <span class="vl-grid-readonly">{{ $fila['ref_felinos'] }}</span>
                            </td>
                            <td class="vl-ii-col--ref vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'ref_equinos')"
                                title="Clic para editar referencia equinos">
                                <span class="vl-grid-readonly">{{ $fila['ref_equinos'] }}</span>
                            </td>
                            <td class="vl-ii-col--ref vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'ref_porcinos')"
                                title="Clic para editar referencia porcinos">
                                <span class="vl-grid-readonly">{{ $fila['ref_porcinos'] }}</span>
                            </td>
                            <td class="vl-ii-col--ref vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'ref_bovinos')"
                                title="Clic para editar referencia bovinos">
                                <span class="vl-grid-readonly">{{ $fila['ref_bovinos'] }}</span>
                            </td>
                            <td class="vl-ii-col--fmt vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'estilo_num')"
                                title="Clic para editar formato">
                                <span class="vl-grid-readonly">
                                    {{ $fila['estilo_num'] !== '' ? \App\Support\Itemsinforme\ItemsinformeCatalog::etiquetaFormatoValor((int) $fila['estilo_num']) : '' }}
                                </span>
                            </td>
                            <td class="vl-ii-col--auto vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'actualiza')"
                                title="Clic para editar automatización">
                                <span class="vl-grid-readonly">
                                    {{ $fila['actualiza'] !== '' ? ($opcionesSiNo[(int) $fila['actualiza']] ?? '—') : '' }}
                                </span>
                            </td>
                            <td class="vl-ii-col--an vl-ii-col-editable"
                                wire:click="abrirEdicionCampo({{ $id }}, 'id_analizador')"
                                title="Clic para editar código analizador">
                                <span class="vl-grid-readonly uppercase">{{ $fila['id_analizador'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnasVisibles }}" class="px-2 py-6 text-center text-neutral-500">
                                No hay ítems de informe registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($modalCampoAbierto && $campoActual)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarEdicionCampo">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarEdicionCampo"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-campo-titulo">
                    <form wire:submit.prevent="guardarCampo">
                        <div class="border-b border-accent-200 px-5 py-4">
                            <h3 id="modal-campo-titulo" class="text-lg font-bold text-neutral-900">
                                {{ $campoActual['label'] }}
                            </h3>
                            @if ($editandoId && isset($filas[$editandoId]))
                                <p class="mt-1 text-sm text-neutral-500">
                                    Ítem #{{ $editandoId }} — {{ $filas[$editandoId]['nombre_item'] }}
                                </p>
                            @endif
                        </div>

                        <div class="px-5 py-4">
                            @switch($campoActual['tipo'])
                                @case('textarea')
                                    <label class="form-label" for="ii-campo-valor">{{ $campoActual['label'] }}</label>
                                    <textarea wire:model="valorCampo"
                                              id="ii-campo-valor"
                                              rows="4"
                                              maxlength="{{ $campoActual['max'] ?? 500 }}"
                                              class="form-input"
                                              autofocus></textarea>
                                    @break

                                @case('select_grupo')
                                    <label class="form-label" for="ii-campo-valor">{{ $campoActual['label'] }}</label>
                                    <select wire:model="valorCampo" id="ii-campo-valor" class="form-select" autofocus>
                                        <option value="">— Sin asignar —</option>
                                        @foreach ($grupos as $grupo)
                                            <option value="{{ $grupo->idGrupos }}">{{ $grupo->nombreGrupo }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('select_modo')
                                    <label class="form-label" for="ii-campo-valor">{{ $campoActual['label'] }}</label>
                                    <select wire:model="valorCampo" id="ii-campo-valor" class="form-select" autofocus>
                                        <option value="">— Sin asignar —</option>
                                        @foreach ($modosCarga as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('select_formato')
                                    <label class="form-label" for="ii-campo-valor">{{ $campoActual['label'] }}</label>
                                    <select wire:model="valorCampo" id="ii-campo-valor" class="form-select" autofocus>
                                        <option value="">— Sin asignar —</option>
                                        @foreach ($formatosValor as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @case('select_sino')
                                    <label class="form-label" for="ii-campo-valor">{{ $campoActual['label'] }}</label>
                                    <select wire:model="valorCampo" id="ii-campo-valor" class="form-select" autofocus>
                                        <option value="">— Sin asignar —</option>
                                        @foreach ($opcionesSiNo as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @break

                                @default
                                    <label class="form-label" for="ii-campo-valor">{{ $campoActual['label'] }}</label>
                                    <input wire:model="valorCampo"
                                           id="ii-campo-valor"
                                           type="text"
                                           maxlength="{{ $campoActual['max'] ?? 255 }}"
                                           @class(['form-input', 'uppercase' => $campoEditando === 'id_analizador'])
                                           autofocus>
                            @endswitch

                            @if (! empty($campoActual['hint']))
                                <p class="mt-2 text-xs text-neutral-500">{{ $campoActual['hint'] }}</p>
                            @endif

                            @error('valorCampo') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap justify-end gap-2 border-t border-accent-200 px-5 py-3">
                            <button type="button"
                                    wire:click="cerrarEdicionCampo"
                                    class="btn-secondary">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="btn-primary"
                                    wire:loading.attr="disabled"
                                    wire:target="guardarCampo">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endteleport
    @endif
</div>
