<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-3">
        <div class="vl-hero-inner flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Movimientos</h1>
                <p class="mt-1 text-sm text-white/80">Ingresos y egresos de caja.</p>
            </div>
            <button type="button"
                    wire:click="abrirFormularioNuevo"
                    class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">
                Nuevo movimiento
            </button>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por cliente, CUIT, cuenta, importe…"
                   class="form-input max-w-md text-xs">
        </div>

        <div class="overflow-x-auto">
            <table class="vl-movimientos-grid">
                <thead>
                    <tr>
                        <th class="vl-movimientos-th vl-movimientos-th--icon"></th>
                        <th class="vl-movimientos-th whitespace-nowrap">Id Pacientes</th>
                        <th class="vl-movimientos-th">Movimiento</th>
                        <th class="vl-movimientos-th">Cliente</th>
                        <th class="vl-movimientos-th">Cuit</th>
                        <th class="vl-movimientos-th">Cuenta</th>
                        <th class="vl-movimientos-th">Proveedor</th>
                        <th class="vl-movimientos-th whitespace-nowrap">Fecha</th>
                        <th class="vl-movimientos-th vl-movimientos-th--num">Importe</th>
                        <th class="vl-movimientos-th">Medio de Pago</th>
                        <th class="vl-movimientos-th">Observaciones</th>
                        <th class="vl-movimientos-th vl-movimientos-th--icon">Facturación</th>
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
                                        wire:click="abrirFormularioEditar({{ $mov->idPacientes }})"
                                        class="vl-movimientos-btn-edit"
                                        title="Editar">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 18.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                            </td>
                            <td class="vl-movimientos-td whitespace-nowrap tabular-nums">
                                {{ number_format((int) $mov->idPacientes, 0, ',', '.') }}
                            </td>
                            <td class="vl-movimientos-td font-medium">{{ $mov->etiquetaMovimiento() }}</td>
                            <td class="vl-movimientos-td">{{ $mov->cliente?->nombre ?: '—' }}</td>
                            <td class="vl-movimientos-td whitespace-nowrap">{{ $mov->cliente?->cuit ?: '' }}</td>
                            <td class="vl-movimientos-td">
                                {{ $mov->esEgreso() ? ($mov->cuentaDetalle?->cuenta?->nombreCuenta ?: '—') : '' }}
                            </td>
                            <td class="vl-movimientos-td">
                                {{ $mov->esEgreso() ? ($mov->cuentaDetalle?->nombreCuentasDetalle ?: '—') : '' }}
                            </td>
                            <td class="vl-movimientos-td whitespace-nowrap">
                                {{ $mov->fechhoy?->format('d/m/Y H:i:s') ?? '—' }}
                            </td>
                            <td class="vl-movimientos-td vl-movimientos-td--num whitespace-nowrap">
                                {{ $mov->pagadoFormateado() }}
                            </td>
                            <td class="vl-movimientos-td">{{ $mov->medioDePago?->nombreMedioPago ?: '—' }}</td>
                            <td class="vl-movimientos-td max-w-[12rem] truncate" title="{{ $mov->observaciones }}">
                                {{ $mov->observaciones ?: '' }}
                            </td>
                            <td class="vl-movimientos-td vl-movimientos-td--icon">
                                <button type="button"
                                        wire:click="facturacionPlaceholder"
                                        class="vl-movimientos-btn-fact"
                                        title="Facturación">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="vl-movimientos-td py-6 text-center text-neutral-500">
                                No hay movimientos de ingreso o egreso.
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
                <div class="relative z-10 flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-movimiento-titulo">
                    <div class="border-b border-accent-200 px-5 py-3">
                        <h3 id="modal-movimiento-titulo" class="text-lg font-bold text-neutral-900">
                            {{ $idPacientes ? 'Editar movimiento' : 'Nuevo movimiento' }}
                        </h3>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <form wire:submit.prevent="guardar" class="vl-form--compact space-y-3">
                            <div class="vl-form-field">
                                <label class="form-label" for="movFecha">Fecha <span class="text-red-600">*</span></label>
                                <div class="flex flex-wrap gap-2">
                                    <input wire:model="fecha" id="movFecha" type="date" class="form-input max-w-[11rem]">
                                    <input wire:model="hora" id="movHora" type="time" step="1" class="form-input max-w-[9rem]">
                                </div>
                                @error('fecha') <p class="form-error">{{ $message }}</p> @enderror
                                @error('hora') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="tipoRegistro">Movimiento</label>
                                <select wire:model.live="tipoRegistro" id="tipoRegistro" class="form-select">
                                    <option value="{{ \App\Models\Paciente::TIPO_INGRESO }}">Ingreso</option>
                                    <option value="{{ \App\Models\Paciente::TIPO_EGRESO }}">Egreso</option>
                                </select>
                                @error('tipoRegistro') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            @if ((int) $tipoRegistro === \App\Models\Paciente::TIPO_INGRESO)
                                <div class="vl-form-field">
                                    <label class="form-label" for="idClientes">Cliente</label>
                                    <select wire:model="idClientes" id="idClientes" class="form-select">
                                        <option value="">Seleccione</option>
                                        @foreach ($clientes as $cliente)
                                            <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('idClientes') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            @else
                                <div class="vl-form-field">
                                    <label class="form-label" for="idCuentas">Cuenta</label>
                                    <select wire:model.live="idCuentas" id="idCuentas" class="form-select">
                                        <option value="">Seleccione</option>
                                        @foreach ($cuentas as $cuenta)
                                            <option value="{{ $cuenta->id }}">{{ $cuenta->nombreCuenta }}</option>
                                        @endforeach
                                    </select>
                                    @error('idCuentas') <p class="form-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="vl-form-field">
                                    <label class="form-label" for="idCuentasdetalle">Proveedor</label>
                                    <select wire:model="idCuentasdetalle" id="idCuentasdetalle" class="form-select" @disabled(! $idCuentas)>
                                        <option value="">Seleccione</option>
                                        @foreach ($proveedores as $proveedor)
                                            <option value="{{ $proveedor->id }}">{{ $proveedor->nombreCuentasDetalle }}</option>
                                        @endforeach
                                    </select>
                                    @error('idCuentasdetalle') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div class="vl-form-field">
                                <label class="form-label" for="pagado">Pagado</label>
                                <input wire:model="pagado" id="pagado" type="text" inputmode="decimal" class="form-input" placeholder="0,00" autocomplete="off">
                                @error('pagado') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="idMediodepago">Medio de pago</label>
                                <select wire:model="idMediodepago" id="idMediodepago" class="form-select">
                                    <option value="">Seleccione</option>
                                    @foreach ($mediosPago as $medio)
                                        <option value="{{ $medio->id }}">{{ $medio->nombreMedioPago }}</option>
                                    @endforeach
                                </select>
                                @error('idMediodepago') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="vl-form-field">
                                <label class="form-label" for="observaciones">Observaciones</label>
                                <textarea wire:model="observaciones" id="observaciones" rows="3" class="form-input"></textarea>
                                @error('observaciones') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <p class="text-xs text-red-600">* Campos obligatorios</p>
                        </form>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cancelarFormulario"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="guardar"
                                wire:loading.attr="disabled"
                                class="btn-primary">
                            {{ $idPacientes ? 'Guardar' : 'Agregar' }}
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
