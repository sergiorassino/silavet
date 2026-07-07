<div class="vl-page">
    <div class="vl-prot-det-header mb-4">
        <div class="vl-prot-det-header-inner">
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Veterinaria:</span>
                <span class="vl-prot-det-header-value">{{ $paciente->cliente?->nombre ?: '—' }}</span>
            </div>
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Paciente:</span>
                <span class="vl-prot-det-header-value">{{ $paciente->nombre ?: '—' }}</span>
            </div>
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Protocolo:</span>
                <span class="vl-prot-det-header-value">{{ $paciente->nombreProtocolo ?: '—' }}</span>
            </div>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 items-center gap-2 min-w-0">
                <label for="busqueda-rapida-det" class="text-xs font-semibold text-neutral-600 whitespace-nowrap">Búsqueda Rápida:</label>
                <input id="busqueda-rapida-det"
                       wire:model.live.debounce.300ms="busquedaRapida"
                       type="search"
                       placeholder="Filtrar determinaciones…"
                       class="form-input max-w-md flex-1">
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button"
                        wire:click="agregarDeterminacion"
                        @disabled($filaNueva !== null)
                        class="btn-primary text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Agregar Determinación
                </button>
                <a href="{{ route('protocolos.index') }}"
                   class="btn-secondary text-sm">
                    Volver
                </a>
            </div>
        </div>

        <div class="vl-prot-det-wrap">
            <table class="vl-determinaciones-grid vl-prot-det-grid text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-determinaciones-th vl-determinaciones-col--acciones" title="Acciones"></th>
                        <th class="vl-determinaciones-th vl-prot-det-col--tipo">Tipo Determinaciones</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--precio">Precio</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--descuento">Descuento</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--derivacion">Derivación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @foreach ($filas as $id => $fila)
                        <tr class="vl-determinaciones-row" wire:key="det-saved-{{ $id }}">
                            <td class="vl-determinaciones-td vl-determinaciones-col--acciones">
                                <div class="flex items-center justify-center">
                                    <button type="button"
                                            title="Eliminar"
                                            aria-label="Eliminar determinación"
                                            class="vl-grid-icon-btn text-red-600 hover:bg-red-50"
                                            x-on:click="window.vlSwalConfirmar('¿Eliminar esta determinación del protocolo?', 'Eliminar determinación', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $id }}))">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--tipo">
                                <span class="text-xs font-medium text-neutral-900">{{ $fila['nombre'] }}</span>
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--precio">
                                <input type="text"
                                       wire:model.blur="filas.{{ $id }}.precio"
                                       wire:blur="guardarFila({{ $id }})"
                                       class="vl-determinaciones-input vl-prot-det-input--precio"
                                       inputmode="decimal">
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--descuento">
                                <span class="text-xs tabular-nums text-neutral-700">{{ $fila['descuento'] }}</span>
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--derivacion">
                                @if ($derivacionEsCatalogo)
                                    <select wire:model="filas.{{ $id }}.idDerivaciones"
                                            wire:change="guardarDerivacion({{ $id }})"
                                            class="vl-determinaciones-select vl-prot-det-select--derivacion">
                                        <option value="0">Seleccione</option>
                                        @foreach ($centrosDerivacion as $centro)
                                            <option value="{{ $centro->idDerivaciones }}">{{ $centro->derivacion }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select wire:model="filas.{{ $id }}.idDerivaciones"
                                            wire:change="guardarDerivacion({{ $id }})"
                                            class="vl-determinaciones-select vl-prot-det-select--derivacion">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if ($filaNueva !== null)
                        <tr class="vl-determinaciones-row vl-prot-det-row--nueva bg-accent-50/30" wire:key="det-nueva">
                            <td class="vl-determinaciones-td vl-determinaciones-col--acciones">
                                <div class="flex items-center justify-center gap-0.5">
                                    <button type="button"
                                            title="Confirmar"
                                            aria-label="Confirmar determinación"
                                            wire:click="confirmarNueva"
                                            class="vl-grid-icon-btn text-emerald-600 hover:bg-emerald-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    <button type="button"
                                            title="Cancelar"
                                            aria-label="Cancelar"
                                            wire:click="cancelarNueva"
                                            class="vl-grid-icon-btn text-neutral-600 hover:bg-neutral-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--tipo">
                                <select wire:model.live="filaNueva.idTipodeterminaciones"
                                        class="vl-determinaciones-select vl-prot-det-select--tipo">
                                    <option value="">Seleccione</option>
                                    @foreach ($tiposDisponibles as $tipo)
                                        @if (! in_array((int) $tipo->idTipodeterminaciones, $idsCargados, true))
                                            <option value="{{ $tipo->idTipodeterminaciones }}">{{ $tipo->nombre }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--precio">
                                <input type="text"
                                       wire:model="filaNueva.precio"
                                       class="vl-determinaciones-input vl-prot-det-input--precio"
                                       inputmode="decimal">
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--descuento">
                                <span class="text-xs tabular-nums text-neutral-700">{{ $filaNueva['descuento'] ?: '—' }}</span>
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--derivacion">
                                @if ($derivacionEsCatalogo)
                                    <select wire:model="filaNueva.idDerivaciones"
                                            class="vl-determinaciones-select vl-prot-det-select--derivacion">
                                        <option value="0">Seleccione</option>
                                        @foreach ($centrosDerivacion as $centro)
                                            <option value="{{ $centro->idDerivaciones }}">{{ $centro->derivacion }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select wire:model="filaNueva.idDerivaciones"
                                            class="vl-determinaciones-select vl-prot-det-select--derivacion">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                @endif
                            </td>
                        </tr>
                    @endif

                    @if (count($filas) === 0 && $filaNueva === null)
                        <tr>
                            <td colspan="5" class="vl-determinaciones-td text-center text-neutral-500 py-10">
                                No hay determinaciones solicitadas. Presione <strong>Agregar Determinación</strong> para comenzar.
                            </td>
                        </tr>
                    @endif
                </tbody>
                @if (count($filas) > 0 || $filaNueva !== null)
                    <tfoot class="border-t border-accent-200 bg-accent-50/50">
                        <tr>
                            <td colspan="2" class="vl-determinaciones-td vl-prot-det-footer-label text-right text-xs font-semibold text-neutral-600 py-2">
                                Total protocolo
                            </td>
                            <td colspan="3" class="vl-determinaciones-td vl-prot-det-footer-total text-xs font-bold text-neutral-900 tabular-nums py-2">
                                {{ $totalProtocolo }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
