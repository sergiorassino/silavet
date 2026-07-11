<div class="vl-page vl-carga-page"
     x-data="vlCargaResultados({
         formulas: {{ \Illuminate\Support\Js::from($formulasJs) }},
         estadoInicial: {{ \Illuminate\Support\Js::from($estadoPaciente) }}
     })"
     @keydown.window="onKeydown($event)">

    <div class="vl-prot-det-header mb-4">
        <div class="vl-prot-det-header-inner">
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Veterinaria:</span>
                <span class="vl-prot-det-header-value">{{ $pacienteResumen['veterinaria'] ?? '—' }}</span>
            </div>
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Paciente:</span>
                <span class="vl-prot-det-header-value">{{ $pacienteResumen['nombre'] ?? '—' }}</span>
            </div>
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Protocolo:</span>
                <span class="vl-prot-det-header-value">{{ $pacienteResumen['protocolo'] ?? '—' }}</span>
            </div>
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Especie:</span>
                <span class="vl-prot-det-header-value">{{ $pacienteResumen['especie'] ?? '—' }}</span>
            </div>
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Raza:</span>
                <span class="vl-prot-det-header-value">{{ $pacienteResumen['raza'] ?? '—' }}</span>
            </div>
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Sexo:</span>
                <span class="vl-prot-det-header-value">{{ $pacienteResumen['sexo'] ?? '—' }}</span>
            </div>
            <div class="vl-prot-det-header-item">
                <span class="vl-prot-det-header-label">Edad:</span>
                <span class="vl-prot-det-header-value">{{ $pacienteResumen['edad'] ?? '—' }}</span>
            </div>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-base font-semibold text-neutral-800">Carga de resultados</h1>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button"
                        class="btn-secondary text-sm opacity-60 cursor-not-allowed"
                        title="Funcionalidad a desarrollar"
                        disabled>
                    Autoanalizadores
                </button>
                <a href="{{ $urlVolver }}" class="btn-secondary text-sm">Volver</a>
            </div>
        </div>

        <div id="vl-form-carga" class="vl-carga-form">
            @forelse ($grupos as $grupo)
                <section class="vl-carga-grupo" wire:key="grupo-{{ $grupo['idGrupos'] }}">
                    <h2 class="vl-carga-grupo-titulo">{{ $grupo['nombreGrupo'] }}</h2>

                    <div class="vl-carga-filas">
                        @foreach ($grupo['renglones'] as $renglon)
                            @php
                                $idR = $renglon['idRenglones'];
                                $idI = $renglon['idItems'];
                                $tipo = (int) $renglon['tipoItem'];
                                $estilo = (int) $renglon['estiloNum'];
                                $actualiza = (int) $renglon['actualiza'] === 1;
                                $onchangeFmt = $actualiza
                                    ? "formatearNumero({$idI}, {$estilo}); if (typeof formulas === 'function') formulas();"
                                    : "formatearNumero({$idI}, {$estilo});";
                            @endphp

                            @if ($tipo === 5)
                                <div class="vl-carga-linea" wire:ignore wire:key="ren-{{ $idR }}">
                                    <hr>
                                </div>
                            @elseif ($tipo === 3)
                                <div class="vl-carga-fila vl-carga-fila--titulo" wire:ignore wire:key="ren-{{ $idR }}">
                                    <div class="vl-carga-label">{{ $renglon['nombreItem'] }}</div>
                                    <div class="vl-carga-control"></div>
                                </div>
                            @elseif ($tipo === 10)
                                <div class="vl-carga-fila vl-carga-fila--imagen" wire:key="ren-img-{{ $idR }}">
                                    <div class="vl-carga-label">{{ $renglon['nombreItem'] }}</div>
                                    <div class="vl-carga-control">
                                        <livewire:protocolos.renglon-imagenes
                                            :id-renglones="$idR"
                                            :id-pacientes="$idPacientes"
                                            :nombre-item="$renglon['nombreItem']"
                                            :key="'renglon-img-'.$idR" />
                                    </div>
                                </div>
                            @else
                                <div class="vl-carga-fila" wire:ignore wire:key="ren-{{ $idR }}">
                                    <div class="vl-carga-label">
                                        <label for="{{ $idI }}">{{ $renglon['nombreItem'] }}</label>
                                    </div>
                                    <div class="vl-carga-control">
                                        @if ($tipo === 1)
                                            <input type="text"
                                                   id="{{ $idI }}"
                                                   class="form-input vl-carga-input"
                                                   data-renglon="{{ $idR }}"
                                                   data-campo="valor"
                                                   value="{{ $renglon['valor'] }}"
                                                   placeholder="{{ $renglon['placeholder'] }}"
                                                   autocomplete="off"
                                                   @input="reemplazarComaPorPunto({{ $idI }}, {{ $estilo }})"
                                                   @change="{{ $onchangeFmt }}">
                                        @elseif ($tipo === 4)
                                            <div class="vl-carga-select-wrap">
                                                <select id="{{ $idI }}_2"
                                                        class="form-input vl-carga-select"
                                                        data-renglon="{{ $idR }}"
                                                        data-campo="valor2"
                                                        @change="comportamientoSelect('{{ $idI }}', '{{ $idI }}_2')">
                                                    <option value="">Seleccione</option>
                                                    @foreach ($renglon['opciones'] as $opcion)
                                                        <option value="{{ $opcion }}" @selected($opcion === $renglon['valor'] || $opcion === $renglon['valor2'])>
                                                            {{ $opcion }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <textarea id="{{ $idI }}"
                                                          class="form-input vl-carga-textarea"
                                                          data-renglon="{{ $idR }}"
                                                          data-campo="valor"
                                                          rows="2"
                                                          @change="comportamientoInputSelect('{{ $idI }}', '{{ $idI }}_2')">{{ $renglon['valor'] }}</textarea>
                                            </div>
                                        @elseif ($tipo === 7)
                                            <input type="text"
                                                   id="{{ $idI }}"
                                                   class="form-input vl-carga-input vl-carga-input--readonly"
                                                   data-renglon="{{ $idR }}"
                                                   data-campo="valor"
                                                   value="{{ $renglon['valor'] }}"
                                                   readonly
                                                   tabindex="-1">
                                        @elseif ($tipo === 8)
                                            <textarea id="{{ $idI }}"
                                                      class="form-input vl-carga-textarea"
                                                      data-renglon="{{ $idR }}"
                                                      data-campo="valor"
                                                      rows="3">{{ $renglon['valor'] }}</textarea>
                                        @elseif ($tipo === 9)
                                            <div class="vl-carga-doble">
                                                <input type="text"
                                                       id="{{ $idI }}"
                                                       class="form-input vl-carga-input"
                                                       data-renglon="{{ $idR }}"
                                                       data-campo="valor"
                                                       value="{{ $renglon['valor'] }}"
                                                       autocomplete="off"
                                                       @input="reemplazarComaPorPunto('{{ $idI }}', {{ $estilo }})"
                                                       @change="formatearNumero({{ $idI }}, {{ $estilo }}); if (typeof formulas === 'function') formulas();">
                                                <input type="hidden"
                                                       id="{{ $idI }}_2"
                                                       data-renglon="{{ $idR }}"
                                                       data-campo="valor2"
                                                       value="{{ $renglon['valor2'] }}">
                                                <input type="text"
                                                       id="{{ $idI }}_T"
                                                       class="form-input vl-carga-input vl-carga-input--readonly"
                                                       value="{{ $renglon['valor2'] }}"
                                                       disabled
                                                       tabindex="-1">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="px-5 py-10 text-center text-sm text-neutral-500">
                    No hay determinaciones con ítems para cargar en este protocolo.
                </div>
            @endforelse
        </div>

        <div class="vl-carga-footer border-t border-accent-200 px-5 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <button type="button"
                    class="btn-primary text-sm order-3 sm:order-1"
                    wire:loading.attr="disabled"
                    @click="guardar(false)">
                Guardar (F9)
            </button>
            <select x-model="estadoPaciente"
                    class="form-input vl-carga-estado order-2"
                    aria-label="Estado del protocolo">
                @foreach ($estados as $estado)
                    <option value="{{ $estado }}">{{ $estado }}</option>
                @endforeach
            </select>
            <button type="button"
                    class="btn-primary text-sm order-1 sm:order-3"
                    wire:loading.attr="disabled"
                    @click="guardar(true)">
                Guardar y Salir (F10)
            </button>
        </div>
    </div>
</div>
