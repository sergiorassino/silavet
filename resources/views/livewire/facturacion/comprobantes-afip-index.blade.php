<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-3">
        <div class="vl-hero-inner flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Facturación</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Comprobantes AFIP</h1>
                <p class="mt-1 text-sm text-white/80">
                    {{ $origenLabel }} · {{ $clienteLabel }} · {{ $importeLabel }}
                    @if ($simulando)
                        <span class="ml-2 rounded bg-amber-400/90 px-1.5 py-0.5 text-xs font-semibold text-amber-950">SIMULACIÓN</span>
                    @endif
                </p>
            </x-vl-hero-heading>
            <a href="{{ $volverUrl }}" class="btn-secondary shrink-0 bg-white/10 text-white hover:bg-white/20">
                Volver
            </a>
        </div>
    </div>

    @unless ($emisorOk)
        <div class="vl-card mb-3 border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            El usuario actual no puede emitir: falta permiso AFIP, CUIT, punto de venta o certificados en
            <code class="text-xs">afipSE/cert/{{ (int) (labCtx()->idUsuarios ?? 0) }}/</code>.
        </div>
    @endunless

    <div class="vl-card mb-3 p-4">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-500">Emitir</h2>
        <div class="flex flex-wrap items-end gap-3">
            <button type="button"
                    wire:click="emitirFactura"
                    wire:loading.attr="disabled"
                    @disabled(! $emisorOk)
                    class="btn-primary disabled:opacity-50">
                Factura
            </button>
            <button type="button"
                    wire:click="emitirComanda"
                    wire:loading.attr="disabled"
                    @disabled(! $emisorOk)
                    class="btn-secondary disabled:opacity-50">
                Comanda
            </button>

            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="form-label mb-1" for="idFacturaNc">Nota de crédito sobre</label>
                    <select wire:model="idFacturaNc" id="idFacturaNc" class="form-input py-1.5 text-sm min-w-[12rem]">
                        <option value="">— Seleccionar factura —</option>
                        @foreach ($facturasAnulables as $f)
                            <option value="{{ $f->id }}">
                                {{ $f->numeroFormateado() }} · $ {{ number_format((float) $f->importe, 2, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="button"
                        wire:click="emitirNotaCredito"
                        wire:loading.attr="disabled"
                        @disabled(! $emisorOk || $facturasAnulables === [])
                        class="btn-secondary disabled:opacity-50">
                    Nota de crédito
                </button>
            </div>
        </div>
        <p class="mt-2 text-xs text-neutral-500">
            La nota de crédito se emite por el importe total de la factura seleccionada.
        </p>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="vl-movimientos-grid">
                <thead>
                    <tr>
                        <th class="vl-movimientos-th">Fecha</th>
                        <th class="vl-movimientos-th">Tipo</th>
                        <th class="vl-movimientos-th">Número</th>
                        <th class="vl-movimientos-th">Receptor</th>
                        <th class="vl-movimientos-th">Concepto</th>
                        <th class="vl-movimientos-th vl-movimientos-th--num">Importe</th>
                        <th class="vl-movimientos-th">CAE</th>
                        <th class="vl-movimientos-th vl-movimientos-th--icon">PDF</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comprobantes as $c)
                        <tr class="vl-movimientos-row">
                            <td class="vl-movimientos-td whitespace-nowrap">
                                {{ $c->fechaComprobante?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="vl-movimientos-td">{{ $c->etiquetaTipo() }}</td>
                            <td class="vl-movimientos-td whitespace-nowrap tabular-nums">{{ $c->numeroFormateado() }}</td>
                            <td class="vl-movimientos-td">
                                {{ $c->razonSocialCliente }}
                                <span class="block text-xs text-neutral-500">{{ $c->DocNro }}</span>
                            </td>
                            <td class="vl-movimientos-td max-w-[14rem] truncate" title="{{ $c->conceptoFacturado }}">
                                {{ $c->conceptoFacturado }}
                            </td>
                            <td class="vl-movimientos-td vl-movimientos-td--num whitespace-nowrap">
                                $ {{ number_format((float) $c->importe, 2, ',', '.') }}
                            </td>
                            <td class="vl-movimientos-td whitespace-nowrap text-xs">
                                @if ($c->esComanda())
                                    —
                                @else
                                    {{ $c->CAE }}
                                @endif
                            </td>
                            <td class="vl-movimientos-td vl-movimientos-td--icon">
                                <a href="{{ $urlPdfFn((int) $c->id) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="vl-movimientos-btn-fact"
                                   title="Imprimir PDF">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="vl-movimientos-td py-6 text-center text-neutral-500">
                                No hay comprobantes AFIP vinculados a este registro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
