<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <p class="vl-eyebrow">Tesorería</p>
            <h1 class="text-2xl font-bold sm:text-3xl">Transferencias Intercuenta</h1>
            <p class="mt-2 text-sm text-white/80">Movimientos entre cuentas de medio de pago (retiro + depósito).</p>
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
                <label class="form-label mb-0" for="trFecha">Fecha</label>
                <div class="flex flex-wrap gap-2">
                    <input wire:model="fecha" id="trFecha" type="date" class="form-input max-w-[11rem]" required>
                    <input wire:model="hora" id="trHora" type="time" step="1" class="form-input max-w-[9rem]" required>
                </div>
                @error('fecha') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
                @error('hora') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,1fr)] sm:items-center">
                <label class="form-label mb-0" for="idMediodepagoOrigen">Cuenta de Origen</label>
                <select wire:model="idMediodepagoOrigen" id="idMediodepagoOrigen" class="form-input">
                    <option value="">Seleccione</option>
                    @foreach ($mediosPago as $medio)
                        <option value="{{ $medio->id }}">{{ $medio->nombreMedioPago }}</option>
                    @endforeach
                </select>
                @error('idMediodepagoOrigen') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,1fr)] sm:items-center">
                <label class="form-label mb-0" for="idMediodepagoDestino">Cuenta de Destino</label>
                <select wire:model="idMediodepagoDestino" id="idMediodepagoDestino" class="form-input">
                    <option value="">Seleccione</option>
                    @foreach ($mediosPago as $medio)
                        <option value="{{ $medio->id }}">{{ $medio->nombreMedioPago }}</option>
                    @endforeach
                </select>
                @error('idMediodepagoDestino') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,12rem)] sm:items-center">
                <label class="form-label mb-0" for="monto">Monto</label>
                <input wire:model="monto" id="monto" type="text" inputmode="decimal" class="form-input" placeholder="0,00">
                @error('monto') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-1 sm:grid-cols-[11rem_minmax(0,1fr)] sm:items-start">
                <label class="form-label mb-0 pt-2" for="observaciones">Observaciones</label>
                <textarea wire:model="observaciones" id="observaciones" rows="4" class="form-input"></textarea>
                @error('observaciones') <p class="form-error sm:col-start-2">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-center pt-2">
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="btn-primary inline-flex items-center gap-2 rounded-full px-8">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Aceptar
                </button>
            </div>
        </form>
    </div>
</div>
