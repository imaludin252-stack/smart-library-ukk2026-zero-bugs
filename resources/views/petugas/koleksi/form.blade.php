@extends('layouts.app')

@section('title', $book ? 'Ubah Buku' : 'Tambah Buku')

@section('content')
    @php $edit = (bool) $book; @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">{{ $edit ? 'Ubah Data Buku' : 'Tambah Buku Baru' }}</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">{{ $edit ? 'Perbarui informasi koleksi "'.$book->judul.'".' : 'Lengkapi data bibliografi koleksi perpustakaan.' }}</p>
        </div>
        <a href="{{ route('petugas.koleksi.index') }}" class="btn btn-ghost">
            <x-icon name="arrow-left" class="h-4 w-4" /> Kembali
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

    <form method="POST" action="{{ $edit ? route('petugas.koleksi.update', $book) : route('petugas.koleksi.store') }}" class="grid gap-5 lg:grid-cols-3">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="space-y-5 lg:col-span-2">
            <div class="card card-pad">
                <h2 class="section-title">Informasi Utama</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="field-label" for="judul">Judul Buku <span class="text-[var(--color-danger)]">*</span></label>
                        <input id="judul" name="judul" type="text" value="{{ old('judul', $book?->judul) }}" class="input" required>
                    </div>
                    <div>
                        <label class="field-label" for="kode_buku">Kode Buku <span class="text-[var(--color-danger)]">*</span></label>
                        <input id="kode_buku" name="kode_buku" type="text" value="{{ old('kode_buku', $book?->kode_buku) }}" class="input text-mono" placeholder="RPL-PWD-001" required>
                    </div>
                    <div>
                        <label class="field-label" for="kategori_id">Kategori <span class="text-[var(--color-danger)]">*</span></label>
                        <select id="kategori_id" name="kategori_id" class="select" required>
                            <option value="">Pilih kategori…</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('kategori_id', $book?->kategori_id) === (string) $category->id)>{{ $category->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label" for="penulis">Penulis</label>
                        <input id="penulis" name="penulis" type="text" value="{{ old('penulis', $book?->penulisLabel() === '-' ? '' : $book?->penulisLabel()) }}" class="input" placeholder="Pisahkan dengan koma untuk beberapa penulis">
                    </div>
                    <div>
                        <label class="field-label" for="penerbit">Penerbit</label>
                        <input id="penerbit" name="penerbit" type="text" value="{{ old('penerbit', $book?->penerbit) }}" class="input">
                    </div>
                    <div>
                        <label class="field-label" for="tahun">Tahun Terbit</label>
                        <input id="tahun" name="tahun" type="number" min="1900" max="{{ date('Y') + 1 }}" value="{{ old('tahun', $book?->tahun) }}" class="input text-mono">
                    </div>
                    <div>
                        <label class="field-label" for="isbn">ISBN</label>
                        <input id="isbn" name="isbn" type="text" value="{{ old('isbn', $book?->isbn) }}" class="input text-mono">
                    </div>
                    <div>
                        <label class="field-label" for="tebal_halaman">Tebal Halaman</label>
                        <input id="tebal_halaman" name="tebal_halaman" type="number" min="1" value="{{ old('tebal_halaman', $book?->tebal_halaman) }}" class="input text-mono">
                    </div>
                    <div>
                        <label class="field-label" for="bahasa">Bahasa</label>
                        <input id="bahasa" name="bahasa" type="text" value="{{ old('bahasa', $book?->bahasa ?? 'Indonesia') }}" class="input">
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="section-title">Deskripsi &amp; Sinopsis</h2>
                <div class="mt-4">
                    <textarea id="deskripsi" name="deskripsi" class="textarea" rows="5" placeholder="Ringkasan isi buku, kompetensi yang dicakup, dsb.">{{ old('deskripsi', $book?->deskripsi) }}</textarea>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card card-pad">
                <h2 class="section-title">Penempatan &amp; Stok</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="jurusan">Program Keahlian <span class="text-[var(--color-danger)]">*</span></label>
                        <select id="jurusan" name="jurusan" class="select" required>
                            @foreach ($jurusanList as $jurusan)
                                <option value="{{ $jurusan }}" @selected(old('jurusan', $book?->jurusan) === $jurusan)>{{ $jurusan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="nomor_panggil">Nomor Panggil</label>
                        <input id="nomor_panggil" name="nomor_panggil" type="text" value="{{ old('nomor_panggil', $book?->nomor_panggil) }}" class="input text-mono">
                    </div>
                    <div>
                        <label class="field-label" for="lokasi_rak">Lokasi Rak</label>
                        <input id="lokasi_rak" name="lokasi_rak" type="text" value="{{ old('lokasi_rak', $book?->lokasi_rak) }}" class="input">
                    </div>
                    <div>
                        <label class="field-label" for="stok">Jumlah Stok (Eksemplar) <span class="text-[var(--color-danger)]">*</span></label>
                        <input id="stok" name="stok" type="number" min="0" max="9999" value="{{ old('stok', $book?->stok ?? 1) }}" class="input text-mono" required>
                    </div>
                    <div>
                        <label class="field-label" for="status">Status Koleksi <span class="text-[var(--color-danger)]">*</span></label>
                        <select id="status" name="status" class="select" required>
                            <option value="aktif" @selected(old('status', $book?->status ?? 'aktif') === 'aktif')>Aktif — dapat dipinjam</option>
                            <option value="nonaktif" @selected(old('status', $book?->status) === 'nonaktif')>Nonaktif — disembunyikan</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <button type="submit" class="btn btn-primary btn-full">
                    <x-icon name="check" class="h-4 w-4" /> {{ $edit ? 'Simpan Perubahan' : 'Simpan Buku' }}
                </button>
                <a href="{{ route('petugas.koleksi.index') }}" class="btn btn-ghost btn-full mt-2">Batal</a>
            </div>
        </div>
    </form>
@endsection
