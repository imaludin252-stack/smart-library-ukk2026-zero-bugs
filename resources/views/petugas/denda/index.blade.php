@extends('layouts.app')

@section('title', 'Denda')

@section('content')
    <x-breadcrumb :items="[['label' => 'Denda']]" />

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Rekap Denda</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Pantau dan kelola denda keterlambatan pengembalian buku anggota.</p>
        </div>
        <a href="{{ route('petugas.sirkulasi.index', ['tab' => 'selesai']) }}" class="btn btn-secondary">
            <x-icon name="refresh" class="h-4 w-4" /> Pengembalian Hari Ini
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 flex items-start gap-2 rounded-xl bg-[var(--color-danger-bg)] px-4 py-3 text-[13px] font-semibold text-[var(--color-danger)]">
            <x-icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $rupiah = fn (int $nilai): string => 'Rp'."\u{00A0}".number_format($nilai, 0, ',', '.');
    @endphp

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-metric-card label="Total Denda" tone="warning" icon="credit-card" :value="$rupiah($totalDenda)" :sub="$jumlahTransaksi.' transaksi'" />
        <x-metric-card label="Belum Lunas" tone="danger" icon="alert-triangle" :value="$rupiah($dendaBelumLunas)" sub="Perlu ditagih" />
        <x-metric-card label="Sudah Lunas" tone="success" icon="check-circle" :value="$rupiah($dendaLunas)" sub="Sudah dibayar" />
        <x-metric-card label="Transaksi Denda" tone="info" icon="file-text" :value="number_format($jumlahTransaksi, 0, ',', '.')" sub="Pengembalian terlambat" />
    </div>

    <div class="card card-pad mb-5">
        <form method="GET" action="{{ route('petugas.denda.index') }}" class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="field-label" for="q">Pencarian</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="Nama anggota, NISN, kelas, judul buku, atau kode transaksi…">
            </div>
            <div>
                <label class="field-label" for="status">Status Denda</label>
                <select id="status" name="status" class="select">
                    <option value="">Semua Status</option>
                    <option value="belum_lunas" @selected($filters['status'] === 'belum_lunas')>Belum Lunas</option>
                    <option value="lunas" @selected($filters['status'] === 'lunas')>Lunas</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="btn btn-primary btn-full"><x-icon name="filter" class="h-4 w-4" /> Terapkan</button>
                <a href="{{ route('petugas.denda.index') }}" class="btn btn-ghost"><x-icon name="refresh" class="h-4 w-4" /> Reset</a>
            </div>
        </form>
    </div>

    @if ($loans->isEmpty())
        <x-empty-state icon="credit-card" title="Belum ada denda" description="Denda akan tercatat di sini setelah buku dikembalikan melewati tanggal jatuh tempo.">
            <a href="{{ route('petugas.sirkulasi.index') }}" class="btn btn-primary">
                <x-icon name="refresh" class="h-4 w-4" /> Buka Meja Sirkulasi
            </a>
        </x-empty-state>
    @else
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Anggota</th>
                            <th>Kelas</th>
                            <th>Judul Buku</th>
                            <th>Tanggal Kembali</th>
                            <th class="text-right">Denda</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loans as $loan)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-[12px] font-bold text-[var(--color-primary-800)]">
                                            {{ $loan->member?->user?->initials() ?? '?' }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-[var(--color-ink-strong)]">{{ $loan->member?->nama ?? '-' }}</p>
                                            <p class="text-mono text-[11px] text-[var(--color-ink-muted)]">{{ $loan->member?->nisn ?? '-' }}</p>
                                            <p class="text-mono text-[11px] text-[var(--color-ink-muted)]">{{ $loan->kode_transaksi }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap text-[12px]">{{ $loan->member?->kelas ?? '-' }}</td>
                                <td class="max-w-[240px]">
                                    @forelse ($loan->books as $book)
                                        <p class="line-clamp-1 font-semibold text-[var(--color-ink-strong)]">{{ $book->judul }}</p>
                                        <p class="text-mono text-[11px] text-[var(--color-ink-muted)]">{{ $book->kode_buku }}</p>
                                    @empty
                                        <span class="text-[13px] text-[var(--color-ink-muted)]">-</span>
                                    @endforelse
                                </td>
                                <td class="whitespace-nowrap text-[12px]">{{ $loan->tanggal_kembali?->translatedFormat('d M Y') ?? '-' }}</td>
                                <td class="whitespace-nowrap text-right text-[13px] font-bold text-[var(--color-danger)]">
                                    {{ $rupiah($loan->denda) }}
                                </td>
                                <td class="whitespace-nowrap">
                                    <x-status-badge :status="$loan->status_denda" />
                                    @if ($loan->isDendaLunas() && $loan->tanggal_bayar_denda)
                                        <p class="mt-1 text-[10px] text-[var(--color-ink-muted)]">Dibayar {{ $loan->tanggal_bayar_denda->translatedFormat('d M Y') }}</p>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button type="button" data-modal-toggle="edit-denda-{{ $loan->id_peminjaman }}" class="btn btn-secondary btn-sm" aria-label="Edit denda {{ $loan->kode_transaksi }}">
                                        <x-icon name="pencil" class="h-3.5 w-3.5" /> Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <x-pagination :paginator="$loans" />
    @endif

    @foreach ($loans as $loan)
        <div data-modal="edit-denda-{{ $loan->id_peminjaman }}" class="modal-backdrop hidden">
            <div class="modal-panel">
                <form method="POST" action="{{ route('petugas.denda.update', $loan) }}">
                    @csrf @method('PUT')
                    <div class="flex items-start justify-between border-b border-[var(--color-line)] p-5">
                        <div>
                            <h3 class="section-title">Edit Denda</h3>
                            <p class="text-mono mt-1 text-[12px] text-[var(--color-ink-muted)]">{{ $loan->kode_transaksi }}</p>
                        </div>
                        <button type="button" data-modal-close="edit-denda-{{ $loan->id_peminjaman }}" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]" aria-label="Tutup">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                            <p class="text-[14px] font-bold text-[var(--color-ink-strong)]">{{ $loan->member?->nama ?? '-' }}</p>
                            <p class="text-[12px] text-[var(--color-ink-muted)]">{{ $loan->member?->kelas ?? '-' }} · NISN {{ $loan->member?->nisn ?? '-' }}</p>
                            <p class="mt-2 text-[13px] font-semibold text-[var(--color-ink-body)]">{{ $loan->books->pluck('judul')->join(', ') ?: '-' }}</p>
                            <p class="text-[12px] text-[var(--color-ink-muted)]">Dikembalikan {{ $loan->tanggal_kembali?->translatedFormat('d M Y') ?? '-' }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="field-label" for="denda-{{ $loan->id_peminjaman }}">Denda (Rp)</label>
                                <input id="denda-{{ $loan->id_peminjaman }}" name="denda" type="number" min="0" max="100000000" value="{{ $loan->denda }}" class="input text-mono" required>
                            </div>
                            <div>
                                <label class="field-label" for="status-denda-{{ $loan->id_peminjaman }}">Status Denda</label>
                                <select id="status-denda-{{ $loan->id_peminjaman }}" name="status_denda" class="select" required>
                                    <option value="belum_lunas" @selected($loan->status_denda === 'belum_lunas')>Belum Lunas</option>
                                    <option value="lunas" @selected($loan->status_denda === 'lunas')>Lunas</option>
                                </select>
                            </div>
                        </div>
                        <p class="text-[11px] text-[var(--color-ink-muted)]">Mengubah status menjadi Lunas akan mencatat tanggal pembayaran otomatis. Perubahan denda ikut memengaruhi total pada laporan.</p>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                        <button type="button" data-modal-close="edit-denda-{{ $loan->id_peminjaman }}" class="btn btn-ghost">Batal</button>
                        <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
