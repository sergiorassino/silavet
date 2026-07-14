    @if ($modalEnvioAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalEnvio">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalEnvio"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-envio-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-envio-titulo" class="text-lg font-bold text-neutral-900">Enviar informe</h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Protocolo <strong>{{ $envioProtocolo }}</strong>
                            · {{ $envioNombrePaciente }}
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-3">
                        <div class="vl-form--compact space-y-2">
                            <section class="rounded-lg border border-accent-200 bg-accent-50/40 px-3 py-2">
                                <div class="flex min-w-0 items-baseline gap-2">
                                    <h4 class="shrink-0 text-[10px] font-bold uppercase tracking-[0.08em] text-primary-700">Cliente</h4>
                                    <p class="min-w-0 truncate text-xs font-medium text-neutral-900" title="{{ $envioClienteNombre }}">{{ $envioClienteNombre }}</p>
                                </div>
                                <div class="mt-1.5 grid grid-cols-[1fr_7.5rem] gap-1.5">
                                    <div class="vl-form-field">
                                        <label class="sr-only" for="envioClienteEmail">Email del cliente</label>
                                        <input wire:model.blur="envioClienteEmail"
                                               id="envioClienteEmail"
                                               type="email"
                                               maxlength="150"
                                               class="form-input"
                                               placeholder="Email"
                                               autocomplete="off">
                                        @error('envioClienteEmail') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="vl-form-field">
                                        <label class="sr-only" for="envioClienteWhatsapp">WhatsApp del cliente</label>
                                        <input wire:model.blur="envioClienteWhatsapp"
                                               id="envioClienteWhatsapp"
                                               type="text"
                                               maxlength="20"
                                               class="form-input"
                                               placeholder="WhatsApp"
                                               autocomplete="off">
                                        @error('envioClienteWhatsapp') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-accent-200 bg-accent-50/40 px-3 py-2">
                                <div class="flex min-w-0 items-baseline gap-2">
                                    <h4 class="shrink-0 text-[10px] font-bold uppercase tracking-[0.08em] text-primary-700">Paciente</h4>
                                    <p class="min-w-0 truncate text-xs font-medium text-neutral-900" title="{{ $envioNombrePaciente }}">{{ $envioNombrePaciente }}</p>
                                </div>
                                <div class="mt-1.5 grid grid-cols-[1fr_7.5rem] gap-1.5">
                                    <div class="vl-form-field">
                                        <label class="sr-only" for="envioPacienteEmail">Email del paciente</label>
                                        <input wire:model.blur="envioPacienteEmail"
                                               id="envioPacienteEmail"
                                               type="email"
                                               maxlength="150"
                                               class="form-input"
                                               placeholder="Email"
                                               autocomplete="off">
                                        @error('envioPacienteEmail') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="vl-form-field">
                                        <label class="sr-only" for="envioPacienteWhatsapp">WhatsApp del paciente</label>
                                        <input wire:model.blur="envioPacienteWhatsapp"
                                               id="envioPacienteWhatsapp"
                                               type="text"
                                               maxlength="20"
                                               class="form-input"
                                               placeholder="WhatsApp"
                                               autocomplete="off">
                                        @error('envioPacienteWhatsapp') <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="vl-form--compact grid gap-2 sm:grid-cols-2">
                            <div class="vl-form-field">
                                <label class="form-label" for="envioDestinatario">Destinatario</label>
                                <select wire:model="envioDestinatario" id="envioDestinatario" class="form-select">
                                    <option value="">Seleccionar</option>
                                    <option value="cliente">Cliente</option>
                                    <option value="paciente">Paciente</option>
                                </select>
                                @error('envioDestinatario') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="vl-form-field">
                                <label class="form-label" for="envioForma">Forma de envío</label>
                                <select wire:model="envioForma" id="envioForma" class="form-select">
                                    <option value="">Seleccionar</option>
                                    <option value="mail">Mail</option>
                                    <option value="whatsapp">WhatsApp</option>
                                </select>
                                @error('envioForma') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <p class="text-[11px] leading-snug text-neutral-500">
                            Mail: usa la cuenta de Parámetros del Sistema.
                            WhatsApp: abre WhatsApp Web.
                        </p>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalEnvio"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="confirmarEnvio"
                                wire:loading.attr="disabled"
                                wire:target="confirmarEnvio"
                                class="btn-primary rounded-xl px-4 py-2 text-sm">
                            <span wire:loading.remove wire:target="confirmarEnvio">Enviar</span>
                            <span wire:loading wire:target="confirmarEnvio">Enviando…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($modalEdInfAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalEdInf">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalEdInf"
                        aria-label="Cerrar"></button>
                <div class="vl-edinf-modal relative z-10 flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-edinf-titulo">
                    <div class="border-b border-accent-200 px-5 py-3">
                        <h3 id="modal-edinf-titulo" class="text-base font-bold text-neutral-900">Editar informe</h3>
                        <p class="mt-0.5 text-xs text-neutral-600">
                            Protocolo <strong>{{ $edInfProtocolo }}</strong>
                            · {{ $edInfNombrePaciente }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-neutral-500">
                            Visibilidad de los renglones en el informe (Sí = mostrar, No = ocultar).
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-2">
                        @if (count($edInfRenglones) === 0)
                            <p class="py-8 text-center text-sm text-neutral-500">
                                No hay renglones de informe para este protocolo.
                                Agregue determinaciones primero.
                            </p>
                        @else
                            <table class="vl-edinf-table">
                                <thead>
                                    <tr>
                                        <th class="vl-edinf-th vl-edinf-th--grupo">Grupo</th>
                                        <th class="vl-edinf-th">Ítem</th>
                                        <th class="vl-edinf-th vl-edinf-th--visible">Visible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $grupoActual = null; @endphp
                                    @foreach ($edInfRenglones as $renglon)
                                        @php
                                            $nombreItem = $renglon['nombreItem'] !== '' ? $renglon['nombreItem'] : '—';
                                            $oculto = (int) $renglon['mostrar'] !== 1;
                                        @endphp
                                        <tr class="vl-edinf-row {{ $oculto ? 'is-oculto' : '' }}">
                                            <td class="vl-edinf-td vl-edinf-td--grupo">
                                                @if ($grupoActual !== $renglon['idGrupos'])
                                                    @php $grupoActual = $renglon['idGrupos']; @endphp
                                                    <span class="vl-edinf-grupo" title="{{ $renglon['nombreGrupo'] ?: '—' }}">
                                                        {{ $renglon['nombreGrupo'] ?: '—' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="vl-edinf-td vl-edinf-td--item">
                                                <span class="vl-edinf-item" title="{{ $nombreItem }}">{{ $nombreItem }}</span>
                                            </td>
                                            <td class="vl-edinf-td vl-edinf-td--visible">
                                                <label class="sr-only" for="edinf-mostrar-{{ $renglon['idRenglones'] }}">
                                                    Visibilidad de {{ $nombreItem }}
                                                </label>
                                                <select id="edinf-mostrar-{{ $renglon['idRenglones'] }}"
                                                        class="vl-edinf-select"
                                                        wire:change="setMostrarRenglon({{ $renglon['idRenglones'] }}, $event.target.value)"
                                                        wire:loading.attr="disabled">
                                                    <option value="1" @selected(! $oculto)>Sí</option>
                                                    <option value="0" @selected($oculto)>No</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-accent-200 px-5 py-2.5">
                        <button type="button"
                                wire:click="cerrarModalEdInf"
                                class="rounded-lg border border-accent-200 px-3.5 py-1.5 text-xs font-medium text-neutral-700 hover:bg-accent-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($modalObsAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalObs">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalObs"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-obs-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-obs-titulo" class="text-lg font-bold text-neutral-900">Observaciones</h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Protocolo <strong>{{ $obsProtocolo }}</strong>
                            · {{ $obsNombrePaciente }}
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <div class="vl-form-field">
                            <label class="form-label" for="obsTexto">Observaciones del protocolo</label>
                            <textarea wire:model="obsTexto"
                                      id="obsTexto"
                                      rows="8"
                                      class="form-input"
                                      placeholder="Escriba las observaciones…"></textarea>
                            @error('obsTexto') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalObs"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="guardarObservaciones"
                                wire:loading.attr="disabled"
                                wire:target="guardarObservaciones"
                                class="btn-primary rounded-xl px-4 py-2 text-sm">
                            <span wire:loading.remove wire:target="guardarObservaciones">Guardar</span>
                            <span wire:loading wire:target="guardarObservaciones">Guardando…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($modalAdjuntoAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalAdjunto">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalAdjunto"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-adjunto-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-adjunto-titulo" class="text-lg font-bold text-neutral-900">Adjunto PDF</h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Protocolo <strong>{{ $adjuntoProtocolo }}</strong>
                            · {{ $adjuntoNombrePaciente }}
                        </p>
                        <p class="mt-1 text-[11px] text-neutral-500">
                            El PDF se agrega al final del informe, con todas sus páginas.
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                        @if ($adjuntoNombreActual !== '')
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-[0.08em] text-emerald-800">Archivo actual</p>
                                <p class="mt-1 break-all text-sm font-medium text-neutral-900">{{ $adjuntoNombreActual }}</p>
                            </div>
                            <p class="text-sm text-neutral-600">
                                Para subir otro PDF, primero eliminá el actual. Al eliminarlo se borra también del servidor.
                            </p>
                        @else
                            <p class="rounded-lg border border-dashed border-accent-200 bg-accent-50/40 px-3 py-3 text-sm text-neutral-600">
                                Este protocolo aún no tiene un PDF adjunto.
                            </p>

                            <div class="vl-form-field">
                                <label class="form-label" for="adjuntoArchivo">Seleccionar PDF</label>
                                <input wire:model="adjuntoArchivo"
                                       id="adjuntoArchivo"
                                       type="file"
                                       accept=".pdf,application/pdf"
                                       class="form-input">
                                <p class="mt-1 text-[11px] text-neutral-500">Solo PDF · máximo 10 MB</p>
                                @error('adjuntoArchivo') <p class="form-error">{{ $message }}</p> @enderror
                                <div wire:loading wire:target="adjuntoArchivo" class="mt-1 text-xs text-primary-700">
                                    Cargando archivo…
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-accent-200 px-5 py-3">
                        <div>
                            @if ($adjuntoNombreActual !== '')
                                <button type="button"
                                        x-on:click="window.vlSwalConfirmar('¿Eliminar el PDF adjunto? Se borrará también del servidor.', 'Eliminar adjunto', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminarAdjunto())"
                                        wire:loading.attr="disabled"
                                        wire:target="eliminarAdjunto"
                                        class="rounded-xl border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                                    <span wire:loading.remove wire:target="eliminarAdjunto">Eliminar</span>
                                    <span wire:loading wire:target="eliminarAdjunto">Eliminando…</span>
                                </button>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    wire:click="cerrarModalAdjunto"
                                    class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                                {{ $adjuntoNombreActual !== '' ? 'Cerrar' : 'Cancelar' }}
                            </button>
                            @if ($adjuntoNombreActual === '')
                                <button type="button"
                                        wire:click="guardarAdjunto"
                                        wire:loading.attr="disabled"
                                        wire:target="guardarAdjunto,adjuntoArchivo"
                                        class="btn-primary rounded-xl px-4 py-2 text-sm">
                                    <span wire:loading.remove wire:target="guardarAdjunto">Guardar</span>
                                    <span wire:loading wire:target="guardarAdjunto">Guardando…</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($modalAvisoAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalAviso">
                <div class="absolute inset-0 bg-neutral-900/50"
                     wire:click="cerrarModalAviso"
                     aria-hidden="true"></div>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-visible rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-aviso-titulo"
                     @click.stop>
                    <div class="shrink-0 border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-aviso-titulo" class="text-lg font-bold text-neutral-900">Aviso al cliente</h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Protocolo <strong>{{ $avisoProtocolo }}</strong>
                            · {{ $avisoNombrePaciente }}
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 overflow-visible px-5 py-4">
                        <x-vl-rich-text-editor
                            wire-property="avisoTexto"
                            :initial="$avisoTexto"
                            :max-length="255"
                            label="Texto del aviso"
                            placeholder="Escriba el aviso…"
                            toolbar-aria-label="Formato del aviso"
                            save-method="guardarAviso"
                        >
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-accent-200 pt-3">
                                <div>
                                    @if ($avisoIdNotificacion !== null)
                                        <button type="button"
                                                x-on:click="window.vlSwalConfirmar('¿Eliminar este aviso al cliente?', 'Eliminar aviso', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminarAviso())"
                                                wire:loading.attr="disabled"
                                                wire:target="eliminarAviso"
                                                class="rounded-xl border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                                            <span wire:loading.remove wire:target="eliminarAviso">Eliminar</span>
                                            <span wire:loading wire:target="eliminarAviso">Eliminando…</span>
                                        </button>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button"
                                            wire:click="cerrarModalAviso"
                                            class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                                        Cancelar
                                    </button>
                                    <button type="button"
                                            @click="guardar()"
                                            wire:loading.attr="disabled"
                                            wire:target="guardarAviso"
                                            class="btn-primary rounded-xl px-4 py-2 text-sm">
                                        <span wire:loading.remove wire:target="guardarAviso">Guardar</span>
                                        <span wire:loading wire:target="guardarAviso">Guardando…</span>
                                    </button>
                                </div>
                            </div>
                        </x-vl-rich-text-editor>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($modalIaAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalIa">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalIa"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-ia-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-ia-titulo" class="text-lg font-bold text-neutral-900">Asistente IA</h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Protocolo <strong>{{ $iaProtocolo }}</strong>
                            · {{ $iaNombrePaciente }}
                            · {{ $iaEspecie }}
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
                             role="note">
                            <p class="font-semibold text-amber-900">Aviso importante</p>
                            <p class="mt-1 leading-relaxed">
                                {{ \App\Support\Protocolos\DiagnosticoIaPromptBuilder::DISCLAIMER }}
                            </p>
                        </div>

                        <div class="vl-form-field">
                            <label class="form-label" for="iaClinica">Síntomas clínicos</label>
                            <p class="mb-2 text-xs text-neutral-500">
                                Describí el cuadro clínico del paciente. Estos datos se guardan en el protocolo y se incluyen en el prompt enviado a ChatGPT junto con los resultados de laboratorio.
                            </p>
                            <textarea wire:model="iaClinica"
                                      id="iaClinica"
                                      rows="8"
                                      class="form-input"
                                      placeholder="Ej.: anorexia de 3 días, vómitos intermitentes, debilidad, mucosas pálidas…"></textarea>
                            @error('iaClinica') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalIa"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cerrar
                        </button>
                        <button type="button"
                                wire:click="guardarClinicaIa"
                                wire:loading.attr="disabled"
                                wire:target="guardarClinicaIa,consultarChatGpt"
                                class="rounded-xl border border-primary-200 px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-50">
                            <span wire:loading.remove wire:target="guardarClinicaIa">Guardar síntomas</span>
                            <span wire:loading wire:target="guardarClinicaIa">Guardando…</span>
                        </button>
                        <button type="button"
                                x-data
                                x-on:click="window.__vlIaChatWin = window.open('about:blank', '_blank')"
                                wire:click="consultarChatGpt"
                                wire:loading.attr="disabled"
                                wire:target="guardarClinicaIa,consultarChatGpt"
                                class="btn-primary rounded-xl px-4 py-2 text-sm">
                            <span wire:loading.remove wire:target="consultarChatGpt">Consultar ChatGPT</span>
                            <span wire:loading wire:target="consultarChatGpt">Preparando…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
