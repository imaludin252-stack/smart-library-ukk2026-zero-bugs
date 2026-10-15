@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $role = $user?->role ?? 'siswa';
    $isSiswa = $role === 'siswa';

    if ($isSiswa) {
        $nav = [
            ['route' => 'beranda', 'label' => 'Beranda', 'icon' => 'home', 'active' => 'beranda'],
            ['route' => 'catalog.index', 'label' => 'Katalog Buku', 'icon' => 'search', 'active' => 'catalog.*'],
            ['route' => 'pinjaman.index', 'label' => 'Peminjaman Saya', 'icon' => 'clipboard', 'active' => 'pinjaman.*'],
            ['route' => 'riwayat.index', 'label' => 'Riwayat Pinjam', 'icon' => 'clock', 'active' => 'riwayat.*'],
            ['route' => 'profil.index', 'label' => 'Profil Saya', 'icon' => 'user', 'active' => 'profil.*'],
        ];
        $bottomNav = [
            ['route' => 'beranda', 'label' => 'Beranda', 'icon' => 'home', 'active' => 'beranda'],
            ['route' => 'catalog.index', 'label' => 'Katalog', 'icon' => 'search', 'active' => 'catalog.*'],
            ['route' => 'pinjaman.index', 'label' => 'Pinjaman', 'icon' => 'clipboard', 'active' => 'pinjaman.*'],
            ['route' => 'riwayat.index', 'label' => 'Riwayat', 'icon' => 'clock', 'active' => 'riwayat.*'],
        ];
        $searchAction = route('catalog.index');
        $searchPlaceholder = 'Cari buku, penulis, atau kode buku';
    } else {
        $nav = [
            ['route' => 'petugas.dashboard', 'label' => 'Dashboard Petugas', 'icon' => 'home', 'active' => 'petugas.dashboard'],
            ['route' => 'petugas.koleksi.index', 'label' => 'Data Koleksi Buku', 'icon' => 'library', 'active' => 'petugas.koleksi.*'],
            ['route' => 'petugas.kategori.index', 'label' => 'Kategori & Penulis', 'icon' => 'tag', 'active' => 'petugas.kategori.*'],
            ['route' => 'petugas.anggota.index', 'label' => 'Data Anggota Siswa', 'icon' => 'users', 'active' => 'petugas.anggota.*'],
            ['route' => 'petugas.sirkulasi.index', 'label' => 'Meja Sirkulasi', 'icon' => 'refresh', 'active' => 'petugas.sirkulasi.*'],
            ['route' => 'petugas.laporan.index', 'label' => 'Laporan & Rekap', 'icon' => 'bar-chart', 'active' => 'petugas.laporan.*'],
        ];
        if ($role === 'admin') {
            $nav[] = ['route' => 'admin.pengguna', 'label' => 'Manajemen Pengguna', 'icon' => 'shield', 'active' => 'admin.pengguna*'];
            $nav[] = ['route' => 'admin.pengaturan', 'label' => 'Konfigurasi Sistem', 'icon' => 'settings', 'active' => 'admin.pengaturan*'];
        }
        $bottomNav = [];
        $searchAction = route('petugas.koleksi.index');
        $searchPlaceholder = 'Scan barcode ISBN / NIS / No. Transaksi';
    }
@endphp

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
    <input id="drawer" type="checkbox" class="peer/drawer hidden">

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-40 flex w-[240px] -translate-x-full flex-col border-r border-[var(--color-line)] bg-white transition-transform duration-200 peer-checked/drawer:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
            <div class="flex h-16 items-center justify-between px-5 md:h-[72px]">
                <x-logo />
                <label for="drawer" class="cursor-pointer rounded-lg p-2 text-[var(--color-ink-muted)] hover:bg-[var(--color-primary-50)] lg:hidden">
                    <x-icon name="x" class="h-5 w-5" />
                </label>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                        class="nav-item {{ request()->routeIs($item['active']) ? 'nav-item-active' : '' }}">
                        <x-icon :name="$item['icon']" class="nav-icon" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="space-y-3 border-t border-[var(--color-line)] p-4">
                <div class="rounded-xl bg-[var(--color-bg-muted)] p-3.5">
                    <p class="flex items-center gap-1.5 text-[12px] font-bold text-[var(--color-ink-strong)]">
                        <x-icon name="info" class="h-4 w-4" /> Bantuan Perpustakaan
                    </p>
                    <p class="mt-1 text-[11px] leading-4 text-[var(--color-ink-muted)]">
                        Jam buka<br>{{ \App\Models\Setting::get('jam_buka', 'Senin–Jumat, 07.30–15.30 WIB') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger-ghost btn-full">
                        <x-icon name="log-out" class="h-4 w-4" /> Keluar
                    </button>
                </form>
            </div>
        </aside>

        <label for="drawer" class="fixed inset-0 z-30 hidden bg-[rgba(9,24,52,.5)] peer-checked/drawer:block lg:hidden"></label>

        {{-- Kolom utama --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-[var(--color-line)] bg-white/95 px-4 backdrop-blur md:h-[72px] md:px-8">
                <label for="drawer" class="cursor-pointer rounded-lg p-2 text-[var(--color-ink-body)] hover:bg-[var(--color-primary-50)] lg:hidden">
                    <x-icon name="menu" class="h-5 w-5" />
                </label>

                <x-logo variant="mark" class="lg:hidden" />

                <form method="GET" action="{{ $searchAction }}" class="hidden flex-1 md:block">
                    <div class="relative max-w-md">
                        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-ink-muted)]" />
                        <input type="search" name="q" value="{{ request('q') }}" data-search-input
                            placeholder="{{ $searchPlaceholder }}"
                            class="input pl-10 pr-16">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md border border-[var(--color-line)] bg-white px-1.5 py-0.5 text-[10px] font-bold text-[var(--color-ink-muted)]">Ctrl K</span>
                    </div>
                </form>

                <div class="ml-auto flex items-center gap-2 md:gap-3">
                    @if (! $isSiswa)
                        <span class="hidden items-center gap-1.5 rounded-full bg-[var(--color-success-bg)] px-3 py-1.5 text-[11px] font-bold text-[var(--color-success)] lg:flex">
                            <span class="h-2 w-2 rounded-full bg-[var(--color-success)]"></span>
                            Meja Sirkulasi: Aktif
                        </span>
                    @endif

                    <a href="{{ $isSiswa ? route('pinjaman.index') : route('petugas.sirkulasi.index') }}"
                        class="relative rounded-xl p-2.5 text-[var(--color-ink-body)] hover:bg-[var(--color-primary-50)]">
                        <x-icon name="bell" class="h-5 w-5" />
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-[var(--color-danger)]"></span>
                    </a>

                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-xl p-1.5 hover:bg-[var(--color-primary-50)]">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-[12px] font-bold text-[var(--color-primary-800)]">
                                {{ $user?->initials() }}
                            </span>
                            <span class="hidden text-left leading-tight md:block">
                                <span class="block max-w-[160px] truncate text-[13px] font-bold text-[var(--color-ink-strong)]">{{ $user?->name }}</span>
                                <span class="block text-[11px] text-[var(--color-ink-muted)]">
                                    @if ($isSiswa)
                                        {{ $user->member?->kelas }} • NISN {{ $user->member?->nisn }}
                                    @else
                                        {{ $user->roleLabel() }}
                                    @endif
                                </span>
                            </span>
                            <x-icon name="chevron-down" class="hidden h-4 w-4 text-[var(--color-ink-muted)] md:block" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-56 rounded-xl border border-[var(--color-line)] bg-white p-2 shadow-lg">
                            <a href="{{ route('profil.index') }}" class="nav-item w-full">
                                <x-icon name="user" class="nav-icon" /> Profil Saya
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-item w-full text-[var(--color-danger)]">
                                    <x-icon name="log-out" class="nav-icon text-[var(--color-danger)]" /> Keluar
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1040px] flex-1 px-4 py-5 md:px-8 md:py-8 {{ $isSiswa ? 'pb-24 lg:pb-8' : '' }}">
                @yield('content')
            </main>
        </div>
    </div>

    @if ($isSiswa)
        <nav class="fixed inset-x-0 bottom-0 z-30 flex border-t border-[var(--color-line)] bg-white px-2 pb-[env(safe-area-inset-bottom)] lg:hidden">
            @foreach ($bottomNav as $item)
                <a href="{{ route($item['route']) }}" class="bottom-nav-item {{ request()->routeIs($item['active']) ? 'bottom-nav-item-active' : '' }}">
                    <x-icon :name="$item['icon']" class="h-5 w-5" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    <x-flash />
    @stack('scripts')
</body>
</html>
