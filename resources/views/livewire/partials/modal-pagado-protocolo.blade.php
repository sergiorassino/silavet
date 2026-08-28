    @if ($modalPagadoProtocoloAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalPagadoProtocolo">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalPagadoProtocolo"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-sm flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-pagado-protocolo-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-pagado-protocolo-titulo" class="text-lg font-bold text-neutral-900">
                            Importe pagado
                        </h3>
                    </div>

                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-4">
                        <div class="vl-form--compact space-y-3">
                            <div class="vl-form-field">
                                <label class="form-label" for="pagadoProtocoloImporte">Importe</label>
                                <input wire:model="pagadoProtocoloImporte"
                                       id="pagadoProtocoloImporte"
                                       type="text"
                                       inputmode="decimal"
                                       class="form-input"
                                       placeholder="0,00"
                                       autocomplete="off">
                                @error('pagadoProtocoloImporte') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="vl-form-field">
                                <label class="form-label" for="pagadoProtocoloIdMediodepago">Medio de pago</label>
                                <select wire:model="pagadoProtocoloIdMediodepago"
                                        id="pagadoProtocoloIdMediodepago"
                                        class="form-select">
                                    <option value="">Seleccionar</option>
                                    @foreach ($mediosPagoPagadoProtocolo as $medio)
                                        <option value="{{ $medio->id }}">{{ $medio->nombreMedioPago }}</option>
                                    @endforeach
                                </select>
                                @error('pagadoProtocoloIdMediodepago') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalPagadoProtocolo"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Volver
                        </button>
                        <button type="button"
                                wire:click="guardarPagadoProtocolo"
                                wire:loading.attr="disabled"
                                class="btn-primary">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
