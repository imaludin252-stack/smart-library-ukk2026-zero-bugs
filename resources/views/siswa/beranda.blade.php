@extends('layouts.app')

@section('title', 'Beranda Siswa')

@section('content')
    @php
        $aktif = $pinjamanAktif->whereIn('status', [\App\Models\Loan::STATUS_DIPINJAM, \App\Models\Loan::STATUS_TERLAMBAT]);
        $menunggu = $pinjamanAktif->where('status', \App\Models\Loan::STATUS_DIAJUKAN);
        $totalAktif = $pinjamanAktif->count();
    @endphp

    {{-- Hero --}}
    <div class="card card-pad mb-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="badge badge-success"><x-icon name="check-circle" class="h-3.5 w-3.5" /> Anggota Aktif Perpustakaan</span>
                <h1 class="mt-3 text-[26px] font-extrabold leading-8 text-[var(--color-ink-strong)] md:text-[34px] md:leading-[42px]">
                    Selamat datang kembali, <span class="text-[var(--color-primary-800)]">{{ $member->nama }}!</span>
                </h1>
                <p class="text-mono mt-1 text-[13px] text-[var(--color-ink-muted)]">NISN {{ $member->nisn }} · {{ $member->kelas }} · {{ $member->jurusan }}</p>
            </div>
            <a href="{{ route('profil.index') }}" class="btn btn-secondary">
                <x-icon name="credit-card" class="h-4 w-4" /> Kartu Anggota Digital
            </a>
        </div>
    </div>

    {{-- Banner jatuh tempo --}}
    @if ($jatuhTempoTerdekat && $jatuhTempoTerdekat->sisaHari() <= 2)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-card)] bg-[var(--color-warning-bg)] p-4 md:p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#FFE7A3] text-[var(--color-warning)]">
                    <x-icon name="alert-triangle" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-[14px] font-bold text-[var(--color-warning)]">
                        @if ($jatuhTempoTerdekat->isTerlambat())
                            Buku "{{ $jatuhTempoTerdekat->judulBukuPertama() }}" telah melewati jatuh tempo!
                        @else
                            Perhatian tenggat sirkulasi: "{{ $jatuhTempoTerdekat->judulBukuPertama() }}" jatuh tempo dalam {{ max(0, $jatuhTempoTerdekat->sisaHari()) }} hari.
                        @endif
                    </p>
                    <p class="mt-0.5 text-[12px] text-[var(--color-warning)]">Batas pengembalian {{ $jatuhTempoTerdekat->tanggal_jatuh_tempo->translatedFormat('d F Y') }} · Denda Rp {{ number_format((int) \App\Models\Setting::get('tarif_denda', 1000), 0, ',', '.') }}/hari.</p>
                </div>
            </div>
            @if ($jatuhTempoTerdekat->isDipinjam() && ! $jatuhTempoTerdekat->sudah_diperpanjang)
                <form method="POST" action="{{ route('pinjaman.perpanjang', $jatuhTempoTerdekat) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-warning"><x-icon name="calendar" class="h-4 w-4" /> Perpanjang Pinjaman</button>
                </form>
            @endif
        </div>
    @endif

    {{-- Metrik --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-metric-card label="Peminjaman Aktif" :value="$totalAktif.' Buku'" icon="book-open"
            :sub="'Maksimal '.$batasPinjam.' buku'" :progress="($totalAktif / max(1, $batasPinjam)) * 100" />
        <x-metric-card label="Jatuh Tempo Terdekat" tone="warning" icon="calendar"
            :value="$jatuhTempoTerdekat ? ($jatuhTempoTerdekat->isTerlambat() ? 'Terlambat' : max(0, $jatuhTempoTerdekat->sisaHari()).' Hari Lagi') : '—'"
            :sub="$jatuhTempoTerdekat?->tanggal_jatuh_tempo->translatedFormat('d M Y') ?? 'Tidak ada pinjaman aktif'" />
        <x-metric-card label="Riwayat Buku Selesai" tone="success" icon="check-circle" :value="$riwayatSelesai" sub="Buku telah dikembalikan" />
        <x-metric-card label="Status Denda" :tone="$totalDenda > 0 ? 'danger' : 'success'" icon="credit-card"
            :value="'Rp '.number_format($totalDenda, 0, ',', '.')" :sub="$totalDenda > 0 ? 'Segera lunasi di meja sirkulasi' : 'Bebas denda'" />
    </div>

    {{-- Buku sedang dipinjam --}}
    <section class="mb-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="section-title">Buku Yang Sedang Dipinjam</h2>
            <a href="{{ route('pinjaman.index') }}" class="link text-[13px]">Seluruh Pinjaman ({{ $totalAktif }})</a>
        </div>

        @if ($pinjamanAktif->isEmpty())
            <x-empty-state icon="book-open" title="Belum ada peminjaman aktif"
                description="Jelajahi katalog dan ajukan peminjaman buku favoritmu.">
                <a href="{{ route('catalog.index') }}" class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Jelajahi Katalog</a>
            </x-empty-state>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($pinjamanAktif as $loan)
                    @php $b = $loan->books->first(); @endphp
                    <div class="card card-pad flex gap-4">
                        <div class="w-20 shrink-0">
                            <x-cover :book="$b" class="aspect-[2/3] w-20" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <x-status-badge :status="$loan->status" />
                                <span class="text-mono text-[11px] font-semibold text-[var(--color-ink-muted)]">{{ $loan->kode_transaksi }}</span>
                            </div>
                            <h3 class="mt-2 line-clamp-2 text-[15px] font-bold text-[var(--color-ink-strong)]">{{ $b->judul }}</h3>
                            <p class="line-clamp-1 text-[12px] text-[var(--color-ink-muted)]">{{ $b->penulisLabel() }} · {{ $b->lokasi_rak }}</p>

                            @if ($loan->status !== \App\Models\Loan::STATUS_DIAJUKAN)
                                <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                                    <div class="rounded-lg bg-[var(--color-bg-muted)] p-2">
                                        <p class="text-[10px] font-semibold text-[var(--color-ink-muted)]">Tgl Pinjam</p>
                                        <p class="text-[12px] font-bold text-[var(--color-ink-strong)]">{{ $loan->tanggal_pinjam->translatedFormat('d M Y') }}</p>
                                    </div>
                                    <div class="rounded-lg {{ $loan->isTerlambat() ? 'bg-[var(--color-danger-bg)]' : 'bg-[var(--color-bg-muted)]' }} p-2">
                                        <p class="text-[10px] font-semibold text-[var(--color-ink-muted)]">Tenggat</p>
                                        <p class="text-[12px] font-bold {{ $loan->isTerlambat() ? 'text-[var(--color-danger)]' : 'text-[var(--color-ink-strong)]' }}">{{ $loan->tanggal_jatuh_tempo->translatedFormat('d M Y') }}</p>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar {{ $loan->isTerlambat() ? 'bg-[var(--color-danger)]' : '' }}" style="width: {{ $loan->kemajuanWaktu() }}%"></div>
                                </div>
                            @else
                                <p class="mt-2 rounded-lg bg-[var(--color-warning-bg)] px-3 py-2 text-[11px] font-semibold text-[var(--color-warning)]">Menunggu verifikasi petugas di meja sirkulasi.</p>
                            @endif

                            <div class="mt-3 flex gap-2">
                                <a href="{{ route('catalog.show', $b->kode_buku) }}" class="btn btn-ghost btn-sm flex-1">Detail</a>
                                @if ($loan->isDipinjam() && ! $loan->sudah_diperpanjang)
                                    <form method="POST" action="{{ route('pinjaman.perpanjang', $loan) }}" class="flex-1">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-secondary btn-sm btn-full"><x-icon name="calendar" class="h-3.5 w-3.5" /> Perpanjang</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Koleksi populer --}}
    <section class="mb-6">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="section-title">Koleksi Populer</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('catalog.index') }}" class="filter-pill">Semua</a>
                @foreach (['RPL', 'TKJ', 'AKL', 'OTKP'] as $j)
                    <a href="{{ route('catalog.index', ['jurusan' => $j]) }}" class="filter-pill">{{ $j }}</a>
                @endforeach
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($populer->take(4) as $item)
                <x-book-card :book="$item" />
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-[var(--radius-card)] bg-gradient-to-r from-[var(--color-primary-700)] to-[var(--color-primary-600)] p-6 text-white md:p-8">
        <div>
            <h2 class="text-[20px] font-extrabold md:text-[24px]">Butuh referensi tugas akhir atau praktikum?</h2>
            <p class="mt-1 text-[13px] text-white/80">Jelajahi seluruh koleksi perpustakaan dan temukan buku yang tepat.</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="btn btn-white">
            <x-icon name="search" class="h-4 w-4" /> Jelajahi Seluruh Katalog
        </a>
    </section>
@endsection
