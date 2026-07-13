@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-2">
        {{ $paginator->links() }}
    </nav>
@endif
