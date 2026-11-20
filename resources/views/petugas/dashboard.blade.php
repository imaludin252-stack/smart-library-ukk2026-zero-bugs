@extends('layouts.app')

@section('title', 'Dashboard Petugas')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Dashboard Petugas</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">{{ $tanggal }} · Jam layanan {{ \App\Models\Setting::get('jam_buka', 'Senin–Jumat, 07.30–15.30 WIB') }}</p>
        </div>
        <a href="{{ route('petugas.sirkulasi.index') }}" class="btn btn-primary">
            <x-icon name="refresh" class="h-4 w-4" /> Buka Meja Sirkulasi
        </a>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-metric-card label="Total Koleksi" icon="library" :value="number_format($totalKoleksi, 0, ',', '.')" sub="Eksemplar aktif" />
        <x-metric-card label="Sedang Dipinjam" tone="info" icon="book-open" :value="$sedangDipinjam" sub="Termasuk yang terlambat" />
        <x-metric-card label="Jatuh Tempo Hari Ini" tone="warning" icon="calendar" :value="$jatuhTempoHariIni" sub="Perlu pengingatan" />
        <x-metric-card label="Terlambat" :tone="$jumlahTerlambat > 0 ? 'danger' : 'success'" icon="alert-triangle" :value="$jumlahTerlambat"
            :sub="'Akumulasi denda Rp '.number_format($totalDenda, 0, ',', '.')" />
    </div>

    <div class="grid gap-5 lg:grid-cols-5">
        {{-- Antrian permohonan --}}
        <div class="card card-pad lg:col-span-3">
            <div class="flex items-center justify-between">
                <h2 class="section-title">Menunggu Verifikasi</h2>
                <span class="badge badge-warning">{{ $permohonan->count() }} Permohonan</span>
            </div>

            @if ($permohonan->isEmpty())
                <div class="mt-4 rounded-xl bg-[var(--color-success-bg)] px-4 py-6 text-center text-[13px] font-semibold text-[var(--color-success)]">
                    <x-icon name="check-circle" class="mx-auto mb-1 h-6 w-6" />
                    Tidak ada permohonan yang menunggu. Semua sudah ditangani.
                </div>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($permohonan as $loan)
                        @php $b = $loan->books->first(); @endphp
                        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-[var(--color-line)] p-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-[13px] font-bold text-[var(--color-primary-800)]">
                                {{ $loan->member->user->initials() }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] font-bold text-[var(--color-ink-strong)]">{{ $loan->member->nama }}</p>
                                <p class="line-clamp-1 text-[12px] text-[var(--color-ink-muted)]">{{ $b->judul }} · <span class="text-mono">{{ $loan->kode_transaksi }}</span></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('petugas.sirkulasi.verifikasi', $loan) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-success btn-sm"><x-icon name="check" class="h-3.5 w-3.5" /> Verifikasi</button>
                                </form>
                                <a href="{{ route('petugas.sirkulasi.index', ['tab' => 'permohonan']) }}" class="btn btn-ghost btn-sm">Detail</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Perlu perhatian --}}
        <div class="card card-pad lg:col-span-2">
            <h2 class="section-title">Perlu Perhatian</h2>
            <p class="mt-1 text-[12px] text-[var(--color-ink-muted)]">Terlambat atau jatuh tempo ≤ 2 hari.</p>

            @if ($perhatian->isEmpty())
                <p class="mt-4 rounded-xl bg-[var(--color-bg-muted)] px-4 py-6 text-center text-[13px] font-semibold text-[var(--color-ink-muted)]">
                    Tidak ada pinjaman yang mendesak.
                </p>
            @else
                <div class="mt-4 space-y-2.5">
                    @foreach ($perhatian as $loan)
                        @php $b = $loan->books->first(); @endphp
                        <div class="rounded-xl border border-[var(--color-line)] p-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[13px] font-bold text-[var(--color-ink-strong)]">{{ $loan->member->nama }}</p>
                                <x-status-badge :status="$loan->isTerlambat() ? 'terlambat' : 'mendekati'" />
                            </div>
                            <p class="line-clamp-1 text-[12px] text-[var(--color-ink-muted)]">{{ $b->judul }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-[var(--color-ink-muted)]">
                                Tenggat {{ $loan->tanggal_jatuh_tempo->translatedFormat('d M Y') }}
                                @if ($loan->isTerlambat())
                                    · <span class="text-[var(--color-danger)]">{{ $loan->hariTerlambat() }} hari · denda Rp {{ number_format($loan->hitungDenda(), 0, ',', '.') }}</span>
                                @else
                                    · {{ $loan->sisaHari() }} hari lagi
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Distribusi koleksi --}}
    <div class="card card-pad mt-5">
        <div class="flex items-center justify-between">
            <h2 class="section-title">Distribusi Koleksi per Program Keahlian</h2>
            <a href="{{ route('petugas.koleksi.index') }}" class="link text-[13px]">Kelola Koleksi</a>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach ($programKeahlian as $jurusan)
                @php $jumlah = (int) ($distribusi[$jurusan] ?? 0); @endphp
                <div>
                    <div class="flex items-center justify-between text-[12px] font-semibold">
                        <span class="text-[var(--color-ink-body)]">{{ $jurusan }}</span>
                        <span class="text-mono text-[var(--color-ink-muted)]">{{ $jumlah }}</span>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar" style="width: {{ ($jumlah / $maxDistribusi) * 100 }}%"></div>
                    </div>
                    @if ($jumlah === 0)
                        <p class="mt-1.5 text-[11px] text-[var(--color-ink-muted)]">Belum ada koleksi</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- SKKNI 5: tabel ringkasan hasil rekap agregat per kategori. --}}
    <div class="card card-pad mt-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="section-title">Rekap Koleksi per Kategori</h2>
                <p class="mt-1 text-[12px] text-[var(--color-ink-muted)]">Jumlah judul dan total eksemplar aktif pada setiap kategori koleksi.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="badge badge-info">{{ $rekapKategori->count() }} Kategori</span>
                <a href="{{ route('petugas.koleksi.index') }}" class="link text-[13px]">Kelola Koleksi</a>
            </div>
        </div>

        <div class="table-wrap mt-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th class="text-right">Jumlah Judul</th>
                        <th class="text-right">Total Eksemplar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekapKategori as $rekap)
                        <tr>
                            <td class="font-semibold text-[var(--color-ink-strong)]">{{ $rekap->nama }}</td>
                            <td class="text-right text-mono">{{ number_format((int) $rekap->total_data, 0, ',', '.') }}</td>
                            <td class="text-right text-mono">{{ number_format((int) $rekap->total_stok, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-[13px] text-[var(--color-ink-muted)]">Belum ada kategori koleksi.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($rekapKategori->isNotEmpty())
                    <tfoot>
                        <tr class="border-t border-[var(--color-line)] font-bold text-[var(--color-ink-strong)]">
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 text-right text-mono">{{ number_format($totalJudul, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-mono">{{ number_format($totalEksemplar, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
