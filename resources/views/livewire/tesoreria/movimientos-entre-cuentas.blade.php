<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Movimientos entre Cuentas</h1>
                <p class="mt-2 text-sm text-white/80">Transferencia entre cuentas (retiro + depósito en tabla movimientos).</p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-card mx-auto max-w-2xl overflow-hidden">
        <div class="border-b border-primary-800 bg-primary-800 px-5 py-3">
            <h2 class="text-sm font-semibold tracking-wide text-white uppercase">
                Movimientos entre cuentas
            </h2>
        </div>

        <form wire:submit.prevent="aceptar" class="space-y-4 px-5 py-5">
            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,1fr)] sm:items-center">
                <label class="form-label mb-0" for="mecFecha">Fecha</label>
                <div class="flex flex-wrap gap-2">
                    <input wire:model="fecha" id="mecFecha" type="date" class="form-input max-w-[11rem]" required>
                    <input wire:model="hora" id="mecHora" type="time" step="1" class="form-input max-w-[9rem]" required>
                </div>
                @error('fecha') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
                @error('hora') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,1fr)] sm:items-center">
                <label class="form-label mb-0" for="idCuentaOrigen">Cuenta de Origen</label>
                <select wire:model="idCuentaOrigen" id="idCuentaOrigen" class="form-input">
                    <option value="">Seleccione</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->id }}">{{ $cuenta->nombreMedioPago }}</option>
                    @endforeach
                </select>
                @error('idCuentaOrigen') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,1fr)] sm:items-center">
                <label class="form-label mb-0" for="idCuentaDestino">Cuenta de Destino</label>
                <select wire:model="idCuentaDestino" id="idCuentaDestino" class="form-input">
                    <option value="">Seleccione</option>
                    @foreach ($cuentas as $cuenta)
                        <option value="{{ $cuenta->id }}">{{ $cuenta->nombreMedioPago }}</option>
                    @endforeach
                </select>
                @error('idCuentaDestino') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,12rem)] sm:items-center">
                <label class="form-label mb-0" for="mecMonto">Monto</label>
                <input wire:model="monto" id="mecMonto" type="text" inputmode="decimal" class="form-input" placeholder="0,00">
                @error('monto') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,1fr)] sm:items-start">
                <label class="form-label mb-0 pt-2" for="mecObservaciones">Observaciones</label>
                <textarea wire:model="observaciones" id="mecObservaciones" rows="4" class="form-input"></textarea>
                @error('observaciones') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap justify-center gap-3 pt-2">
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="btn-primary inline-flex items-center gap-2 rounded-full px-8">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Aceptar
                </button>
                <button type="button"
                        wire:click="salir"
                        class="btn-secondary inline-flex items-center gap-2 rounded-full border-red-300 bg-red-600 px-8 text-white hover:bg-red-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Salir
                </button>
            </div>
        </form>
    </div>
</div>
