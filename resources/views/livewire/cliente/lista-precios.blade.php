<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Autogestión</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Lista de precios</h1>
                <p class="mt-2 text-sm text-white/80">
                    Tarifario vigente publicado por el laboratorio.
                </p>
            </x-vl-hero-heading>
            <x-vl-cli-avisos-campana />
        </div>
    </div>

    <div class="vl-card overflow-hidden p-5 sm:p-6">
        @if ($tieneLista)
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-neutral-600">
                    La lista de precios está disponible en PDF. Podés abrirla en una pestaña nueva.
                </p>
                <a href="{{ $pdfUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn-primary shrink-0 inline-flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 2l5 5h-5V4zM8.5 13.5h7v1.5h-7v-1.5zm0 3h7v1.5h-7v-1.5z"/>
                    </svg>
                    Ver lista de precios
                </a>
            </div>
            <div class="mt-5 overflow-hidden rounded-xl border border-accent-200 bg-neutral-50">
                <iframe
                    src="{{ $pdfUrl }}"
                    title="Lista de precios PDF"
                    class="h-[70vh] w-full"
                ></iframe>
            </div>
        @else
            <p class="py-8 text-center text-sm text-neutral-500">
                Todavía no hay una lista de precios publicada. Consultá al laboratorio.
            </p>
        @endif
    </div>
</div>
