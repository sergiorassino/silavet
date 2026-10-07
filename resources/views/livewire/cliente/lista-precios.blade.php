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
                   id="vl-lista-precios-abrir"
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
                <p id="vl-lista-precios-estado" class="px-4 py-3 text-sm text-neutral-500">Cargando lista de precios…</p>
                <iframe
                    id="vl-lista-precios-frame"
                    title="Lista de precios PDF"
                    class="h-[70vh] w-full"
                ></iframe>
                <script>
                    (function () {
                        var url = @json($pdfUrl);
                        var frame = document.getElementById('vl-lista-precios-frame');
                        var link = document.getElementById('vl-lista-precios-abrir');
                        var estado = document.getElementById('vl-lista-precios-estado');
                        var blobUrl = null;

                        function esPwa() {
                            return window.matchMedia('(display-mode: standalone)').matches
                                || window.matchMedia('(display-mode: fullscreen)').matches
                                || window.navigator.standalone === true;
                        }

                        function cargar() {
                            var destino = url + (url.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();

                            return fetch(destino, {
                                cache: 'no-store',
                                credentials: 'same-origin'
                            }).then(function (res) {
                                var tipo = (res.headers.get('Content-Type') || '').toLowerCase();
                                if (!res.ok || tipo.indexOf('pdf') === -1) {
                                    throw new Error('HTTP ' + res.status);
                                }

                                return res.blob();
                            }).then(function (blob) {
                                var pdf = blob.type && blob.type.indexOf('pdf') !== -1
                                    ? blob
                                    : new Blob([blob], { type: 'application/pdf' });

                                if (blobUrl) {
                                    URL.revokeObjectURL(blobUrl);
                                }

                                blobUrl = URL.createObjectURL(pdf);
                                frame.src = blobUrl;

                                if (estado) {
                                    estado.hidden = true;
                                }

                                return blobUrl;
                            });
                        }

                        if (link) {
                            link.addEventListener('click', function (ev) {
                                if (esPwa()) {
                                    return;
                                }

                                ev.preventDefault();
                                ev.stopPropagation();

                                var listo = blobUrl ? Promise.resolve(blobUrl) : cargar();
                                listo.then(function (abierta) {
                                    window.open(abierta, '_blank', 'noopener,noreferrer');
                                }).catch(function () {
                                    window.open(url, '_blank', 'noopener,noreferrer');
                                });
                            }, true);
                        }

                        function mostrar() {
                            cargar().catch(function () {
                                if (estado) {
                                    estado.hidden = false;
                                    estado.textContent = 'No se pudo cargar la lista de precios. Intente de nuevo.';
                                }
                            });
                        }

                        mostrar();
                        window.addEventListener('pageshow', function (ev) {
                            if (ev.persisted) {
                                mostrar();
                            }
                        });
                    })();
                </script>
            </div>
        @else
            <p class="py-8 text-center text-sm text-neutral-500">
                Todavía no hay una lista de precios publicada. Consultá al laboratorio.
            </p>
        @endif
    </div>
</div>
