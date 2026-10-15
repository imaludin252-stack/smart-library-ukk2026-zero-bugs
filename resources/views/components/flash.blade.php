@php
    $flash = null;
    if (session('success')) {
        $flash = ['success', session('success')];
    } elseif (session('error')) {
        $flash = ['error', session('error')];
    } elseif (session('warning')) {
        $flash = ['warning', session('warning')];
    }
@endphp

@if ($flash)
    <div data-toast class="toast {{ $flash[0] === 'success' ? 'bg-[var(--color-success)]' : ($flash[0] === 'warning' ? 'bg-[var(--color-warning)]' : 'bg-[var(--color-danger)]') }}">
        <x-icon :name="$flash[0] === 'success' ? 'check-circle' : 'alert-triangle'" class="h-5 w-5 shrink-0" />
        <span class="flex-1">{{ $flash[1] }}</span>
        <button type="button" onclick="this.parentElement.remove()" class="shrink-0 opacity-80 hover:opacity-100">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>
@endif
