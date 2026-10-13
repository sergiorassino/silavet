<div>
    <div class="vl-auth-card vl-auth-card--login">
        <h2 class="font-bold tracking-tight text-neutral-800">Iniciar sesión</h2>
        <p class="mt-1 text-sm text-neutral-600">
            Ingrese el DNI del tutor para ver los estudios de sus mascotas.
        </p>

        @if (session('error'))
            <div class="mt-5 p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <noscript>
            <p class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-950">
                Se requiere JavaScript activo para iniciar sesión de forma segura.
            </p>
        </noscript>

        <form class="vl-auth-form"
              autocomplete="on"
              x-data
              x-on:submit.prevent="
                  if (window.__vlLoginSubmitting) { return; }
                  window.__vlLoginSubmitting = true;
                  window.dispatchEvent(new CustomEvent('vl-login-submit'));
                  if (typeof window.__vlLoginAutofillLock === 'function') {
                      window.__vlLoginAutofillLock();
                  }
                  const dniEl = $el.querySelector('#dni');
                  const dni = (dniEl?.value || '').replace(/[^a-zA-Z0-9]/g, '').slice(0, 8);
                  $wire.set('dni', dni, false);
                  try {
                      await $wire.login();
                  } finally {
                      window.__vlLoginSubmitting = false;
                  }
              ">
            <div>
                <label class="vl-auth-label" for="dni">DNI (usuario)</label>
                <input wire:model="dni"
                       id="dni"
                       type="text"
                       maxlength="20"
                       autocomplete="username"
                       placeholder="Ej: 25038868"
                       x-on:input="$event.target.value = $event.target.value.replace(/[^a-zA-Z0-9]/g, '').slice(0, 8)"
                       class="vl-auth-input @error('dni') !border-red-400 ring-2 ring-red-200/80 @enderror">
                @error('dni')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-1">
                <button type="submit" class="vl-auth-btn" wire:loading.attr="disabled" wire:target="login">
                    <span wire:loading.remove wire:target="login">Ingresar</span>
                    <span wire:loading wire:target="login" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Verificando…
                    </span>
                </button>
            </div>
        </form>

        @include('layouts.partials.login-autofill-sync')
    </div>
</div>
