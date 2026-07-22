<div class="vl-page"
     x-data="{
        init() {
            this._focusTipoPendiente = false;
            this._onEnterCapture = (event) => {
                if (event.key !== 'Enter') {
                    return;
                }
                if (document.querySelector('.swal2-container')) {
                    return;
                }
                const input = document.getElementById('vl-prot-det-select-tipo');
                const idSeleccionado = input?.dataset?.selectedId || '';
                if (!input || !idSeleccionado) {
                    return;
                }
                const active = document.activeElement;
                // El combobox maneja Enter en su propio keydown.
                if (active === input) {
                    return;
                }
                if (active) {
                    const tag = active.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA') {
                        return;
                    }
                    if (tag === 'SELECT') {
                        return;
                    }
                    if (active.isContentEditable) {
                        return;
                    }
                }
                event.preventDefault();
                event.stopPropagation();
                $wire.confirmarNueva(idSeleccionado);
            };
            document.addEventListener('keydown', this._onEnterCapture, true);
            this._onMorphUpdated = () => {
                if (this._focusTipoPendiente) {
                    this.intentarEnfocarTipo(12);
                }
            };
            if (window.Livewire && typeof Livewire.hook === 'function') {
                this._unhookMorph = Livewire.hook('morph.updated', this._onMorphUpdated);
            }
            this.$el.addEventListener('alpine:destroying', () => {
                document.removeEventListener('keydown', this._onEnterCapture, true);
                if (typeof this._unhookMorph === 'function') {
                    this._unhookMorph();
                }
            });
        },
        camposNav() {
            return Array.from(this.$el.querySelectorAll('.vl-prot-det-nav'));
        },
        enfocarCampo(el) {
            if (! el) {
                return;
            }
            el.focus({ preventScroll: true });
            el.select();
            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        },
        caretAlInicio(el) {
            const start = el.selectionStart ?? 0;
            const end = el.selectionEnd ?? 0;
            return start === 0 && end === 0;
        },
        caretAlFinal(el) {
            const len = String(el.value ?? '').length;
            const start = el.selectionStart ?? 0;
            const end = el.selectionEnd ?? 0;
            return start === len && end === len;
        },
        todoSeleccionado(el) {
            const len = String(el.value ?? '').length;
            const start = el.selectionStart ?? 0;
            const end = el.selectionEnd ?? 0;
            return len === 0 || (start === 0 && end === len);
        },
        navegarCampos(event) {
            const keys = ['Enter', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
            if (! keys.includes(event.key)) {
                return;
            }

            const actual = event.target;
            if (! actual || ! actual.classList.contains('vl-prot-det-nav')) {
                return;
            }

            const campos = this.camposNav();
            const idx = campos.indexOf(actual);
            if (idx < 0) {
                return;
            }

            const col = actual.dataset.navCol || '';
            let destino = null;

            if (event.key === 'Enter' || event.key === 'ArrowRight') {
                if (event.key === 'ArrowRight' && ! this.todoSeleccionado(actual) && ! this.caretAlFinal(actual)) {
                    return;
                }
                destino = campos[idx + 1] || null;
            } else if (event.key === 'ArrowLeft') {
                if (! this.todoSeleccionado(actual) && ! this.caretAlInicio(actual)) {
                    return;
                }
                destino = campos[idx - 1] || null;
            } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                const paso = event.key === 'ArrowDown' ? 1 : -1;
                for (let i = idx + paso; i >= 0 && i < campos.length; i += paso) {
                    if ((campos[i].dataset.navCol || '') === col) {
                        destino = campos[i];
                        break;
                    }
                }
            }

            if (! destino) {
                return;
            }

            event.preventDefault();
            actual.blur();
            this.$nextTick(() => this.enfocarCampo(destino));
        },
        enfocarTipo() {
            this._focusTipoPendiente = true;
            this.intentarEnfocarTipo(30);
        },
        intentarEnfocarTipo(intentos) {
            const el = document.getElementById('vl-prot-det-select-tipo');
            if (el) {
                el.focus({ preventScroll: true });
                if (document.activeElement === el) {
                    try {
                        el.select();
                    } catch (e) {
                        // ignore
                    }
                    el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    this._focusTipoPendiente = false;
                    return;
                }
            }
            if (intentos <= 0) {
                this._focusTipoPendiente = false;
                return;
            }
            window.setTimeout(() => this.intentarEnfocarTipo(intentos - 1), 40);
        },
        onTeclado(event) {
            if (document.querySelector('.swal2-container')) {
                return;
            }
            if (event.key === 'F2' || event.key === 'Insert') {
                event.preventDefault();
                $wire.agregarDeterminacion();
                return;
            }
            if (event.key === 'Escape' && document.getElementById('vl-prot-det-select-tipo')) {
                // Si el listado del combobox está abierto, su propio handler cierra con stopPropagation.
                event.preventDefault();
                $wire.cancelarNueva();
            }
        }
     }"
     @keydown.window="onTeclado($event)"
     @vl-prot-det-focus-tipo.window="enfocarTipo()">
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
                       placeholder="Prefiltrar catálogo…"
                       class="form-input max-w-md flex-1">
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button"
                        wire:click="agregarDeterminacion"
                        @disabled($filaNueva !== null)
                        title="Agregar determinación (F2)"
                        class="btn-primary text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Agregar Determinación (F2)
                </button>
                <a href="{{ $urlVolver }}"
                   class="btn-secondary text-sm">
                    Volver
                </a>
            </div>
        </div>

        <p class="border-b border-accent-200 bg-accent-50/40 px-5 py-2 text-xs text-neutral-600">
            Puede cargar determinaciones solo con teclado:
            <strong class="font-semibold text-neutral-700">F2</strong> agregar,
            escriba para filtrar (queda marcada la primera),
            <strong class="font-semibold text-neutral-700">↑↓</strong> cambiar,
            <strong class="font-semibold text-neutral-700">Enter</strong> confirmar,
            <strong class="font-semibold text-neutral-700">Esc</strong> cancelar.
            En Neto/Descuento: <strong class="font-semibold text-neutral-700">Enter</strong> y <strong class="font-semibold text-neutral-700">flechas</strong> para navegar.
        </p>

        <div class="vl-prot-det-wrap" @keydown="navegarCampos($event)">
            <table class="vl-determinaciones-grid vl-prot-det-grid text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-determinaciones-th vl-determinaciones-col--acciones" title="Acciones"></th>
                        <th class="vl-determinaciones-th vl-prot-det-col--tipo">Tipo Determinaciones</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--neto" title="Precio de lista">Neto</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--descuento" title="Importe de descuento según % del cliente">Descuento</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--precio" title="Neto menos descuento">Precio (con descuento)</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--derivacion">Derivación</th>
                        @if ($tieneFechasDerivacion)
                            <th class="vl-determinaciones-th vl-prot-det-col--fecha-deriv" title="Fecha de envío a derivación">F. envío</th>
                            <th class="vl-determinaciones-th vl-prot-det-col--fecha-deriv" title="Fecha de devolución">F. devol.</th>
                        @endif
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
                            <td class="vl-determinaciones-td vl-prot-det-col--neto">
                                <input type="text"
                                       wire:model.blur="filas.{{ $id }}.neto"
                                       wire:blur="guardarFila({{ $id }})"
                                       class="vl-determinaciones-input vl-prot-det-input--precio vl-prot-det-nav"
                                       data-nav-col="neto"
                                       inputmode="decimal">
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--descuento">
                                <input type="text"
                                       wire:model.blur="filas.{{ $id }}.descuento"
                                       wire:blur="guardarFila({{ $id }})"
                                       class="vl-determinaciones-input vl-prot-det-input--precio vl-prot-det-nav"
                                       data-nav-col="descuento"
                                       inputmode="decimal">
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--precio">
                                <span class="text-xs tabular-nums text-neutral-800">{{ $fila['precio'] }}</span>
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
                            @if ($tieneFechasDerivacion)
                                <td class="vl-determinaciones-td vl-prot-det-col--fecha-deriv">
                                    <input type="date"
                                           wire:model="filas.{{ $id }}.fechaEnvioDeriv"
                                           wire:change="guardarFechaEnvioDeriv({{ $id }})"
                                           class="vl-determinaciones-input vl-prot-det-input--fecha"
                                           title="Fecha de envío">
                                </td>
                                <td class="vl-determinaciones-td vl-prot-det-col--fecha-deriv">
                                    <input type="date"
                                           wire:model="filas.{{ $id }}.fechaDevolucDeterm"
                                           wire:change="guardarFechaDevolucDeterm({{ $id }})"
                                           class="vl-determinaciones-input vl-prot-det-input--fecha"
                                           title="Fecha de devolución">
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    @if ($filaNueva !== null)
                        <tr class="vl-determinaciones-row vl-prot-det-row--nueva bg-accent-50/30" wire:key="det-nueva-{{ $filaNuevaSeq }}">
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
                                <div class="vl-prot-det-combobox"
                                     wire:key="det-nueva-combo-{{ $filaNuevaSeq }}"
                                     x-data="vlProtDetCombobox({
                                         opciones: @js($tiposParaCombobox),
                                         idInicial: @js((string) ($filaNueva['idTipodeterminaciones'] ?? '')),
                                         nombreInicial: @js($nombreTipoSeleccionado),
                                     })"
                                     @click.outside="cerrar()">
                                    <input id="vl-prot-det-select-tipo"
                                           x-ref="input"
                                           type="text"
                                           class="vl-determinaciones-input vl-prot-det-combobox-input"
                                           placeholder="Escriba para buscar…"
                                           autocomplete="off"
                                           spellcheck="false"
                                           title="Escriba, use ↑↓ y Enter para confirmar"
                                           x-model="consulta"
                                           @focus="abrir()"
                                           @input="onInput()"
                                           @keydown="onKeydown($event)">
                                    <ul x-ref="lista"
                                        class="vl-prot-det-combobox-lista"
                                        x-show="abierto"
                                        x-cloak
                                        role="listbox">
                                        <template x-for="(item, index) in filtrados" :key="item.id">
                                            <li role="option"
                                                :data-combo-idx="index"
                                                class="vl-prot-det-combobox-item"
                                                :class="indice === index ? 'is-active' : ''"
                                                :aria-selected="indice === index"
                                                @mouseenter="indice = index"
                                                @mousedown.prevent="elegirClick(item)"
                                                x-text="item.nombre"></li>
                                        </template>
                                        <li x-show="filtrados.length === 0"
                                            class="vl-prot-det-combobox-vacio"
                                            x-text="consulta.trim() ? 'Sin coincidencias' : 'Sin determinaciones disponibles'"></li>
                                    </ul>
                                </div>
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--neto">
                                <input type="text"
                                       wire:model.live="filaNueva.neto"
                                       class="vl-determinaciones-input vl-prot-det-input--precio vl-prot-det-nav"
                                       data-nav-col="neto"
                                       inputmode="decimal">
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--descuento">
                                <input type="text"
                                       wire:model.live="filaNueva.descuento"
                                       class="vl-determinaciones-input vl-prot-det-input--precio vl-prot-det-nav"
                                       data-nav-col="descuento"
                                       inputmode="decimal">
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--precio">
                                <span class="text-xs tabular-nums text-neutral-800">{{ $filaNueva['precio'] ?: '—' }}</span>
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-col--derivacion">
                                @if ($derivacionEsCatalogo)
                                    <select wire:model.live="filaNueva.idDerivaciones"
                                            class="vl-determinaciones-select vl-prot-det-select--derivacion">
                                        <option value="0">Seleccione</option>
                                        @foreach ($centrosDerivacion as $centro)
                                            <option value="{{ $centro->idDerivaciones }}">{{ $centro->derivacion }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select wire:model.live="filaNueva.idDerivaciones"
                                            class="vl-determinaciones-select vl-prot-det-select--derivacion">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                @endif
                            </td>
                            @if ($tieneFechasDerivacion)
                                <td class="vl-determinaciones-td vl-prot-det-col--fecha-deriv">
                                    <input type="date"
                                           wire:model="filaNueva.fechaEnvioDeriv"
                                           class="vl-determinaciones-input vl-prot-det-input--fecha"
                                           title="Fecha de envío">
                                </td>
                                <td class="vl-determinaciones-td vl-prot-det-col--fecha-deriv">
                                    <input type="date"
                                           wire:model="filaNueva.fechaDevolucDeterm"
                                           class="vl-determinaciones-input vl-prot-det-input--fecha"
                                           title="Fecha de devolución">
                                </td>
                            @endif
                        </tr>
                    @endif

                    @if (count($filas) === 0 && $filaNueva === null)
                        <tr>
                            <td colspan="{{ $tieneFechasDerivacion ? 8 : 6 }}" class="vl-determinaciones-td text-center text-neutral-500 py-10">
                                No hay determinaciones solicitadas. Presione <strong>Agregar Determinación</strong> o <strong>F2</strong> para comenzar.
                            </td>
                        </tr>
                    @endif
                </tbody>
                @if (count($filas) > 0 || $filaNueva !== null)
                    <tfoot class="border-t border-accent-200 bg-accent-50/50">
                        <tr>
                            <td colspan="4" class="vl-determinaciones-td vl-prot-det-footer-label text-right text-xs font-semibold text-neutral-600 py-2">
                                Total protocolo
                            </td>
                            <td class="vl-determinaciones-td vl-prot-det-footer-total text-xs font-bold text-neutral-900 tabular-nums py-2">
                                {{ $totalProtocolo }}
                            </td>
                            <td class="vl-determinaciones-td"></td>
                            @if ($tieneFechasDerivacion)
                                <td class="vl-determinaciones-td"></td>
                                <td class="vl-determinaciones-td"></td>
                            @endif
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
