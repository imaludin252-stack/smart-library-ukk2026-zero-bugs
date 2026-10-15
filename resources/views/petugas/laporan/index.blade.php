@extends('layouts.app')

@section('title', 'Laporan & Rekap')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Laporan &amp; Rekap</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Analisis sirkulasi dan unduh laporan periode {{ \Illuminate\Support\Carbon::parse($dari)->translatedFormat('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($sampai)->translatedFormat('d M Y') }}.</p>
        </div>
    </div>

    <div class="card card-pad mb-5">
        <form method="GET" action="{{ route('petugas.laporan.index') }}" class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="field-label" for="dari">Dari Tanggal</label>
                <input id="dari" name="dari" type="date" value="{{ $dari }}" class="input">
            </div>
            <div>
                <label class="field-label" for="sampai">Sampai Tanggal</label>
                <input id="sampai" name="sampai" type="date" value="{{ $sampai }}" class="input">
            </div>
            <div class="flex items-end">
                <button class="btn btn-primary btn-full"><x-icon name="filter" class="h-4 w-4" /> Tampilkan</button>
            </div>
            <div class="flex items-end gap-2">
                <a href="{{ route('petugas.laporan.pdf', ['jenis' => 'transaksi', 'dari' => $dari, 'sampai' => $sampai]) }}" class="btn btn-secondary btn-full">
                    <x-icon name="download" class="h-4 w-4" /> PDF Transaksi
                </a>
            </div>
        </form>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ route('petugas.laporan.pdf', ['jenis' => 'terlambat', 'dari' => $dari, 'sampai' => $sampai]) }}" class="btn btn-ghost btn-sm"><x-icon name="alert-triangle" class="h-3.5 w-3.5" /> PDF Buku Terlambat</a>
            <a href="{{ route('petugas.laporan.pdf', ['jenis' => 'populer', 'dari' => $dari, 'sampai' => $sampai]) }}" class="btn btn-ghost btn-sm"><x-icon name="trending-up" class="h-3.5 w-3.5" /> PDF Buku Terpopuler</a>
        </div>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-metric-card label="Total Transaksi" icon="clipboard" :value="number_format($totalTransaksi, 0, ',', '.')" />
        <x-metric-card label="Siswa Meminjam" tone="info" icon="users" :value="number_format($siswaAktif, 0, ',', '.')" />
        <x-metric-card label="Selesai" tone="success" icon="check-circle" :value="number_format($totalSelesai, 0, ',', '.')" />
        <x-metric-card label="Terlambat" :tone="$totalTerlambat > 0 ? 'danger' : 'success'" icon="clock" :value="number_format($totalTerlambat, 0, ',', '.')" />
        <x-metric-card label="Denda Terkumpul" tone="warning" icon="credit-card" :value="'Rp '.number_format($dendaTerkumpul, 0, ',', '.')" />
    </div>

    <div class="card card-pad mb-5">
        <h2 class="section-title">Tren Peminjaman 6 Bulan Terakhir</h2>
        <div class="mt-5 flex items-end justify-between gap-3" style="height: 180px">
            @foreach ($trenBulanan as $bulan)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <span class="text-mono text-[11px] font-bold text-[var(--color-ink-strong)]">{{ $bulan['total'] }}</span>
                    <div class="flex w-full flex-1 items-end">
                        <div class="w-full rounded-t-lg bg-[var(--color-primary-800)] transition-all" style="height: {{ max(4, ($bulan['total'] / $maxTren) * 100) }}%"></div>
                    </div>
                    <span class="text-[11px] font-semibold text-[var(--color-ink-muted)]">{{ $bulan['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="card card-pad">
            <h2 class="section-title">Buku Terpopuler</h2>
            <div class="mt-4 space-y-3">
                @forelse ($bukuPopuler as $i => $book)
                    <div>
                        <div class="flex items-center justify-between text-[13px]">
                            <span class="flex items-center gap-2 font-semibold text-[var(--color-ink-strong)]">
                                <span class="text-mono text-[var(--color-ink-muted)]">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="line-clamp-1">{{ $book->judul }}</span>
                            </span>
                            <span class="shrink-0 text-mono text-[12px] text-[var(--color-ink-muted)]">{{ $book->total_pinjam }}x</span>
                        </div>
                        <div class="progress mt-1.5">
                            <div class="progress-bar" style="width: {{ ($book->total_pinjam / $maxPopuler) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-[13px] text-[var(--color-ink-muted)]">Belum ada data peminjaman pada periode ini.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-5">
            <div class="card card-pad">
                <h2 class="section-title">Rekap per Kelas</h2>
                <div class="table-wrap mt-3">
                    <table class="table">
                        <thead><tr><th>Kelas</th><th class="text-right">Transaksi</th></tr></thead>
                        <tbody>
                            @forelse ($rekapKelas as $row)
                                <tr><td class="text-[13px] font-semibold">{{ $row->kelas }}</td><td class="text-right text-mono text-[12px]">{{ $row->total }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="py-6 text-center text-[13px] text-[var(--color-ink-muted)]">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="section-title">Distribusi per Jurusan</h2>
                <div class="mt-4 space-y-3">
                    @foreach (['RPL', 'TKJ', 'AKL', 'OTKP'] as $jurusan)
                        @php $jumlah = (int) ($distribusiJurusan[$jurusan] ?? 0); @endphp
                        <div>
                            <div class="flex items-center justify-between text-[12px] font-semibold">
                                <span>{{ $jurusan }}</span>
                                <span class="text-mono text-[var(--color-ink-muted)]">{{ $jumlah }}</span>
                            </div>
                            <div class="progress mt-1.5">
                                <div class="progress-bar" style="width: {{ ($jumlah / $maxJurusan) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
