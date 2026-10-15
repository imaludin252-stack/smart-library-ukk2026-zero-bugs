<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Library') — Smart Library</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-bg-page)]">
    <header class="sticky top-0 z-30 border-b border-[var(--color-line)] bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 md:h-[72px] md:px-8">
            <x-logo />
            <nav class="ml-auto hidden items-center gap-1 md:flex">
                <a href="{{ route('catalog.index') }}" class="rounded-full px-4 py-2 text-[13px] font-semibold {{ request()->routeIs('catalog.*') ? 'bg-[var(--color-primary-800)] text-white' : 'text-[var(--color-ink-body)] hover:bg-[var(--color-primary-50)]' }}">Katalog Buku</a>
                <a href="{{ route('panduan') }}" class="rounded-full px-4 py-2 text-[13px] font-semibold {{ request()->routeIs('panduan') ? 'bg-[var(--color-primary-800)] text-white' : 'text-[var(--color-ink-body)] hover:bg-[var(--color-primary-50)]' }}">Panduan</a>
            </nav>
            @auth
                <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="btn btn-primary btn-sm ml-auto md:ml-0">
                    <x-icon name="home" class="h-4 w-4" /> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm ml-auto md:ml-0">
                    <x-icon name="key" class="h-4 w-4" /> Masuk Portal
                </a>
            @endauth
        </div>
    </header>

    <div class="border-b border-[var(--color-line)] bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-1 px-4 py-2 text-[11px] font-semibold text-[var(--color-ink-muted)] md:px-8">
            <span class="flex items-center gap-1.5 text-[var(--color-success)]">
                <span class="h-2 w-2 rounded-full bg-[var(--color-success)]"></span>
                Sistem Informasi Terpadu Perpustakaan Sekolah
            </span>
            <span>{{ \App\Models\Setting::get('nama_sekolah', 'SMK Budi Bakti Ciwidey') }}</span>
            <span>TA {{ \App\Models\Setting::get('tahun_ajaran', '2026/2027') }}</span>
            <span class="hidden md:inline">{{ \App\Models\Setting::get('jam_buka', 'Senin–Jumat, 07.30–15.30 WIB') }}</span>
        </div>
    </div>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 md:px-8 md:py-8">
        @yield('content')
    </main>

    <footer class="mt-8 border-t border-[var(--color-line)] bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-[12px] text-[var(--color-ink-muted)] md:flex-row md:items-center md:justify-between md:px-8">
            <p>© {{ date('Y') }} Smart Library · {{ \App\Models\Setting::get('nama_sekolah', 'SMK Budi Bakti Ciwidey') }}</p>
            <p>Jalan Raya Ciwidey, Kabupaten Bandung · Tim Pengembang: Zero Bug</p>
        </div>
    </footer>

    <x-flash />
    @stack('scripts')
</body>
</html>
