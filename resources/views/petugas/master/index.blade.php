@extends('layouts.app')

@section('title', 'Kategori & Penulis')

@section('content')
    <div class="mb-5">
        <h1 class="page-title">Kategori &amp; Penulis</h1>
        <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Kelola data master kategori koleksi dan daftar penulis buku.</p>
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

    <div class="grid gap-5 lg:grid-cols-2">
        {{-- Kategori --}}
        <div class="card card-pad">
            <div class="flex items-center justify-between">
                <h2 class="section-title flex items-center gap-2"><x-icon name="tag" class="h-5 w-5 text-[var(--color-primary-800)]" /> Kategori</h2>
                <span class="badge badge-neutral">{{ $kategori->count() }} kategori</span>
            </div>

            <form method="POST" action="{{ route('petugas.kategori.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="field-label" for="kat-nama">Nama Kategori</label>
                    <input id="kat-nama" name="nama" type="text" class="input" placeholder="Mis. Pemrograman Web" required>
                </div>
                <div>
                    <label class="field-label" for="kat-deskripsi">Deskripsi</label>
                    <input id="kat-deskripsi" name="deskripsi" type="text" class="input" placeholder="Opsional">
                </div>
                <div class="sm:col-span-2">
                    <button class="btn btn-primary btn-full"><x-icon name="plus" class="h-4 w-4" /> Tambah Kategori</button>
                </div>
            </form>

            <form method="GET" action="{{ route('petugas.kategori.index') }}" class="mt-4">
                <input name="q" type="search" value="{{ $q }}" class="input" placeholder="Cari kategori…">
            </form>

            <div class="mt-4 divide-y divide-[var(--color-line)]">
                @forelse ($kategori as $item)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-[14px] font-bold text-[var(--color-ink-strong)]">{{ $item->nama }}</p>
                            <p class="text-[12px] text-[var(--color-ink-muted)]">{{ $item->deskripsi ?: 'Tanpa deskripsi' }} · {{ $item->books_count }} buku</p>
                        </div>
                        <div class="flex shrink-0 gap-1.5">
                            <button type="button" data-modal-toggle="edit-kat-{{ $item->id }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                            <form method="POST" action="{{ route('petugas.kategori.destroy', $item) }}" data-confirm="Hapus kategori '{{ $item->nama }}'?">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger-ghost btn-sm"><x-icon name="trash" class="h-3.5 w-3.5" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-[13px] text-[var(--color-ink-muted)]">Belum ada kategori.</p>
                @endforelse
            </div>
        </div>

        {{-- Penulis --}}
        <div class="card card-pad">
            <div class="flex items-center justify-between">
                <h2 class="section-title flex items-center gap-2"><x-icon name="pen-tool" class="h-5 w-5 text-[var(--color-primary-800)]" /> Penulis</h2>
                <span class="badge badge-neutral">{{ $penulis->count() }} penulis</span>
            </div>

            <form method="POST" action="{{ route('petugas.penulis.store') }}" class="mt-4 flex gap-3">
                @csrf
                <input name="nama" type="text" class="input" placeholder="Nama penulis baru…" required>
                <button class="btn btn-primary shrink-0"><x-icon name="plus" class="h-4 w-4" /> Tambah</button>
            </form>

            <div class="mt-4 max-h-[480px] divide-y divide-[var(--color-line)] overflow-y-auto">
                @forelse ($penulis as $item)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-[14px] font-bold text-[var(--color-ink-strong)]">{{ $item->nama }}</p>
                            <p class="text-[12px] text-[var(--color-ink-muted)]">{{ $item->books_count }} buku</p>
                        </div>
                        <div class="flex shrink-0 gap-1.5">
                            <button type="button" data-modal-toggle="edit-pen-{{ $item->id }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                            <form method="POST" action="{{ route('petugas.penulis.destroy', $item) }}" data-confirm="Hapus penulis '{{ $item->nama }}'?">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger-ghost btn-sm"><x-icon name="trash" class="h-3.5 w-3.5" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-[13px] text-[var(--color-ink-muted)]">Belum ada penulis.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Modal edit kategori --}}
    @foreach ($kategori as $item)
        <div data-modal="edit-kat-{{ $item->id }}" class="modal-backdrop hidden">
            <div class="modal-panel">
                <form method="POST" action="{{ route('petugas.kategori.update', $item) }}">
                    @csrf @method('PUT')
                    <div class="flex items-center justify-between border-b border-[var(--color-line)] p-5">
                        <h3 class="section-title">Ubah Kategori</h3>
                        <button type="button" data-modal-close="edit-kat-{{ $item->id }}" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <label class="field-label" for="kat-nama-{{ $item->id }}">Nama Kategori</label>
                            <input id="kat-nama-{{ $item->id }}" name="nama" type="text" value="{{ $item->nama }}" class="input" required>
                        </div>
                        <div>
                            <label class="field-label" for="kat-desk-{{ $item->id }}">Deskripsi</label>
                            <input id="kat-desk-{{ $item->id }}" name="deskripsi" type="text" value="{{ $item->deskripsi }}" class="input">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                        <button type="button" data-modal-close="edit-kat-{{ $item->id }}" class="btn btn-ghost">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    {{-- Modal edit penulis --}}
    @foreach ($penulis as $item)
        <div data-modal="edit-pen-{{ $item->id }}" class="modal-backdrop hidden">
            <div class="modal-panel">
                <form method="POST" action="{{ route('petugas.penulis.update', $item) }}">
                    @csrf @method('PUT')
                    <div class="flex items-center justify-between border-b border-[var(--color-line)] p-5">
                        <h3 class="section-title">Ubah Penulis</h3>
                        <button type="button" data-modal-close="edit-pen-{{ $item->id }}" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                    </div>
                    <div class="p-5">
                        <label class="field-label" for="pen-nama-{{ $item->id }}">Nama Penulis</label>
                        <input id="pen-nama-{{ $item->id }}" name="nama" type="text" value="{{ $item->nama }}" class="input" required>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                        <button type="button" data-modal-close="edit-pen-{{ $item->id }}" class="btn btn-ghost">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
