@extends('layouts.app')

@section('title', 'Data Koleksi Buku')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Data Koleksi Buku</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Kelola katalog, stok, dan status koleksi perpustakaan.</p>
        </div>
        <a href="{{ route('petugas.koleksi.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Tambah Buku
        </a>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-metric-card label="Total Judul" icon="library" :value="number_format($totalJudul, 0, ',', '.')" sub="Judul terdaftar" />
        <x-metric-card label="Total Eksemplar" tone="info" icon="package" :value="number_format($totalEksemplar, 0, ',', '.')" sub="Seluruh stok fisik" />
        <x-metric-card label="Tersedia" tone="success" icon="check-circle" :value="number_format($totalTersedia, 0, ',', '.')" sub="Siap dipinjam" />
        <x-metric-card label="Sedang Dipinjam" tone="warning" icon="book-open" :value="number_format($totalDipinjam, 0, ',', '.')" sub="Sedang beredar" />
    </div>

    <div class="card card-pad mb-5">
        <form method="GET" action="{{ route('petugas.koleksi.index') }}" class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="field-label" for="q">Pencarian</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="Judul, kode buku, ISBN, atau penulis…">
            </div>
            <div>
                <label class="field-label" for="kategori">Kategori</label>
                <select id="kategori" name="kategori" class="select">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['kategori'] === (string) $category->id)>{{ $category->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="jurusan">Program Keahlian</label>
                <select id="jurusan" name="jurusan" class="select">
                    <option value="">Semua Jurusan</option>
                    @foreach ($jurusanList as $jurusan)
                        <option value="{{ $jurusan }}" @selected($filters['jurusan'] === $jurusan)>{{ $jurusan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Semua Status</option>
                    <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Nonaktif</option>
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-3">
                <button type="submit" class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> Terapkan</button>
                <a href="{{ route('petugas.koleksi.index') }}" class="btn btn-ghost"><x-icon name="refresh" class="h-4 w-4" /> Reset</a>
            </div>
        </form>
    </div>

    @if ($books->isEmpty())
        <x-empty-state icon="library" title="Belum ada koleksi" description="Tambahkan buku pertama atau sesuaikan filter pencarian.">
            <a href="{{ route('petugas.koleksi.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Buku</a>
        </x-empty-state>
    @else
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Buku</th>
                            <th>Kategori</th>
                            <th>Jurusan</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($books as $book)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 shrink-0">
                                            <x-cover :book="$book" class="aspect-[2/3] w-10" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="line-clamp-1 font-semibold text-[var(--color-ink-strong)]">{{ $book->judul }}</p>
                                            <p class="text-mono text-[11px] text-[var(--color-ink-muted)]">{{ $book->kode_buku }} · {{ $book->penulisLabel() }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-[12px]">{{ $book->kategori->nama }}</td>
                                <td><span class="badge badge-neutral">{{ $book->jurusan }}</span></td>
                                <td class="text-[12px]">
                                    <span class="font-bold text-[var(--color-ink-strong)]">{{ $book->tersedia() }}</span>
                                    <span class="text-[var(--color-ink-muted)]">/ {{ $book->stok }}</span>
                                </td>
                                <td><x-status-badge :status="$book->isNonaktif() ? 'nonaktif' : ($book->isTersedia() ? 'tersedia' : 'habis')" /></td>
                                <td>
                                    <div class="flex justify-end gap-1.5">
                                        <a href="{{ route('catalog.show', $book->kode_buku) }}" class="btn btn-ghost btn-sm" title="Lihat"><x-icon name="eye" class="h-3.5 w-3.5" /></a>
                                        <a href="{{ route('petugas.koleksi.edit', $book) }}" class="btn btn-secondary btn-sm" title="Ubah"><x-icon name="pencil" class="h-3.5 w-3.5" /></a>
                                        <form method="POST" action="{{ route('petugas.koleksi.status', $book) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn {{ $book->isNonaktif() ? 'btn-success' : 'btn-ghost' }} btn-sm" title="{{ $book->isNonaktif() ? 'Aktifkan' : 'Nonaktifkan' }}">
                                                <x-icon name="refresh" class="h-3.5 w-3.5" />
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('petugas.koleksi.destroy', $book) }}" data-confirm="Hapus buku '{{ $book->judul }}' secara permanen?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-danger-ghost btn-sm" title="Hapus"><x-icon name="trash" class="h-3.5 w-3.5" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <x-pagination :paginator="$books" />
    @endif
@endsection
