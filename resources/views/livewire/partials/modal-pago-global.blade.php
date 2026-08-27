    @if ($modalPagoGlobalAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalPagoGlobal">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalPagoGlobal"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-pago-global-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-pago-global-titulo" class="text-lg font-bold text-neutral-900">
                            {{ $pagoGlobalIdPacientes ? 'Editar pago global' : 'Pago global' }}
                        </h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            Registro de pago del cliente para el día
                            {{ $this->pagoGlobalFechaEtiqueta() }}.
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-4">
                        <div class="vl-form--compact space-y-3">
                            <div class="vl-form-field">
                                <label class="form-label" for="pagoGlobalIdClientes">Cliente</label>
                                <select wire:model="pagoGlobalIdClientes"
                                        id="pagoGlobalIdClientes"
                                        class="form-select"
                                        @disabled(! $this->pagoGlobalClienteEditable())>
                                    <option value="">Seleccionar</option>
                                    @foreach ($clientesPagoGlobal as $cliente)
                                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('pagoGlobalIdClientes') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="vl-form-field">
                                <label class="form-label" for="pagoGlobalImporte">Importe</label>
                                <input wire:model="pagoGlobalImporte"
                                       id="pagoGlobalImporte"
                                       type="text"
                                       inputmode="decimal"
                                       class="form-input"
                                       placeholder="0,00"
                                       autocomplete="off">
                                @error('pagoGlobalImporte') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="vl-form-field">
                                <label class="form-label" for="pagoGlobalIdMediodepago">Medio de pago</label>
                                <select wire:model="pagoGlobalIdMediodepago"
                                        id="pagoGlobalIdMediodepago"
                                        class="form-select">
                                    <option value="">Seleccionar</option>
                                    @foreach ($mediosPago as $medio)
                                        <option value="{{ $medio->id }}">{{ $medio->nombreMedioPago }}</option>
                                    @endforeach
                                </select>
                                @error('pagoGlobalIdMediodepago') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalPagoGlobal"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="guardarPagoGlobal"
                                wire:loading.attr="disabled"
                                class="btn-primary">
                            {{ $pagoGlobalIdPacientes ? 'Guardar cambios' : 'Guardar pago' }}
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
