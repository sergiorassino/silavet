<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Clientes</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Resumen cliente entre fechas</h1>
                <p class="mt-2 text-sm text-white/80">
                    PDF con protocolos y pagos del cliente en el período elegido, con saldo acumulado y saldo actual de la cuenta.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-card mx-auto max-w-lg overflow-hidden">
        <form wire:submit="generarPdf" class="space-y-5 p-5 sm:p-6">
            <div>
                <label class="form-label" for="vl-rcf-cliente">
                    Cliente <span class="text-red-600">*</span>
                </label>
                <select wire:model="idClientes"
                        id="vl-rcf-cliente"
                        class="form-input"
                        required>
                    <option value="">Seleccione…</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
                @error('idClientes')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label" for="vl-rcf-desde">
                    Desde <span class="text-red-600">*</span>
                </label>
                <input type="date"
                       wire:model="fechaDesde"
                       id="vl-rcf-desde"
                       class="form-input tabular-nums"
                       required>
                <p class="mt-1 text-xs text-neutral-500">Inclusive · formato DD/MM/AAAA.</p>
                @error('fechaDesde')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label" for="vl-rcf-hasta">
                    Hasta <span class="text-red-600">*</span>
                </label>
                <input type="date"
                       wire:model="fechaHasta"
                       id="vl-rcf-hasta"
                       class="form-input tabular-nums"
                       required>
                <p class="mt-1 text-xs text-neutral-500">Inclusive · formato DD/MM/AAAA.</p>
                @error('fechaHasta')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-xs text-red-600">* Campos obligatorios</p>

            <div class="border-t border-accent-100 pt-4">
                <button type="submit"
                        class="btn-primary w-full"
                        wire:loading.attr="disabled"
                        wire:target="generarPdf">
                    <span wire:loading.remove wire:target="generarPdf">Aceptar</span>
                    <span wire:loading wire:target="generarPdf">Generando…</span>
                </button>
            </div>
        </form>
    </div>
</div>
