@props(['variant' => 'full'])

<a href="{{ route('catalog.index') }}" class="flex items-center gap-2.5">
    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--color-primary-800)] text-white">
        <x-icon name="book-open" class="h-5 w-5" />
    </span>
    @if ($variant === 'full')
        <span class="leading-tight">
            <span class="block text-[15px] font-extrabold tracking-tight text-[var(--color-ink-strong)]">SMART LIBRARY</span>
            <span class="block text-[9px] font-bold uppercase tracking-[0.12em] text-[var(--color-ink-muted)]">SMK Budi Bakti Ciwidey</span>
        </span>
    @endif
</a>
