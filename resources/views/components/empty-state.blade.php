@props(['icon' => 'info', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--color-primary-100)] text-[var(--color-primary-800)]">
        <x-icon :name="$icon" class="h-7 w-7" />
    </span>
    <div>
        <h3 class="text-[16px] font-bold text-[var(--color-ink-strong)]">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1 max-w-md text-[13px] text-[var(--color-ink-muted)]">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
