<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-3">
        <div class="vl-hero-inner flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Movimientos</h1>
                <p class="mt-1 text-sm text-white/80">Ingresos y egresos de caja (tabla movimientos).</p>
            </x-vl-hero-heading>
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                <button type="button"
                        wire:click="abrirFormularioAsiento"
                        class="btn-secondary shrink-0 border-white/40 bg-white/15 text-white hover:bg-white/25">
                    Nuevo Asiento
                </button>
                <button type="button"
                        wire:click="abrirFormularioNuevo"
                        class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">
                    Nuevo movimiento
                </button>
            </div>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por cliente, cuenta, concepto, monto…"
                   class="form-input max-w-md text-xs">
        </div>

        <div class="overflow-x-auto">
            <table class="vl-movimientos-grid">
                <thead>
                    <tr>
                        <th class="vl-movimientos-th vl-movimientos-th--icon"></th>
                        <th class="vl-movimientos-th whitespace-nowrap">Fechhora</th>
                        <th class="vl-movimientos-th">Cuenta</th>
                        <th class="vl-movimientos-th whitespace-nowrap">Ingreso / Egreso</th>
                        <th class="vl-movimientos-th">Cliente</th>
                        <th class="vl-movimientos-th">Paciente</th>
                        <th class="vl-movimientos-th">Concepto</th>
                        <th class="vl-movimientos-th">Proveedores</th>
                        <th class="vl-movimientos-th">Comprobante</th>
                        <th class="vl-movimientos-th vl-movimientos-th--num">Monto</th>
                        <th class="vl-movimientos-th">Obs</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $mov)
                        @php
                            $filaCss = $mov->esEgreso()
                                ? 'vl-movimientos-row--egreso'
                                : 'vl-movimientos-row--ingreso';
                        @endphp
                        <tr class="vl-movimientos-row {{ $filaCss }}">
                            <td class="vl-movimientos-td vl-movimientos-td--icon">
                                <button type="button"
                                        wire:click="abrirFormularioEditar({{ $mov->id }})"
                                        class="vl-movimientos-btn-edit"
                                        title="Editar">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 18.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                            </td>
                            <td class="vl-movimientos-td whitespace-nowrap">
                                {{ $mov->fechhora?->format('d/m/Y H:i:s') ?? '—' }}
                            </td>
                            <td class="vl-movimientos-td">{{ $mov->cuenta?->nombreMedioPago ?: '—' }}</td>
                            <td class="vl-movimientos-td font-medium">
                                {{ $mov->tipoMovimiento?->tipoMovimiento ?: '—' }}
                            </td>
                            <td class="vl-movimientos-td">
                                {{ ((int) ($mov->idClientes ?? 0) > 0) ? ($mov->cliente?->nombre ?: '—') : '' }}
                            </td>
                            <td class="vl-movimientos-td whitespace-nowrap">{{ $mov->etiquetaPaciente() }}</td>
                            <td class="vl-movimientos-td">{{ $mov->concepto?->concepto ?: '—' }}</td>
                            <td class="vl-movimientos-td">
                                {{ ((int) ($mov->idProveedores ?? 0) > 0) ? ($mov->proveedor?->proveedor ?: '') : '' }}
                            </td>
                            <td class="vl-movimientos-td">{{ $mov->comprobante ?: '' }}</td>
                            <td class="vl-movimientos-td vl-movimientos-td--num whitespace-nowrap">
                                {{ $mov->montoFormateado() }}
                            </td>
                            <td class="vl-movimientos-td max-w-[12rem] truncate" title="{{ $mov->obs }}">
                                {{ $mov->obs ?: '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="vl-movimientos-td py-6 text-center text-neutral-500">
                                No hay movimientos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movimientos->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $movimientos->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>

    @if ($formAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cancelarFormulario">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cancelarFormulario"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-mov-caja-titulo">
                    <div class="border-b border-accent-200 px-5 py-3">
                        <h3 id="modal-mov-caja-titulo" class="text-lg font-bold text-neutral-900">
                            {{ $idMovimiento ? 'Editar movimiento' : 'Nuevo movimiento' }}
                        </h3>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <form wire:submit.prevent="guardar" class="vl-form--compact space-y-3">
                            <div class="vl-form-field">
                                <label class="form-label" for="fechaElegirProtocolos">Fecha de los Protocolos a Cargar</label>
                                <select wire:model.live="fechaElegirProtocolos" id="fechaElegirProtocolos" class="form-select">
                                    <option value="">Seleccione</option>
                                    @foreach ($fechasProtocolos as $fp)
                                        <option value="{{ $fp['valor'] }}">{{ $fp['etiqueta'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="idTipoMovimiento">Tipo Movimiento <span class="text-red-600">*</span></label>
                                <select wire:model.live="idTipoMovimiento" id="idTipoMovimiento" class="form-select">
                                    <option value="">Seleccione</option>
                                    @foreach ($tipos as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->tipoMovimiento }}</option>
                                    @endforeach
                                </select>
                                @error('idTipoMovimiento') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="idCuentas">Cuenta <span class="text-red-600">*</span></label>
                                <select wire:model="idCuentas" id="idCuentas" class="form-select">
                                    <option value="">Seleccione</option>
                                    @foreach ($cuentas as $cuenta)
                                        <option value="{{ $cuenta->id }}">{{ $cuenta->nombreMedioPago }}</option>
                                    @endforeach
                                </select>
                                @error('idCuentas') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="idConcepto">Concepto <span class="text-red-600">*</span></label>
                                <select wire:model.live="idConcepto" id="idConcepto" class="form-select" @disabled(! $idTipoMovimiento)>
                                    <option value="">Seleccione</option>
                                    @foreach ($conceptos as $concepto)
                                        <option value="{{ $concepto->id }}">{{ $concepto->concepto }}</option>
                                    @endforeach
                                </select>
                                @error('idConcepto') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            @if ($esEgreso)
                                <div class="vl-form-field">
                                    <label class="form-label" for="idProveedores">Proveedores</label>
                                    <select wire:model="idProveedores" id="idProveedores" class="form-select" @disabled(! $idConcepto)>
                                        <option value="">Seleccione</option>
                                        @foreach ($proveedores as $proveedor)
                                            <option value="{{ $proveedor->id }}">{{ $proveedor->proveedor }}</option>
                                        @endforeach
                                    </select>
                                    @error('idProveedores') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            @if ($mostrarPaciente)
                                <div class="vl-form-field">
                                    <label class="form-label" for="idPacientes">Paciente <span class="text-red-600">*</span></label>
                                    <select wire:model.live="idPacientes" id="idPacientes" class="form-select">
                                        <option value="">Seleccione</option>
                                        @foreach ($protocolosIngresos as $prot)
                                            <option value="{{ $prot->idPacientes }}">{{ $prot->etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @error('idPacientes') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            @if ($mostrarCadeteria)
                                <div class="vl-form-field">
                                    <label class="form-label" for="idCadete">Cadetería <span class="text-red-600">*</span></label>
                                    <select wire:model.live="idCadete" id="idCadete" class="form-select">
                                        <option value="">Seleccione</option>
                                        @foreach ($protocolosCadeteria as $prot)
                                            <option value="{{ $prot->idPacientes }}">{{ $prot->etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    @error('idCadete') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div class="vl-form-field">
                                <label class="form-label" for="monto">Monto <span class="text-red-600">*</span></label>
                                <input wire:model="monto" id="monto" type="text" inputmode="decimal"
                                       class="form-input" placeholder="0.00" autocomplete="off">
                                @error('monto') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="comprobante">Comprobante</label>
                                <input wire:model="comprobante" id="comprobante" type="text" class="form-input" maxlength="20">
                                @error('comprobante') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="obs">Obs</label>
                                <textarea wire:model="obs" id="obs" rows="3" class="form-input"></textarea>
                                @error('obs') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="movFechaRegistro">Fecha y hora del Registro en Tesorería</label>
                                <div class="flex flex-wrap gap-2">
                                    <input wire:model="fecha" id="movFechaRegistro" type="date" class="form-input max-w-[11rem]">
                                    <input wire:model="hora" id="movHoraRegistro" type="time" step="1" class="form-input max-w-[9rem]">
                                </div>
                                @error('fecha') <p class="form-error">{{ $message }}</p> @enderror
                                @error('hora') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <p class="text-xs text-red-600">* Campos obligatorios</p>
                        </form>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-accent-200 px-5 py-3">
                        <div>
                            @if ($idMovimiento)
                                <button type="button"
                                        x-on:click="window.vlSwalConfirmar('¿Eliminar este movimiento? Esta acción no se puede deshacer.', 'Eliminar movimiento', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar())"
                                        wire:loading.attr="disabled"
                                        wire:target="eliminar"
                                        class="rounded-xl border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                                    <span wire:loading.remove wire:target="eliminar">Eliminar</span>
                                    <span wire:loading wire:target="eliminar">Eliminando…</span>
                                </button>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    wire:click="cancelarFormulario"
                                    class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                                Cancelar
                            </button>
                            <button type="button"
                                    wire:click="guardar"
                                    wire:loading.attr="disabled"
                                    wire:target="guardar"
                                    class="btn-primary">
                                {{ $idMovimiento ? 'Guardar' : 'Agregar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if ($asientoAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cancelarAsiento">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cancelarAsiento"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-asiento-titulo">
                    <div class="border-b border-primary-800 bg-primary-800 px-5 py-3">
                        <h3 id="modal-asiento-titulo" class="text-lg font-bold tracking-wide text-white uppercase">
                            Asiento
                        </h3>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <form wire:submit.prevent="guardarAsiento" class="vl-form--compact space-y-3">
                            <div class="vl-form-field">
                                <label class="form-label" for="asientoFecha">Fecha <span class="text-red-600">*</span></label>
                                <div class="flex flex-wrap gap-2">
                                    <input wire:model="asientoFecha" id="asientoFecha" type="date" class="form-input max-w-[11rem]">
                                    <input wire:model="asientoHora" id="asientoHora" type="time" step="1" class="form-input max-w-[9rem]">
                                </div>
                                @error('asientoFecha') <p class="form-error">{{ $message }}</p> @enderror
                                @error('asientoHora') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="asientoIdCuentaOrigen">Cuenta de Origen <span class="text-red-600">*</span></label>
                                <select wire:model="asientoIdCuentaOrigen" id="asientoIdCuentaOrigen" class="form-select">
                                    <option value="">Seleccione</option>
                                    @foreach ($cuentas as $cuenta)
                                        <option value="{{ $cuenta->id }}">{{ $cuenta->nombreMedioPago }}</option>
                                    @endforeach
                                </select>
                                @error('asientoIdCuentaOrigen') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="asientoIdCuentaDestino">Cuenta de Destino <span class="text-red-600">*</span></label>
                                <select wire:model="asientoIdCuentaDestino" id="asientoIdCuentaDestino" class="form-select">
                                    <option value="">Seleccione</option>
                                    @foreach ($cuentas as $cuenta)
                                        <option value="{{ $cuenta->id }}">{{ $cuenta->nombreMedioPago }}</option>
                                    @endforeach
                                </select>
                                @error('asientoIdCuentaDestino') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="asientoIdClientes">Cliente <span class="text-red-600">*</span></label>
                                <select wire:model="asientoIdClientes" id="asientoIdClientes" class="form-select">
                                    <option value="">Seleccione</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('asientoIdClientes') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="asientoMonto">Monto <span class="text-red-600">*</span></label>
                                <input wire:model="asientoMonto" id="asientoMonto" type="text" inputmode="decimal"
                                       class="form-input max-w-[10rem]" placeholder="0.00" autocomplete="off">
                                @error('asientoMonto') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="asientoObs">Observaciones</label>
                                <textarea wire:model="asientoObs" id="asientoObs" rows="4" class="form-input"></textarea>
                                @error('asientoObs') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <p class="text-xs text-red-600">* Campos obligatorios</p>
                        </form>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cancelarAsiento"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="guardarAsiento"
                                wire:loading.attr="disabled"
                                wire:target="guardarAsiento"
                                class="btn-primary">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
