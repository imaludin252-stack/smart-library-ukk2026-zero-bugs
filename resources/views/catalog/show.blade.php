@extends(auth()->check() ? 'layouts.app' : 'layouts.public')

@section('title', $book->judul)

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap items-center gap-1.5 text-[12px] font-semibold text-[var(--color-ink-muted)]">
            <a href="{{ route('catalog.index') }}" class="hover:text-[var(--color-primary-800)]">Katalog Buku</a>
            <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            <span>{{ $book->kategori->nama }}</span>
            <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            <span class="text-[var(--color-ink-strong)]">{{ $book->judul }}</span>
        </nav>
        <a href="{{ route('catalog.index') }}" class="btn btn-ghost btn-sm">
            <x-icon name="arrow-left" class="h-4 w-4" /> Kembali ke Daftar
        </a>
    </div>

    @php $tersedia = $book->isTersedia(); @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Kolom kiri --}}
        <div class="space-y-4">
            <div class="card p-4">
                <x-cover :book="$book" class="aspect-[2/3] w-full" />
            </div>
            <div class="card card-pad space-y-3">
                <div>
                    <p class="label-caps">Nomor Panggil</p>
                    <p class="text-mono mt-1 text-[13px] font-semibold text-[var(--color-ink-strong)]">{{ $book->nomor_panggil ?? '-' }}</p>
                </div>
                <div class="divider"></div>
                <div>
                    <p class="label-caps">Lokasi Rak Fisik</p>
                    <p class="mt-1 text-[13px] font-semibold text-[var(--color-ink-strong)]">{{ $book->lokasi_rak ?? 'Belum diatur' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 rounded-[var(--radius-card)] bg-[var(--color-success-bg)] p-4 text-[12px] font-bold text-[var(--color-success)]">
                <x-icon name="check-circle" class="h-5 w-5" /> Koleksi Terverifikasi Kurikulum Merdeka
            </div>
        </div>

        {{-- Kolom kanan --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="card card-pad">
                <div class="flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$tersedia ? 'tersedia' : ($book->isNonaktif() ? 'nonaktif' : 'habis')"
                        :label="$tersedia ? 'TERSEDIA UNTUK DIPINJAM · '.$book->tersedia().' Eksemplar' : ($book->isNonaktif() ? 'NONAKTIF' : 'DIPINJAM SEMUA')" />
                    <span class="text-mono text-[12px] font-semibold text-[var(--color-ink-muted)]">{{ $book->kode_buku }}</span>
                </div>

                <h1 class="mt-3 text-[26px] font-extrabold leading-8 text-[var(--color-ink-strong)] md:text-[32px] md:leading-10">{{ $book->judul }}</h1>

                <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="label-caps">Penulis</p>
                        <p class="mt-1 text-[13px] font-semibold text-[var(--color-ink-strong)]">{{ $book->penulisLabel() }}</p>
                    </div>
                    <div>
                        <p class="label-caps">Kategori</p>
                        <p class="mt-1 text-[13px] font-semibold text-[var(--color-ink-strong)]">{{ $book->kategori->nama }}</p>
                    </div>
                    <div>
                        <p class="label-caps">Penerbit</p>
                        <p class="mt-1 text-[13px] font-semibold text-[var(--color-ink-strong)]">{{ $book->penerbit ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="label-caps">Tahun Terbit</p>
                        <p class="mt-1 text-[13px] font-semibold text-[var(--color-ink-strong)]">{{ $book->tahun ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="label-caps">ISBN</p>
                        <p class="text-mono mt-1 text-[12px] font-semibold text-[var(--color-ink-strong)]">{{ $book->isbn ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="label-caps">Tebal Halaman</p>
                        <p class="mt-1 text-[13px] font-semibold text-[var(--color-ink-strong)]">{{ $book->tebal_halaman ? $book->tebal_halaman.' hlm' : '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="section-title">Ketersediaan Fisik</h2>
                @php
                    $dipinjam = $book->stok - $book->tersedia();
                    $persen = $book->stok > 0 ? (int) round(($book->tersedia() / $book->stok) * 100) : 0;
                @endphp
                <div class="mt-4">
                    <div class="flex items-center justify-between text-[12px] font-semibold">
                        <span class="text-[var(--color-ink-muted)]">Persentase tersedia</span>
                        <span class="text-[var(--color-ink-strong)]">{{ $persen }}%</span>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar" style="width: {{ $persen }}%"></div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-[var(--color-bg-muted)] p-3 text-center">
                        <p class="text-[22px] font-extrabold text-[var(--color-ink-strong)]">{{ $book->stok }}</p>
                        <p class="label-caps">Total Koleksi</p>
                    </div>
                    <div class="rounded-xl bg-[var(--color-success-bg)] p-3 text-center">
                        <p class="text-[22px] font-extrabold text-[var(--color-success)]">{{ $book->tersedia() }}</p>
                        <p class="label-caps text-[var(--color-success)]">Tersedia</p>
                    </div>
                    <div class="rounded-xl bg-[var(--color-info-bg)] p-3 text-center">
                        <p class="text-[22px] font-extrabold text-[var(--color-info)]">{{ $dipinjam }}</p>
                        <p class="label-caps text-[var(--color-info)]">Dipinjam</p>
                    </div>
                </div>

                <div class="mt-5">
                    @auth
                        @if (auth()->user()->isSiswa())
                            @if ($pinjamanSaya)
                                <div class="flex items-center gap-2 rounded-xl bg-[var(--color-warning-bg)] px-4 py-3 text-[13px] font-semibold text-[var(--color-warning)]">
                                    <x-icon name="info" class="h-4 w-4" /> Anda sudah memiliki transaksi aktif untuk buku ini ({{ $pinjamanSaya->kode_transaksi }}).
                                </div>
                            @elseif (! $tersedia)
                                <button type="button" class="btn btn-primary btn-lg btn-full" disabled>
                                    <x-icon name="x" class="h-4 w-4" /> Stok Sedang Tidak Tersedia
                                </button>
                            @elseif ($book->isNonaktif())
                                <button type="button" class="btn btn-primary btn-lg btn-full" disabled>Buku Dinonaktifkan</button>
                            @else
                                <button type="button" data-modal-toggle="pinjam" class="btn btn-primary btn-lg btn-full">
                                    <x-icon name="book-open" class="h-4 w-4" /> Pinjam Buku Ini Sekarang
                                </button>
                            @endif
                        @else
                            <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="btn btn-secondary btn-lg btn-full">
                                <x-icon name="clipboard" class="h-4 w-4" /> Masuk ke Panel Petugas untuk Melayani
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg btn-full">
                            <x-icon name="key" class="h-4 w-4" /> Masuk untuk Meminjam Buku
                        </a>
                    @endauth
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="section-title">Sinopsis &amp; Kompetensi Kejuruan</h2>
                <p class="mt-3 text-[14px] leading-6 text-[var(--color-ink-body)]">{{ $book->deskripsi ?: 'Deskripsi belum tersedia untuk koleksi ini.' }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="badge badge-info">Program {{ $book->jurusan }}</span>
                    <span class="badge badge-neutral">{{ $book->kategori->nama }}</span>
                    <span class="badge badge-neutral">Bahasa {{ $book->bahasa }}</span>
                </div>
            </div>

            @if ($terkait->isNotEmpty())
                <div>
                    <h2 class="section-title mb-3">Koleksi Terkait</h2>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach ($terkait as $item)
                            <x-book-card :book="$item" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal konfirmasi peminjaman --}}
    @auth
        @if (auth()->user()->isSiswa() && $tersedia && ! $book->isNonaktif() && ! $pinjamanSaya)
            <div data-modal="pinjam" class="modal-backdrop hidden">
                <div class="modal-panel">
                    <div class="flex items-start justify-between gap-3 border-b border-[var(--color-line)] p-5">
                        <div>
                            <h3 class="section-title">Konfirmasi Pengajuan Peminjaman</h3>
                            <p class="mt-1 text-[12px] text-[var(--color-ink-muted)]">Periksa kembali detail sebelum mengajukan.</p>
                        </div>
                        <button type="button" data-modal-close="pinjam" class="rounded-lg p-1.5 text-[var(--color-ink-muted)] hover:bg-[var(--color-primary-50)]">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <form method="POST" action="{{ route('pinjaman.store') }}" class="space-y-4 p-5">
                        @csrf
                        <input type="hidden" name="book_id" value="{{ $book->id }}">

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                                <p class="label-caps">Peminjam</p>
                                <p class="mt-1 text-[14px] font-bold text-[var(--color-ink-strong)]">{{ auth()->user()->name }}</p>
                                <p class="text-mono text-[12px] text-[var(--color-ink-muted)]">NISN {{ auth()->user()->member?->nisn }}</p>
                            </div>
                            <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                                <p class="label-caps">Buku</p>
                                <p class="mt-1 text-[14px] font-bold text-[var(--color-ink-strong)]">{{ $book->judul }}</p>
                                <p class="text-mono text-[12px] text-[var(--color-ink-muted)]">{{ $book->kode_buku }}</p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-[var(--color-line)] p-4">
                            <p class="label-caps">Jadwal Sirkulasi &amp; Ketentuan Denda</p>
                            <div class="mt-3 grid grid-cols-3 gap-3 text-center">
                                <div>
                                    <p class="text-[11px] font-semibold text-[var(--color-ink-muted)]">Tanggal Pinjam</p>
                                    <p class="mt-1 text-[13px] font-bold text-[var(--color-ink-strong)]">{{ now()->translatedFormat('d M Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-[var(--color-ink-muted)]">Batas Kembali</p>
                                    <p class="mt-1 text-[13px] font-bold text-[var(--color-ink-strong)]">{{ now()->addDays($durasi)->translatedFormat('d M Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold text-[var(--color-ink-muted)]">Denda / Hari</p>
                                    <p class="mt-1 text-[13px] font-bold text-[var(--color-danger)]">Rp {{ number_format($tarifDenda, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start gap-2 rounded-xl bg-[var(--color-info-bg)] px-4 py-3 text-[12px] font-semibold text-[var(--color-info)]">
                            <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>Proses verifikasi dan serah-terima buku dilakukan di meja sirkulasi perpustakaan.</span>
                        </div>

                        <label class="flex items-start gap-2 text-[13px] font-medium text-[var(--color-ink-body)]">
                            <input type="checkbox" data-setuju class="checkbox mt-0.5">
                            <span>Saya menyetujui tata tertib perpustakaan dan akan mengembalikan buku sebelum tanggal jatuh tempo.</span>
                        </label>

                        <div class="flex justify-end gap-2 border-t border-[var(--color-line)] pt-4">
                            <button type="button" data-modal-close="pinjam" class="btn btn-ghost">Batal</button>
                            <button type="submit" data-konfirmasi-pinjam disabled class="btn btn-primary">
                                <x-icon name="check" class="h-4 w-4" /> Konfirmasi Ajukan Pinjam
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endauth
@endsection
