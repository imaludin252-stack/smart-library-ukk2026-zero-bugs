@props([
    'label',
    'value',
    'icon' => 'activity',
    'sub' => null,
    'tone' => 'primary',
    'progress' => null,
])

@php
    $tones = [
        'primary' => 'bg-[var(--color-primary-100)] text-[var(--color-primary-800)]',
        'success' => 'bg-[var(--color-success-bg)] text-[var(--color-success)]',
        'warning' => 'bg-[var(--color-warning-bg)] text-[var(--color-warning)]',
        'danger' => 'bg-[var(--color-danger-bg)] text-[var(--color-danger)]',
        'info' => 'bg-[var(--color-info-bg)] text-[var(--color-info)]',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'card card-pad flex flex-col gap-3']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="label-caps">{{ $label }}</p>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['primary'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
    <div>
        <p class="text-[28px] font-extrabold leading-8 text-[var(--color-ink-strong)] md:text-[34px] md:leading-[40px]">{{ $value }}</p>
        @if ($sub)
            <p class="mt-1 text-[12px] font-medium text-[var(--color-ink-muted)]">{{ $sub }}</p>
        @endif
    </div>
    @if ($progress !== null)
        <div class="progress">
            <div class="progress-bar" style="width: {{ min(100, max(0, $progress)) }}%"></div>
        </div>
    @endif
</div>
