{{-- Campana de avisos en el hero de autogestión del cliente. --}}
@if (labCtx()->esCliente() && labCtx()->idClientes)
    <div {{ $attributes->class(['vl-cli-campana-slot shrink-0 self-start sm:self-center']) }}>
        <livewire:cliente.avisos-campana variant="hero" />
    </div>
@endif
