<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <div>
                <p class="vl-eyebrow">ABM · Gestión de Usuarios</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $titulo }}</h1>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card mx-auto w-full max-w-3xl p-4 sm:p-6">
        <div class="grid gap-4">
            <div>
                <label class="form-label mb-1" for="idClientes">Cliente</label>
                <select wire:model="idClientes" id="idClientes" class="form-input py-1.5 text-sm" autofocus>
                    <option value="">Ninguno (personal del laboratorio)</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
                @error('idClientes') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label mb-1" for="apenom">Apellido y nombre *</label>
                <input wire:model="apenom" id="apenom" type="text" maxlength="150" class="form-input py-1.5 text-sm">
                @error('apenom') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label mb-1" for="dni">DNI / Usuario *</label>
                    <input wire:model="dni" id="dni" type="text" maxlength="10" class="form-input py-1.5 text-sm" autocomplete="username">
                    @error('dni') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label mb-1" for="password">Contraseña *</label>
                    <input wire:model="password" id="password" type="text" maxlength="10" class="form-input py-1.5 text-sm" autocomplete="new-password">
                    @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-neutral-500">Máximo 10 caracteres (texto plano, legado NeoLab).</p>
                </div>
            </div>

            <div>
                <label class="form-label mb-1" for="idRoles">Rol *</label>
                <select wire:model="idRoles" id="idRoles" class="form-input py-1.5 text-sm">
                    <option value="">Seleccionar…</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->id }}">{{ $rol->rol }}</option>
                    @endforeach
                </select>
                @error('idRoles') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-lg border border-accent-200 bg-accent-50/40 px-4 py-3">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-neutral-800">
                    <input wire:model.live="permisoAfip" type="checkbox" class="rounded border-accent-300 text-primary-700 focus:ring-primary-500">
                    Permiso AFIP (configuración de facturación)
                </label>
                <p class="mt-1 text-xs text-neutral-500">Al habilitarlo se muestran los datos del emisor AFIP para este usuario.</p>
            </div>

            @if ($permisoAfip)
                <div class="grid gap-4 rounded-lg border border-primary-200 bg-primary-50/30 p-4">
                    <h2 class="text-sm font-semibold text-primary-800">Configuración AFIP</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label mb-1" for="cuit">CUIT *</label>
                            <input wire:model.live="cuit" id="cuit" type="text" maxlength="13" inputmode="numeric"
                                   class="form-input py-1.5 text-sm" placeholder="99-99999999-9">
                            @error('cuit') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label mb-1" for="razonSocial">Razón social *</label>
                            <input wire:model="razonSocial" id="razonSocial" type="text" maxlength="100" class="form-input py-1.5 text-sm">
                            @error('razonSocial') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label mb-1" for="domicComerc">Domicilio comercial</label>
                        <input wire:model="domicComerc" id="domicComerc" type="text" maxlength="50" class="form-input py-1.5 text-sm">
                        @error('domicComerc') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label mb-1" for="condIva">Condición IVA</label>
                            <input wire:model="condIva" id="condIva" type="text" maxlength="30" class="form-input py-1.5 text-sm">
                            @error('condIva') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label mb-1" for="ingresosBrutos">Ingresos brutos</label>
                            <input wire:model="ingresosBrutos" id="ingresosBrutos" type="text" maxlength="30" class="form-input py-1.5 text-sm">
                            @error('ingresosBrutos') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="form-label mb-1" for="inicioActiv">Inicio de actividades</label>
                            <input wire:model="inicioActiv" id="inicioActiv" type="date" class="form-input py-1.5 text-sm">
                            @error('inicioActiv') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label mb-1" for="PtoVta">Punto de venta *</label>
                            <input wire:model="PtoVta" id="PtoVta" type="number" min="0" max="99" class="form-input py-1.5 text-sm">
                            @error('PtoVta') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label mb-1" for="Concepto">Concepto</label>
                            <input wire:model="Concepto" id="Concepto" type="number" min="0" max="99" class="form-input py-1.5 text-sm">
                            @error('Concepto') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label mb-1" for="CbteTipo">Tipo comprobante</label>
                            <input wire:model="CbteTipo" id="CbteTipo" type="number" min="0" max="99" class="form-input py-1.5 text-sm">
                            @error('CbteTipo') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label mb-1" for="NtaCredTipo">Tipo nota de crédito</label>
                            <input wire:model="NtaCredTipo" id="NtaCredTipo" type="number" min="0" max="99" class="form-input py-1.5 text-sm">
                            @error('NtaCredTipo') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label mb-1" for="DocTipo">Tipo documento</label>
                            <input wire:model="DocTipo" id="DocTipo" type="number" min="0" max="99" class="form-input py-1.5 text-sm">
                            @error('DocTipo') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label mb-1" for="CondicionIVAReceptorId">Condición IVA receptor</label>
                            <input wire:model="CondicionIVAReceptorId" id="CondicionIVAReceptorId" type="number" min="0" max="99" class="form-input py-1.5 text-sm">
                            @error('CondicionIVAReceptorId') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label mb-1" for="key">Archivo / clave (key)</label>
                            <input wire:model="key" id="key" type="text" maxlength="100" class="form-input py-1.5 text-sm">
                            @error('key') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label mb-1" for="crt">Certificado (crt)</label>
                            <input wire:model="crt" id="crt" type="text" maxlength="100" class="form-input py-1.5 text-sm">
                            @error('crt') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit" class="btn-primary py-1.5 text-sm" wire:loading.attr="disabled">Guardar</button>
                <a href="{{ route('abm.usuarios.index') }}" class="btn-secondary py-1.5 text-sm">Cancelar</a>
            </div>
        </div>
    </form>
</div>
