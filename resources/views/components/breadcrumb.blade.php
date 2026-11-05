@props(['items' => []])

@php
    $items = array_values(array_filter($items));
@endphp

<nav aria-label="Breadcrumb" class="mb-4 flex flex-wrap items-center gap-1.5 text-[12px] font-semibold text-[var(--color-ink-muted)]">
    <a href="{{ route('home') }}" class="hover:text-[var(--color-primary-800)]">Beranda</a>

    @foreach ($items as $item)
        <x-icon name="chevron-right" class="h-3.5 w-3.5" />

        @if (! empty($item['url']))
            <a href="{{ $item['url'] }}" class="hover:text-[var(--color-primary-800)]">{{ $item['label'] }}</a>
        @else
            <span class="text-[var(--color-ink-strong)]" aria-current="page">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
