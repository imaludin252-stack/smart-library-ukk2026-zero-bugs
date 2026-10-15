@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="mt-6 flex flex-wrap items-center justify-center gap-1.5" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm btn-ghost cursor-not-allowed opacity-50">
                <x-icon name="chevron-left" class="h-4 w-4" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-ghost">
                <x-icon name="chevron-left" class="h-4 w-4" />
            </a>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if ($page == $paginator->currentPage())
                <span class="btn btn-sm btn-primary">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="btn btn-sm btn-ghost">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-ghost">
                <x-icon name="chevron-right" class="h-4 w-4" />
            </a>
        @else
            <span class="btn btn-sm btn-ghost cursor-not-allowed opacity-50">
                <x-icon name="chevron-right" class="h-4 w-4" />
            </span>
        @endif
    </nav>
@endif
