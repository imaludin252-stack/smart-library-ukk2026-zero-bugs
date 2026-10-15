@extends('layouts.app')

@section('title', 'Riwayat Peminjaman')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Riwayat Peminjaman</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Seluruh transaksi pengembalian buku Anda, lengkap dengan rekap denda.</p>
        </div>
        @if ($bebasPustaka)
            <a href="{{ route('riwayat.bebas') }}" class="btn btn-primary" data-print>
                <x-icon name="download" class="h-4 w-4" /> Unduh Surat Bebas Pustaka
            </a>
        @else
            <button type="button" class="btn btn-ghost" disabled title="Selesaikan seluruh pinjaman & denda terlebih dahulu">
                <x-icon name="lock" class="h-4 w-4" /> Surat Bebas Pustaka Terkunci
            </button>
        @endif
    </div>

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-metric-card label="Total Peminjaman" :value="$totalPinjam.' Buku'" icon="library" sub="Sepanjang riwayat" />
        <x-metric-card label="Tepat Waktu" tone="success" icon="check-circle" :value="$tepatWaktu" :sub="($totalPinjam ? round(($tepatWaktu / max(1, $totalPinjam)) * 100) : 0).'% dari total'" />
        <x-metric-card label="Terlambat" :tone="$terlambat > 0 ? 'danger' : 'success'" icon="clock" :value="$terlambat" :sub="($totalPinjam ? round(($terlambat / max(1, $totalPinjam)) * 100) : 0).'% dari total'" />
        <x-metric-card label="Total Denda" :tone="$totalDenda > 0 ? 'danger' : 'success'" icon="credit-card" :value="'Rp '.number_format($totalDenda, 0, ',', '.')" :sub="$totalDenda > 0 ? 'Dibayarkan di meja sirkulasi' : 'Bebas denda'" />
    </div>

    <div class="card card-pad mb-5">
        <form method="GET" action="{{ route('riwayat.index') }}" class="grid gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <label class="field-label" for="q">Cari Judul Buku</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="Judul buku…">
            </div>
            <div>
                <label class="field-label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Semua</option>
                    <option value="tepat_waktu" @selected($filters['status'] === 'tepat_waktu')>Tepat Waktu</option>
                    <option value="terlambat" @selected($filters['status'] === 'terlambat')>Terlambat</option>
                </select>
            </div>
            <div>
                <label class="field-label" for="dari">Dari Tanggal</label>
                <input id="dari" name="dari" type="date" value="{{ $filters['dari'] }}" class="input">
            </div>
            <div>
                <label class="field-label" for="sampai">Sampai Tanggal</label>
                <input id="sampai" name="sampai" type="date" value="{{ $filters['sampai'] }}" class="input">
            </div>
            <div class="flex gap-2 md:col-span-5">
                <button type="submit" class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> Terapkan Filter</button>
                <a href="{{ route('riwayat.index') }}" class="btn btn-ghost"><x-icon name="refresh" class="h-4 w-4" /> Reset</a>
            </div>
        </form>
    </div>

    @if ($loans->isEmpty())
        <x-empty-state icon="clock" title="Belum ada riwayat pengembalian"
            description="Riwayat akan muncul setelah Anda menyelesaikan peminjaman buku.">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Jelajahi Katalog</a>
        </x-empty-state>
    @else
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <th>Judul Buku</th>
                            <th>Ditangani Petugas</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th class="text-right">Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loans as $loan)
                            @php $b = $loan->books->first(); @endphp
                            <tr>
                                <td class="text-mono text-[12px] font-semibold text-[var(--color-ink-strong)]">{{ $loan->kode_transaksi }}</td>
                                <td>
                                    <p class="font-semibold text-[var(--color-ink-strong)]">{{ $b?->judul }}</p>
                                    <p class="text-mono text-[11px] text-[var(--color-ink-muted)]">{{ $b?->kode_buku }}</p>
                                </td>
                                <td class="text-[12px]">{{ $loan->petugas?->name ?? '—' }}</td>
                                <td class="text-[12px]">{{ $loan->tanggal_pinjam->translatedFormat('d M Y') }}</td>
                                <td class="text-[12px]">{{ $loan->tanggal_kembali?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td>
                                    @if ($loan->denda > 0)
                                        <x-status-badge status="terlambat" />
                                    @else
                                        <x-status-badge status="dikembalikan" />
                                    @endif
                                </td>
                                <td class="text-right text-[12px] font-bold {{ $loan->denda > 0 ? 'text-[var(--color-danger)]' : 'text-[var(--color-ink-muted)]' }}">
                                    Rp {{ number_format($loan->denda, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <x-pagination :paginator="$loans" />
    @endif
@endsection
