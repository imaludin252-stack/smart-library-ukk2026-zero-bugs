@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', 'Katalog Buku')

@section('content')
    <section class="mb-6 rounded-[var(--radius-card)] bg-white p-6 shadow-[var(--shadow-card)] md:p-8">
        <span class="badge badge-info">SISTEM SIRKULASI &amp; KOLEKSI TERPADU</span>
        <h1 class="page-title mt-3">Katalog Buku Perpustakaan</h1>
        <p class="mt-1 text-sm text-[var(--color-ink-muted)]">
            Telusuri lebih dari {{ number_format($totalKoleksi, 0, ',', '.') }} koleksi {{ \App\Models\Setting::get('nama_sekolah', 'SMK Budi Bakti Ciwidey') }}.
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
            <span class="badge badge-neutral"><x-icon name="scan" class="h-3.5 w-3.5" /> Sistem Barcode ISBN</span>
            <span class="badge badge-neutral"><x-icon name="library" class="h-3.5 w-3.5" /> Rak Terbuka Mandiri</span>
        </div>

        <form method="GET" action="{{ route('catalog.index') }}" class="mt-6 space-y-3">
            <div class="flex flex-col gap-3 lg:flex-row">
                <div class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-ink-muted)]" />
                    <input type="search" name="q" value="{{ $filters['q'] }}" class="input pl-11" placeholder="Cari judul, penulis, kategori, ISBN, atau kode buku…">
                </div>
                <select name="kategori" class="select lg:w-56">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['kategori'] === (string) $category->id)>{{ $category->nama }}</option>
                    @endforeach
                </select>
                <select name="jurusan" class="select lg:w-44">
                    <option value="">Semua Jurusan</option>
                    @foreach ($jurusanList as $jurusan)
                        <option value="{{ $jurusan }}" @selected($filters['jurusan'] === $jurusan)>{{ $jurusan }}</option>
                    @endforeach
                </select>
                <select name="urut" class="select lg:w-44">
                    <option value="terbaru" @selected($filters['urut'] === 'terbaru')>Terbaru</option>
                    <option value="judul_asc" @selected($filters['urut'] === 'judul_asc')>Judul A–Z</option>
                    <option value="judul_desc" @selected($filters['urut'] === 'judul_desc')>Judul Z–A</option>
                    <option value="populer" @selected($filters['urut'] === 'populer')>Terpopuler</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="search" class="h-4 w-4" /> Cari
                </button>
            </div>
            @if ($filters['status'])
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif
        </form>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <a href="{{ route('catalog.index', array_filter(['q' => $filters['q'], 'kategori' => $filters['kategori'], 'jurusan' => $filters['jurusan'], 'urut' => $filters['urut']])) }}"
                class="filter-pill {{ ! $filters['status'] ? 'filter-pill-active' : '' }}">Semua Koleksi {{ $totalKoleksi }}</a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'tersedia', 'page' => null]) }}"
                class="filter-pill {{ $filters['status'] === 'tersedia' ? 'filter-pill-active' : '' }}">Tersedia {{ $totalTersedia }}</a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'dipinjam', 'page' => null]) }}"
                class="filter-pill {{ $filters['status'] === 'dipinjam' ? 'filter-pill-active' : '' }}">Sedang Dipinjam {{ $totalDipinjam }}</a>
        </div>
    </section>

    <div class="mb-4 flex items-center justify-between">
        <p class="text-[13px] font-semibold text-[var(--color-ink-muted)]">
            Menampilkan {{ $books->firstItem() ?? 0 }}–{{ $books->lastItem() ?? 0 }} dari {{ $books->total() }} judul buku
        </p>
    </div>

    @if ($books->isEmpty())
        <x-empty-state icon="search" title="Buku tidak ditemukan"
            description="Coba gunakan kata kunci lain atau kosongkan filter kategori dan status pencarian.">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary">
                <x-icon name="refresh" class="h-4 w-4" /> Reset Pencarian
            </a>
        </x-empty-state>
    @else
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
            @foreach ($books as $book)
                <x-book-card :book="$book" />
            @endforeach
        </div>
        <x-pagination :paginator="$books" />
    @endif
@endsection
