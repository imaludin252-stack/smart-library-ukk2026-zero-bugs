@extends('layouts.app')

@section('title', 'Data Anggota Siswa')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Data Anggota Siswa</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Kelola akun dan keanggotaan siswa perpustakaan.</p>
        </div>
        <button type="button" data-modal-toggle="tambah-anggota" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Tambah Anggota
        </button>
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

    <div class="mb-5 grid grid-cols-3 gap-4">
        <x-metric-card label="Total Anggota" icon="users" :value="number_format($totalAnggota, 0, ',', '.')" sub="Terdaftar" />
        <x-metric-card label="Anggota Aktif" tone="success" icon="check-circle" :value="number_format($anggotaAktif, 0, ',', '.')" sub="Dapat meminjam" />
        <x-metric-card label="Sedang Meminjam" tone="info" icon="book-open" :value="number_format($sedangMeminjam, 0, ',', '.')" sub="Memiliki pinjaman aktif" />
    </div>

    <div class="card card-pad mb-5">
        <form method="GET" action="{{ route('petugas.anggota.index') }}" class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="field-label" for="q">Pencarian</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="Nama, NISN, kelas, atau email…">
            </div>
            <div>
                <label class="field-label" for="jurusan">Jurusan</label>
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
                    <option value="">Semua</option>
                    <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Nonaktif</option>
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-4">
                <button class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> Terapkan</button>
                <a href="{{ route('petugas.anggota.index') }}" class="btn btn-ghost"><x-icon name="refresh" class="h-4 w-4" /> Reset</a>
            </div>
        </form>
    </div>

    @if ($members->isEmpty())
        <x-empty-state icon="users" title="Belum ada anggota" description="Tambahkan data anggota siswa untuk mulai melayani peminjaman.">
            <button type="button" data-modal-toggle="tambah-anggota" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Anggota</button>
        </x-empty-state>
    @else
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Anggota</th>
                            <th>NISN</th>
                            <th>Kelas / Jurusan</th>
                            <th>Pinjaman Aktif</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-[12px] font-bold text-[var(--color-primary-800)]">
                                            {{ $member->user->initials() }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-[var(--color-ink-strong)]">{{ $member->nama }}</p>
                                            <p class="text-[11px] text-[var(--color-ink-muted)]">{{ $member->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-mono text-[12px]">{{ $member->nisn }}</td>
                                <td class="text-[12px]">{{ $member->kelas }} · {{ $member->jurusan }}</td>
                                <td>
                                    <span class="badge {{ $member->aktif_count > 0 ? 'badge-info' : 'badge-neutral' }}">{{ $member->aktif_count }} buku</span>
                                </td>
                                <td><x-status-badge :status="$member->status" /></td>
                                <td>
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" data-modal-toggle="edit-anggota-{{ $member->id }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                                        <form method="POST" action="{{ route('petugas.anggota.destroy', $member) }}" data-confirm="Hapus data anggota '{{ $member->nama }}'?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-danger-ghost btn-sm"><x-icon name="trash" class="h-3.5 w-3.5" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <x-pagination :paginator="$members" />
    @endif

    {{-- Modal tambah --}}
    <div data-modal="tambah-anggota" class="modal-backdrop hidden">
        <div class="modal-panel">
            <form method="POST" action="{{ route('petugas.anggota.store') }}">
                @csrf
                <div class="flex items-center justify-between border-b border-[var(--color-line)] p-5">
                    <h3 class="section-title">Tambah Anggota Baru</h3>
                    <button type="button" data-modal-close="tambah-anggota" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="field-label" for="a-name">Nama Lengkap</label>
                        <input id="a-name" name="name" type="text" value="{{ old('name') }}" class="input" required>
                    </div>
                    <div>
                        <label class="field-label" for="a-email">Email</label>
                        <input id="a-email" name="email" type="email" value="{{ old('email') }}" class="input" required>
                    </div>
                    <div>
                        <label class="field-label" for="a-nisn">NISN</label>
                        <input id="a-nisn" name="nisn" type="text" inputmode="numeric" value="{{ old('nisn') }}" class="input text-mono" required>
                    </div>
                    <div>
                        <label class="field-label" for="a-kelas">Kelas</label>
                        <input id="a-kelas" name="kelas" type="text" value="{{ old('kelas') }}" class="input" placeholder="XII RPL 1" required>
                    </div>
                    <div>
                        <label class="field-label" for="a-jurusan">Jurusan</label>
                        <select id="a-jurusan" name="jurusan" class="select" required>
                            <option value="">Pilih jurusan…</option>
                            @foreach ($jurusanList as $jurusan)
                                <option value="{{ $jurusan }}" @selected(old('jurusan') === $jurusan)>{{ $jurusan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label" for="a-password">Kata Sandi Awal</label>
                        <input id="a-password" name="password" type="text" class="input" placeholder="Minimal 8 karakter" required>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                    <button type="button" data-modal-close="tambah-anggota" class="btn btn-ghost">Batal</button>
                    <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Simpan Anggota</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal edit per anggota --}}
    @foreach ($members as $member)
        <div data-modal="edit-anggota-{{ $member->id }}" class="modal-backdrop hidden">
            <div class="modal-panel">
                <form method="POST" action="{{ route('petugas.anggota.update', $member) }}">
                    @csrf @method('PUT')
                    <div class="flex items-center justify-between border-b border-[var(--color-line)] p-5">
                        <h3 class="section-title">Ubah Data Anggota</h3>
                        <button type="button" data-modal-close="edit-anggota-{{ $member->id }}" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="field-label" for="e-name-{{ $member->id }}">Nama Lengkap</label>
                            <input id="e-name-{{ $member->id }}" name="name" type="text" value="{{ $member->nama }}" class="input" required>
                        </div>
                        <div>
                            <label class="field-label" for="e-email-{{ $member->id }}">Email</label>
                            <input id="e-email-{{ $member->id }}" name="email" type="email" value="{{ $member->user->email }}" class="input" required>
                        </div>
                        <div>
                            <label class="field-label" for="e-nisn-{{ $member->id }}">NISN</label>
                            <input id="e-nisn-{{ $member->id }}" name="nisn" type="text" value="{{ $member->nisn }}" class="input text-mono" required>
                        </div>
                        <div>
                            <label class="field-label" for="e-kelas-{{ $member->id }}">Kelas</label>
                            <input id="e-kelas-{{ $member->id }}" name="kelas" type="text" value="{{ $member->kelas }}" class="input" required>
                        </div>
                        <div>
                            <label class="field-label" for="e-jurusan-{{ $member->id }}">Jurusan</label>
                            <select id="e-jurusan-{{ $member->id }}" name="jurusan" class="select" required>
                                @foreach ($jurusanList as $jurusan)
                                    <option value="{{ $jurusan }}" @selected($member->jurusan === $jurusan)>{{ $jurusan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label" for="e-status-{{ $member->id }}">Status Keanggotaan</label>
                            <select id="e-status-{{ $member->id }}" name="status" class="select" required>
                                <option value="aktif" @selected($member->status === 'aktif')>Aktif</option>
                                <option value="nonaktif" @selected($member->status === 'nonaktif')>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                        <button type="button" data-modal-close="edit-anggota-{{ $member->id }}" class="btn btn-ghost">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
