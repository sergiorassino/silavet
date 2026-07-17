<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $titulo }}</h1>
            </x-vl-hero-heading>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card mx-auto w-full max-w-md p-4">
        <div class="grid gap-3">
            <div>
                <label class="form-label mb-1" for="tipoConcepto">Tipo de movimiento *</label>
                <select wire:model="tipoConcepto" id="tipoConcepto" class="form-input py-1.5 text-sm" autofocus>
                    <option value="">Seleccionar…</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->id }}">{{ $tipo->tipoMovimiento }}</option>
                    @endforeach
                </select>
                @error('tipoConcepto') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label mb-1" for="concepto">Concepto *</label>
                <input wire:model="concepto" id="concepto" type="text" maxlength="100" class="form-input py-1.5 text-sm">
                @error('concepto') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label mb-1" for="orden">Orden</label>
                <input wire:model="orden" id="orden" type="number" min="0" max="9999" class="form-input py-1.5 text-sm">
                @error('orden') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit" class="btn-primary py-1.5 text-sm" wire:loading.attr="disabled">Guardar</button>
                <a href="{{ route('tesoreria.conceptos.index') }}" class="btn-secondary py-1.5 text-sm">Cancelar</a>
            </div>
        </div>
    </form>
</div>
