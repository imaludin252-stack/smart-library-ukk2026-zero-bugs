<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Portal — Smart Library</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-bg-page)]">
    <header class="border-b border-[var(--color-line)] bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 md:h-[72px] md:px-8">
            <x-logo />
            <nav class="ml-auto hidden items-center gap-1 md:flex">
                <a href="{{ route('login') }}" class="rounded-full bg-[var(--color-primary-800)] px-4 py-2 text-[13px] font-semibold text-white">Masuk Portal</a>
                <a href="{{ route('catalog.index') }}" class="rounded-full px-4 py-2 text-[13px] font-semibold text-[var(--color-ink-body)] hover:bg-[var(--color-primary-50)]">Katalog Publik</a>
                <a href="{{ route('panduan') }}" class="rounded-full px-4 py-2 text-[13px] font-semibold text-[var(--color-ink-body)] hover:bg-[var(--color-primary-50)]">Panduan</a>
            </nav>
        </div>
    </header>

    <div class="border-b border-[var(--color-line)] bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-1 px-4 py-2 text-[11px] font-semibold text-[var(--color-ink-muted)] md:px-8">
            <span class="flex items-center gap-1.5 text-[var(--color-success)]">
                <span class="h-2 w-2 rounded-full bg-[var(--color-success)]"></span>
                Sistem Informasi Terpadu Perpustakaan Sekolah
            </span>
            <span>Terakreditasi A</span>
            <span class="hidden md:inline">{{ \App\Models\Setting::get('jam_buka', 'Senin–Jumat, 07.30–15.30 WIB') }}</span>
        </div>
    </div>

    <main class="mx-auto grid max-w-6xl gap-6 px-4 py-8 md:px-8 lg:grid-cols-5">
        {{-- Kiri: hero --}}
        <section class="card card-pad flex flex-col gap-5 lg:col-span-2">
            <span class="badge badge-info">PORTAL AKSES TERINTEGRASI v1.0</span>
            <div>
                <h1 class="text-[28px] font-extrabold leading-9 text-[var(--color-ink-strong)]">
                    Selamat Datang di <span class="text-[var(--color-primary-800)]">Smart Library</span>
                </h1>
                <p class="mt-2 text-sm leading-6 text-[var(--color-ink-muted)]">
                    Sistem perpustakaan digital {{ \App\Models\Setting::get('nama_sekolah', 'SMK Budi Bakti Ciwidey') }}.
                    Telusuri koleksi, pinjam buku, dan pantau jatuh tempo dalam satu portal.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                    <p class="text-mono text-[11px] font-bold text-[var(--color-ink-muted)]">KOLEKSI BUKU</p>
                    <p class="text-[26px] font-extrabold text-[var(--color-ink-strong)]">{{ number_format(\App\Models\Book::where('status', 'aktif')->sum('stok'), 0, ',', '.') }}+</p>
                </div>
                <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                    <p class="text-mono text-[11px] font-bold text-[var(--color-ink-muted)]">ANGGOTA</p>
                    <p class="text-[26px] font-extrabold text-[var(--color-ink-strong)]">{{ number_format(\App\Models\Member::count(), 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-[var(--color-bg-muted)] p-4">
                <p class="flex items-center gap-2 text-[13px] font-bold text-[var(--color-ink-strong)]">
                    <x-icon name="trending-up" class="h-4 w-4 text-[var(--color-success)]" /> Peminjaman bulan ini
                </p>
                <p class="mt-1 text-[12px] text-[var(--color-ink-muted)]">
                    {{ \App\Models\Loan::whereMonth('tanggal_pinjam', now()->month)->whereYear('tanggal_pinjam', now()->year)->count() }} transaksi tercatat pada {{ now()->translatedFormat('F Y') }}.
                </p>
            </div>

            <p class="mt-auto flex items-center gap-2 text-[12px] font-semibold text-[var(--color-ink-muted)]">
                <x-icon name="map-pin" class="h-4 w-4" /> Ruang Sirkulasi Lt. 2 · Meja Sirkulasi A
            </p>
        </section>

        {{-- Kanan: form --}}
        <section class="card card-pad lg:col-span-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="section-title">Masuk ke Portal Smart Library</h2>
                    <p class="mt-1 text-[13px] text-[var(--color-ink-muted)]">Siswa, Petugas, dan Administrator menggunakan halaman ini.</p>
                </div>
                <span class="badge badge-success whitespace-nowrap"><x-icon name="shield" class="h-3.5 w-3.5" /> SSL 256-bit</span>
            </div>

            @if ($errors->any())
                <div class="mt-4 flex items-start gap-2 rounded-xl bg-[var(--color-danger-bg)] px-4 py-3 text-[13px] font-semibold text-[var(--color-danger)]">
                    <x-icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="email" class="field-label">Email</label>
                    <div class="relative">
                        <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-ink-muted)]" />
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            class="input pl-10" placeholder="nama@smartlibrary.sch.id">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="field-label">Kata Sandi</label>
                        <span class="text-[11px] font-semibold text-[var(--color-ink-muted)]">Batas sesi 8 jam</span>
                    </div>
                    <div class="relative">
                        <x-icon name="key" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-ink-muted)]" />
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            class="input px-10" placeholder="••••••••">
                        <button type="button" data-toggle-password="password" class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--color-ink-muted)] hover:text-[var(--color-ink-body)]">
                            <x-icon name="eye" class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-[13px] font-medium text-[var(--color-ink-body)]">
                    <input type="checkbox" name="remember" class="checkbox"> Ingat sesi saya di perangkat ini
                </label>

                <button type="submit" class="btn btn-primary btn-lg btn-full">
                    Masuk ke Smart Library <x-icon name="arrow-right" class="h-4 w-4" />
                </button>

                <p class="text-center text-[13px] text-[var(--color-ink-muted)]">
                    Siswa belum punya akun?
                    <a href="{{ route('register') }}" class="link">Daftar sekarang</a>
                </p>
            </form>

            <div class="mt-6">
                <div class="flex items-center gap-3">
                    <span class="h-px flex-1 bg-[var(--color-line)]"></span>
                    <span class="label-caps">Akun Percobaan Demo</span>
                    <span class="h-px flex-1 bg-[var(--color-line)]"></span>
                </div>
                <div class="mt-3 grid gap-2 sm:grid-cols-3">
                    <div class="rounded-xl bg-[var(--color-primary-50)] p-3">
                        <p class="text-[11px] font-bold uppercase text-[var(--color-ink-muted)]">Siswa</p>
                        <p class="text-mono mt-1 break-all text-[11px] text-[var(--color-ink-body)]">rizky@siswa.smkbb.sch.id</p>
                    </div>
                    <div class="rounded-xl bg-[var(--color-primary-50)] p-3">
                        <p class="text-[11px] font-bold uppercase text-[var(--color-ink-muted)]">Petugas</p>
                        <p class="text-mono mt-1 break-all text-[11px] text-[var(--color-ink-body)]">petugas@smartlibrary.sch.id</p>
                    </div>
                    <div class="rounded-xl bg-[var(--color-primary-50)] p-3">
                        <p class="text-[11px] font-bold uppercase text-[var(--color-ink-muted)]">Admin</p>
                        <p class="text-mono mt-1 break-all text-[11px] text-[var(--color-ink-body)]">admin@smartlibrary.sch.id</p>
                    </div>
                </div>
                <p class="mt-2 text-center text-[11px] font-semibold text-[var(--color-ink-muted)]">Kata sandi semua akun demo: <span class="text-mono text-[var(--color-primary-800)]">password</span></p>
            </div>
        </section>
    </main>

    <footer class="mt-4 border-t border-[var(--color-line)] bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-[12px] text-[var(--color-ink-muted)] md:flex-row md:items-center md:justify-between md:px-8">
            <p>© {{ date('Y') }} Smart Library · {{ \App\Models\Setting::get('nama_sekolah', 'SMK Budi Bakti Ciwidey') }}</p>
            <p>Dikembangkan oleh Tim Zero Bug (Firgi Faisal &amp; Imaludin)</p>
        </div>
    </footer>
</body>
</html>
