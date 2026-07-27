<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Gestión de Stock</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $tituloPagina }}</h1>
            </x-vl-hero-heading>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card mx-auto w-full max-w-lg p-4">
        <div class="grid gap-4">
            <div>
                <label class="form-label mb-1" for="reactivo">Nombre del reactivo / insumo *</label>
                <input wire:model="reactivo"
                       id="reactivo"
                       type="text"
                       maxlength="50"
                       class="form-input py-1.5 text-sm"
                       autofocus>
                @error('reactivo') <p class="form-error">{{ $message }}</p> @enderror
                <p class="mt-1 text-[11px] text-neutral-500">Máximo 50 caracteres.</p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="form-label mb-1" for="cantidad">Cantidad restante *</label>
                    <input wire:model="cantidad"
                           id="cantidad"
                           type="number"
                           min="0"
                           step="1"
                           class="form-input py-1.5 text-sm">
                    @error('cantidad') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label mb-1" for="minAviso">Mínimo para aviso *</label>
                    <input wire:model="minAviso"
                           id="minAviso"
                           type="number"
                           min="0"
                           step="1"
                           class="form-input py-1.5 text-sm">
                    @error('minAviso') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label mb-1" for="existIdeal">Existencia ideal *</label>
                    <input wire:model="existIdeal"
                           id="existIdeal"
                           type="number"
                           min="0"
                           step="1"
                           class="form-input py-1.5 text-sm">
                    @error('existIdeal') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <p class="text-[11px] text-neutral-500">
                El stock se descuenta automáticamente al cargar determinaciones en protocolos y se repone al eliminarlas.
            </p>

            <div class="flex flex-wrap gap-2 border-t border-accent-200 pt-3">
                <button type="submit"
                        class="btn-primary py-1.5 text-sm"
                        wire:loading.attr="disabled"
                        wire:target="save">
                    <span wire:loading.remove wire:target="save">Guardar</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
                <a href="{{ route('abm.reactivos.index') }}" class="btn-secondary py-1.5 text-sm">Cancelar</a>
            </div>
        </div>
    </form>
</div>
