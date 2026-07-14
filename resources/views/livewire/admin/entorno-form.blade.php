<div class="vl-page vl-page--wide">
    <div class="vl-hero vl-hero--compact shrink-0">
        <div class="vl-hero-inner">
            <div>
                <p class="vl-eyebrow">Parámetros Generales</p>
                <h1 class="text-xl font-bold sm:text-2xl">Parámetros del Sistema</h1>
                <p class="mt-1 max-w-3xl text-xs text-white/80 sm:text-sm">
                    Configuración institucional del laboratorio: identidad visual, contacto, pie de informe, firmas y envío de mail.
                </p>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card mx-auto w-full max-w-4xl p-4">
        <div class="grid gap-6">

            {{-- General --}}
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">General</h2>
                <div>
                    <label class="form-label mb-1" for="listaPreciosUpload">Lista de precios (PDF)</label>
                    @if ($listaPreciosUrl)
                        <div class="mb-2 flex items-center gap-2 rounded border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm">
                            <svg class="h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <a href="{{ $listaPreciosUrl }}" target="_blank" rel="noopener" class="truncate font-medium text-primary-700 hover:underline">
                                Ver lista de precios actual
                            </a>
                        </div>
                    @else
                        <p class="mb-2 text-xs text-neutral-500">Sin lista de precios cargada.</p>
                    @endif
                    <input wire:model="listaPreciosUpload" id="listaPreciosUpload" type="file" accept="application/pdf,.pdf" class="form-input py-1.5 text-sm file:mr-2 file:rounded file:border-0 file:bg-primary-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-primary-700">
                    <p class="mt-1 text-xs text-neutral-500">PDF. Máx. 10 MB. Se mostrará en la autogestión del cliente.</p>
                    <div wire:loading wire:target="listaPreciosUpload" class="mt-1 text-xs text-primary-600">Subiendo lista de precios…</div>
                    @error('listaPreciosUpload') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </section>

            {{-- Identidad visual --}}
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Identidad visual</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label mb-1" for="logoUpload">Logo institucional</label>
                        @if ($logoPreviewUrl)
                            <div class="mb-2 rounded border border-primary-200 bg-primary-50 p-2">
                                <img src="{{ $logoPreviewUrl }}" alt="Vista previa del logo" class="max-h-20 max-w-full object-contain">
                                <p class="mt-1 text-xs text-primary-700">Vista previa — guardá los parámetros para confirmar.</p>
                            </div>
                        @elseif ($logoUrl)
                            <div class="mb-2 rounded border border-neutral-200 bg-neutral-50 p-2">
                                <img src="{{ $logoUrl }}" alt="Logo actual" class="max-h-20 max-w-full object-contain">
                                <p class="mt-1 text-xs text-neutral-600">Logo cargado. Solo subí otro archivo si querés reemplazarlo.</p>
                            </div>
                        @endif
                        <input wire:model="logoUpload" id="logoUpload" type="file" accept="image/*" class="form-input py-1.5 text-sm file:mr-2 file:rounded file:border-0 file:bg-primary-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-primary-700">
                        <p class="mt-1 text-xs text-neutral-500">PNG, JPG o WebP. Máx. 2 MB.</p>
                        <div wire:loading wire:target="logoUpload" class="mt-1 text-xs text-primary-600">Subiendo logo…</div>
                        @error('logoUpload') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label mb-1" for="colorInforme">Color del informe</label>
                        <div class="flex items-center gap-3">
                            <input wire:model.live="colorInforme" id="colorInforme" type="color" class="h-10 w-14 cursor-pointer rounded border border-neutral-300 bg-white p-1">
                            <input wire:model.live="colorInforme" type="text" maxlength="7" class="form-input max-w-[8rem] py-1.5 font-mono text-sm uppercase">
                            <span class="inline-block h-8 w-16 rounded border border-neutral-200" style="background-color: {{ $colorInforme }}"></span>
                        </div>
                        @error('colorInforme') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Contacto --}}
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Contacto del laboratorio</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="form-label mb-1" for="direLabo">Dirección</label>
                        <input wire:model="direLabo" id="direLabo" type="text" maxlength="255" class="form-input py-1.5 text-sm">
                        @error('direLabo') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="teleLabo">Teléfono</label>
                        <input wire:model="teleLabo" id="teleLabo" type="text" maxlength="80" class="form-input py-1.5 text-sm">
                        @error('teleLabo') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="emailLabo">Email</label>
                        <input wire:model="emailLabo" id="emailLabo" type="email" maxlength="120" class="form-input py-1.5 text-sm">
                        @error('emailLabo') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Pie de informe --}}
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Pie de informe</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <p class="text-xs font-medium text-neutral-600">Columna izquierda</p>
                        <input wire:model="texto1footerIzq" type="text" maxlength="255" placeholder="Línea 1" class="form-input py-1.5 text-sm">
                        <input wire:model="texto2footerIzq" type="text" maxlength="255" placeholder="Línea 2" class="form-input py-1.5 text-sm">
                    </div>
                    <div class="grid gap-2">
                        <p class="text-xs font-medium text-neutral-600">Columna central</p>
                        <input wire:model="texto1footerCentro" type="text" maxlength="255" placeholder="Línea 1" class="form-input py-1.5 text-sm">
                        <input wire:model="texto2footerCentro" type="text" maxlength="255" placeholder="Línea 2" class="form-input py-1.5 text-sm">
                    </div>
                    <div class="grid gap-2">
                        <p class="text-xs font-medium text-neutral-600">Columna derecha</p>
                        <input wire:model="texto1footerDer" type="text" maxlength="255" placeholder="Línea 1" class="form-input py-1.5 text-sm">
                        <input wire:model="texto2footerDer" type="text" maxlength="255" placeholder="Línea 2" class="form-input py-1.5 text-sm">
                    </div>
                </div>
            </section>

            {{-- Firmas --}}
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Firmas del informe</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label mb-1" for="firmaIzqUpload">Firma izquierda</label>
                        @if ($firmaIzqUrl)
                            <div class="mb-2 rounded border border-neutral-200 bg-neutral-50 p-2">
                                <img src="{{ $firmaIzqUrl }}" alt="Firma izquierda" class="max-h-16 max-w-full object-contain">
                            </div>
                        @endif
                        <input wire:model="firmaIzqUpload" id="firmaIzqUpload" type="file" accept="image/*" class="form-input py-1.5 text-sm file:mr-2 file:rounded file:border-0 file:bg-primary-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-primary-700">
                        <div wire:loading wire:target="firmaIzqUpload" class="mt-1 text-xs text-primary-600">Subiendo…</div>
                        @error('firmaIzqUpload') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="firmaCentroUpload">Firma central</label>
                        @if ($firmaCentroUrl)
                            <div class="mb-2 rounded border border-neutral-200 bg-neutral-50 p-2">
                                <img src="{{ $firmaCentroUrl }}" alt="Firma central" class="max-h-16 max-w-full object-contain">
                            </div>
                        @endif
                        <input wire:model="firmaCentroUpload" id="firmaCentroUpload" type="file" accept="image/*" class="form-input py-1.5 text-sm file:mr-2 file:rounded file:border-0 file:bg-primary-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-primary-700">
                        <div wire:loading wire:target="firmaCentroUpload" class="mt-1 text-xs text-primary-600">Subiendo…</div>
                        @error('firmaCentroUpload') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="firmaDerUpload">Firma derecha</label>
                        @if ($firmaDerUrl)
                            <div class="mb-2 rounded border border-neutral-200 bg-neutral-50 p-2">
                                <img src="{{ $firmaDerUrl }}" alt="Firma derecha" class="max-h-16 max-w-full object-contain">
                            </div>
                        @endif
                        <input wire:model="firmaDerUpload" id="firmaDerUpload" type="file" accept="image/*" class="form-input py-1.5 text-sm file:mr-2 file:rounded file:border-0 file:bg-primary-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-primary-700">
                        <div wire:loading wire:target="firmaDerUpload" class="mt-1 text-xs text-primary-600">Subiendo…</div>
                        @error('firmaDerUpload') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="mt-2 text-xs text-neutral-500">Imágenes PNG o JPG con fondo transparente. Máx. 1 MB cada una.</p>
            </section>

            {{-- Mail --}}
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Envío de mail</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="form-label mb-1" for="ctaEnvioMail">Cuenta de envío</label>
                        <input wire:model="ctaEnvioMail" id="ctaEnvioMail" type="text" maxlength="120" class="form-input py-1.5 text-sm">
                        @error('ctaEnvioMail') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="passEnvioMail">Contraseña de envío</label>
                        <input wire:model="passEnvioMail" id="passEnvioMail" type="password" maxlength="255" autocomplete="new-password"
                               placeholder="{{ $tienePassEnvioMail ? '•••••••• (dejar vacío para no cambiar)' : '' }}"
                               class="form-input py-1.5 text-sm">
                        @error('passEnvioMail') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="fromMail">Remitente (From)</label>
                        <input wire:model="fromMail" id="fromMail" type="text" maxlength="120" class="form-input py-1.5 text-sm">
                        <p class="mt-1 text-xs text-neutral-500">Nombre que aparece como remitente (no es la cuenta SMTP).</p>
                        @error('fromMail') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="nombrePieMail">Nombre en pie de mail</label>
                        <input wire:model="nombrePieMail" id="nombrePieMail" type="text" maxlength="120" class="form-input py-1.5 text-sm">
                        @error('nombrePieMail') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label mb-1" for="direccionPieMail">Dirección en pie de mail</label>
                        <input wire:model="direccionPieMail" id="direccionPieMail" type="text" maxlength="255" class="form-input py-1.5 text-sm">
                        @error('direccionPieMail') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="telefonoPieMail">Teléfono en pie de mail</label>
                        <input wire:model="telefonoPieMail" id="telefonoPieMail" type="text" maxlength="80" class="form-input py-1.5 text-sm">
                        @error('telefonoPieMail') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label mb-1" for="emailPieMail">Email en pie de mail</label>
                        <input wire:model="emailPieMail" id="emailPieMail" type="text" maxlength="120" class="form-input py-1.5 text-sm">
                        @error('emailPieMail') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-2 border-t border-neutral-200 pt-4">
                <button type="submit" class="btn-primary py-1.5 text-sm" wire:loading.attr="disabled" wire:target="save">
                    Guardar parámetros
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn-secondary py-1.5 text-sm">Volver</a>
            </div>
        </div>
    </form>
</div>
