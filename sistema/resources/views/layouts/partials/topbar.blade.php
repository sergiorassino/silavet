<header class="flex items-center justify-between border-b border-accent-200 bg-white px-4 py-3 sm:px-6">
    <div class="text-sm text-neutral-600">
        {{ labCtx()->usuario()?->apenom ?? '' }}
        @if (labCtx()->rolNombre())
            <span class="text-neutral-400">·</span> {{ labCtx()->rolNombre() }}
        @endif
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-secondary btn-sm">Salir</button>
    </form>
</header>
