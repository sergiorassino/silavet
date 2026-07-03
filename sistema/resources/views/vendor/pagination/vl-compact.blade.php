@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex items-center justify-between gap-3 text-sm">
        <p class="text-neutral-600">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg px-3 py-1.5 text-neutral-400">Anterior</span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" class="rounded-lg px-3 py-1.5 hover:bg-accent-100">Anterior</button>
            @endif

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" class="rounded-lg px-3 py-1.5 hover:bg-accent-100">Siguiente</button>
            @else
                <span class="rounded-lg px-3 py-1.5 text-neutral-400">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
