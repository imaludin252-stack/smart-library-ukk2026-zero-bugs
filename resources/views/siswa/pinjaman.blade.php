@extends('layouts.app')

@section('title', 'Peminjaman Saya')

@section('content')
    @php
        $tabs = [
            'semua' => ['label' => 'Semua', 'count' => $jumlahAktif + $jumlahVerifikasi + $jumlahSelesai + $jumlahTerlambat],
            'aktif' => ['label' => 'Sedang Dipinjam', 'count' => $jumlahAktif],
            'verifikasi' => ['label' => 'Menunggu Verifikasi', 'count' => $jumlahVerifikasi],
            'terlambat' => ['label' => 'Terlambat', 'count' => $jumlahTerlambat],
            'selesai' => ['label' => 'Selesai', 'count' => $jumlahSelesai],
        ];
        $persenKuota = $batas > 0 ? min(100, ($kuota / $batas) * 100) : 0;
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Peminjaman Saya</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Pantau status pengajuan, jatuh tempo, dan pengembalian buku Anda.</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Ajukan Pinjam Baru
        </a>
    </div>

    <div class="card card-pad mb-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="metric-icon"><x-icon name="book-open" class="h-5 w-5" /></span>
                <div>
                    <p class="label-caps">Kuota Peminjaman Aktif</p>
                    <p class="text-[15px] font-bold text-[var(--color-ink-strong)]">{{ $kuota }} dari {{ $batas }} buku</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if ($totalDenda > 0)
                    <span class="badge badge-danger"><x-icon name="alert-triangle" class="h-3.5 w-3.5" /> Denda Rp {{ number_format($totalDenda, 0, ',', '.') }}</span>
                @else
                    <span class="badge badge-success"><x-icon name="check-circle" class="h-3.5 w-3.5" /> Bebas Denda</span>
                @endif
                @unless ($dapatPinjam)
                    <span class="badge badge-warning"><x-icon name="alert-triangle" class="h-3.5 w-3.5" /> Ada Keterlambatan</span>
                @endunless
            </div>
        </div>
        <div class="progress mt-4">
            <div class="progress-bar {{ $persenKuota >= 100 ? 'bg-[var(--color-danger)]' : '' }}" style="width: {{ $persenKuota }}%"></div>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $item)
            <a href="{{ route('pinjaman.index', ['tab' => $key]) }}"
                class="filter-pill {{ $tab === $key ? 'filter-pill-active' : '' }}">
                {{ $item['label'] }}
                <span class="rounded-full bg-black/5 px-1.5 text-[11px] font-bold">{{ $item['count'] }}</span>
            </a>
        @endforeach
    </div>

    @if ($loans->isEmpty())
        <x-empty-state icon="clipboard" title="Tidak ada data pada kategori ini"
            description="Belum ada transaksi peminjaman pada filter yang Anda pilih.">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Jelajahi Katalog</a>
        </x-empty-state>
    @else
        <div class="space-y-4">
            @foreach ($loans as $loan)
                @php $b = $loan->books->first(); @endphp
                <div class="card card-pad">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <div class="w-24 shrink-0 self-start">
                            <x-cover :book="$b" class="aspect-[2/3] w-24" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-status-badge :status="$loan->status" />
                                <span class="text-mono text-[11px] font-semibold text-[var(--color-ink-muted)]">{{ $loan->kode_transaksi }}</span>
                                @if ($loan->sisaHari() >= 0 && ! $loan->isSelesai())
                                    <span class="badge badge-neutral">{{ $loan->sisaHari() }} hari tersisa</span>
                                @endif
                            </div>
                            <h3 class="mt-2 text-[16px] font-bold text-[var(--color-ink-strong)]">{{ $b->judul }}</h3>
                            <p class="text-[12px] text-[var(--color-ink-muted)]">{{ $b->penulisLabel() }} · {{ $b->lokasi_rak }}</p>

                            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <div class="rounded-lg bg-[var(--color-bg-muted)] p-2.5">
                                    <p class="label-caps">Tgl Pinjam</p>
                                    <p class="text-[12px] font-bold text-[var(--color-ink-strong)]">{{ $loan->tanggal_pinjam->translatedFormat('d M Y') }}</p>
                                </div>
                                <div class="rounded-lg bg-[var(--color-bg-muted)] p-2.5">
                                    <p class="label-caps">Jatuh Tempo</p>
                                    <p class="text-[12px] font-bold {{ $loan->isTerlambat() ? 'text-[var(--color-danger)]' : 'text-[var(--color-ink-strong)]' }}">{{ $loan->tanggal_jatuh_tempo->translatedFormat('d M Y') }}</p>
                                </div>
                                <div class="rounded-lg bg-[var(--color-bg-muted)] p-2.5">
                                    <p class="label-caps">Tgl Kembali</p>
                                    <p class="text-[12px] font-bold text-[var(--color-ink-strong)]">{{ $loan->tanggal_kembali?->translatedFormat('d M Y') ?? '—' }}</p>
                                </div>
                                <div class="rounded-lg {{ $loan->denda > 0 ? 'bg-[var(--color-danger-bg)]' : 'bg-[var(--color-bg-muted)]' }} p-2.5">
                                    <p class="label-caps">Denda</p>
                                    <p class="text-[12px] font-bold {{ $loan->denda > 0 ? 'text-[var(--color-danger)]' : 'text-[var(--color-ink-strong)]' }}">Rp {{ number_format($loan->denda, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            @if ($loan->catatan)
                                <p class="mt-2 rounded-lg bg-[var(--color-warning-bg)] px-3 py-2 text-[12px] font-medium text-[var(--color-warning)]">Catatan petugas: {{ $loan->catatan }}</p>
                            @endif

                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('catalog.show', $b->kode_buku) }}" class="btn btn-ghost btn-sm">
                                    <x-icon name="eye" class="h-3.5 w-3.5" /> Detail Buku
                                </a>
                                @if ($loan->isMenunggu())
                                    <form method="POST" action="{{ route('pinjaman.batal', $loan) }}" data-confirm="Batalkan permohonan peminjaman ini?">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-danger-ghost btn-sm"><x-icon name="x" class="h-3.5 w-3.5" /> Batalkan Permohonan</button>
                                    </form>
                                @endif
                                @if ($loan->isDipinjam() && ! $loan->sudah_diperpanjang)
                                    <form method="POST" action="{{ route('pinjaman.perpanjang', $loan) }}" data-confirm="Perpanjang pinjaman selama {{ \App\Models\Setting::get('durasi_pinjam', 7) }} hari?">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-secondary btn-sm"><x-icon name="calendar" class="h-3.5 w-3.5" /> Perpanjang</button>
                                    </form>
                                @endif
                                @if ($loan->isTerlambat())
                                    <span class="badge badge-danger"><x-icon name="alert-triangle" class="h-3.5 w-3.5" /> Harap segera kembalikan ke meja sirkulasi</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <x-pagination :paginator="$loans" />
    @endif
@endsection
