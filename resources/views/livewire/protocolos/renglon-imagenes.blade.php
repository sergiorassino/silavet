<div class="vl-carga-imagenes"
     x-data="{
         maxFotos: {{ \App\Livewire\Protocolos\RenglonImagenes::MAX_POR_VEZ }},
         avisoMax: {{ \Illuminate\Support\Js::from(\App\Livewire\Protocolos\RenglonImagenes::avisoMaxPorVez()) }},
         subiendo: false,
         subirSeleccion(event) {
             const input = event.target;
             const files = input.files;
             if (!files || files.length === 0 || this.subiendo) {
                 return;
             }
             if (files.length > this.maxFotos) {
                 input.value = '';
                 window.vlSwalError(this.avisoMax);
                 return;
             }
             this.subiendo = true;
             this.$wire.uploadMultiple('archivos', files, () => {
                 input.value = '';
                 this.subiendo = false;
             }, () => {
                 input.value = '';
                 this.subiendo = false;
             }, () => {}, () => {
                 input.value = '';
                 this.subiendo = false;
             }, false);
         },
     }">
    @if (! \Illuminate\Support\Facades\Schema::hasTable('imagenesxrenglon'))
        <div class="vl-carga-imagen-vacia">
            Tabla de imágenes no disponible. Ejecute la migración correspondiente.
        </div>
    @else
        <div class="vl-carga-imagenes-toolbar">
            <label class="btn-secondary text-xs cursor-pointer inline-flex items-center gap-1.5"
                   :class="subiendo ? 'pointer-events-none opacity-60' : ''">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                <span x-show="!subiendo">Seleccionar imágenes</span>
                <span x-show="subiendo" x-cloak>Subiendo…</span>
                <input type="file"
                       class="sr-only"
                       accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"
                       multiple
                       :disabled="subiendo"
                       x-on:change="subirSeleccion($event)">
            </label>
            <span class="text-xs text-neutral-500">{{ \App\Livewire\Protocolos\RenglonImagenes::avisoMaxPorVez() }}</span>
        </div>

        @error('archivos')
            <p class="form-error mt-1">{{ $message }}</p>
        @enderror
        @error('archivos.*')
            <p class="form-error mt-1">{{ $message }}</p>
        @enderror

        @if ($imagenes === [])
            <div class="vl-carga-imagen-vacia mt-2">Sin imágenes cargadas</div>
        @else
            <div class="vl-carga-imagenes-lista mt-3">
                @foreach ($imagenes as $img)
                    <div class="vl-carga-imagen-item" wire:key="img-item-{{ $img['id'] }}">
                        @if ($img['url'])
                            <a href="{{ $img['url'] }}" target="_blank" rel="noopener noreferrer" class="block">
                                <img src="{{ $img['url'] }}"
                                     alt="Imagen {{ $img['id'] }}"
                                     class="vl-carga-imagen-preview">
                            </a>
                        @else
                            <div class="vl-carga-imagen-vacia">Archivo no encontrado</div>
                        @endif

                        <textarea wire:model.blur="observaciones.{{ $img['id'] }}"
                                  wire:blur="guardarObservacion({{ $img['id'] }})"
                                  rows="2"
                                  class="form-input vl-carga-textarea mt-2"
                                  placeholder="Observación…"></textarea>

                        <div class="vl-carga-imagen-acciones mt-2">
                            <button type="button"
                                    class="btn-secondary text-xs text-red-700"
                                    x-on:click="window.vlSwalConfirmar('¿Eliminar esta imagen?', 'Eliminar imagen', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $img['id'] }}))">
                                Borrar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
