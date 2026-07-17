<div class="vl-cli-campana {{ $variant === 'hero' ? 'vl-cli-campana--hero' : '' }}"
     x-data
     @keydown.escape.window="if ($wire.abierto) $wire.cerrarPanel()">
    <button type="button"
            class="vl-cli-campana-btn {{ $tieneSinLeer ? 'is-ringing' : 'is-quiet' }}"
            @if ($tieneSinLeer)
                wire:click="togglePanel"
                aria-expanded="{{ $abierto ? 'true' : 'false' }}"
                aria-controls="vl-cli-campana-panel"
                title="{{ $conteo }} aviso{{ $conteo === 1 ? '' : 's' }} sin leer"
            @else
                disabled
                title="Sin avisos nuevos"
            @endif
            aria-label="{{ $tieneSinLeer ? ($conteo.' avisos sin leer') : 'Sin avisos nuevos' }}">
        <span class="vl-cli-campana-icon" aria-hidden="true">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
        </span>
        @if ($tieneSinLeer)
            <span class="vl-cli-campana-badge">{{ $conteo > 99 ? '99+' : $conteo }}</span>
        @endif
    </button>

    @if ($abierto && $tieneSinLeer)
        @teleport('body')
            <div class="vl-cli-campana-overlay"
                 wire:keydown.escape.window="cerrarPanel">
                <button type="button"
                        class="vl-cli-campana-backdrop"
                        wire:click="cerrarPanel"
                        aria-label="Cerrar"></button>

                <div id="vl-cli-campana-panel"
                     class="vl-cli-campana-panel"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="vl-cli-campana-title"
                     wire:click.stop>
                    <div class="vl-cli-campana-panel-head">
                        <div>
                            <p class="vl-cli-campana-panel-kicker">Laboratorio</p>
                            <h2 id="vl-cli-campana-title" class="vl-cli-campana-panel-title">
                                Mensajes sin leer
                            </h2>
                        </div>
                        <button type="button"
                                class="vl-cli-campana-cerrar"
                                wire:click="cerrarPanel"
                                aria-label="Cerrar">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="vl-cli-campana-panel-body">
                        <div class="vl-cli-avisos-table-wrap">
                            <table class="vl-cli-avisos-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="vl-cli-avisos-th-visto">Visto</th>
                                        <th scope="col">Notificacion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($avisos as $aviso)
                                        <tr wire:key="campana-aviso-{{ $aviso['id'] }}">
                                            <td class="vl-cli-avisos-td-visto">
                                                <button type="button"
                                                        class="vl-cli-avisos-visto"
                                                        wire:click="marcarAvisoLeido({{ $aviso['id'] }})"
                                                        title="Marcar como leído"
                                                        aria-label="Marcar como leído">
                                                    <span class="vl-cli-avisos-visto-box" aria-hidden="true"></span>
                                                    <span class="vl-cli-avisos-visto-x" aria-hidden="true">×</span>
                                                </button>
                                            </td>
                                            <td class="vl-cli-avisos-td-texto">
                                                {{ $aviso['texto'] !== '' ? $aviso['texto'] : 'Aviso sin texto' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
