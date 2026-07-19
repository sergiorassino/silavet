<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Listados estadísticos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Excel de pacientes</h1>
                <p class="mt-2 text-sm text-white/80">
                    Genere una planilla Excel con los protocolos (pacientes) entre dos fechas inclusive.
                    Período: <span class="font-semibold">{{ $periodoTexto }}</span>
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-card mx-auto max-w-lg overflow-hidden">
        <form wire:submit="generarExcel" class="space-y-5 p-5 sm:p-6">
            <div>
                <label class="form-label" for="vl-ep-desde">Fecha inicial</label>
                <input type="date"
                       wire:model="fechaDesde"
                       id="vl-ep-desde"
                       class="form-input tabular-nums"
                       required>
                <p class="mt-1 text-xs text-neutral-500">Inclusive.</p>
                @error('fechaDesde')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label" for="vl-ep-hasta">Fecha final</label>
                <input type="date"
                       wire:model="fechaHasta"
                       id="vl-ep-hasta"
                       class="form-input tabular-nums"
                       required>
                <p class="mt-1 text-xs text-neutral-500">Inclusive.</p>
                @error('fechaHasta')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="btn-primary w-full"
                    wire:loading.attr="disabled"
                    wire:target="generarExcel">
                <span wire:loading.remove wire:target="generarExcel">Generar Excel</span>
                <span wire:loading wire:target="generarExcel">Generando…</span>
            </button>
        </form>
    </div>
</div>
