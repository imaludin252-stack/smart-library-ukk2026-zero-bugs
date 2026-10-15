@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Manajemen Pengguna</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Kelola akun petugas perpustakaan dan administrator sistem.</p>
        </div>
        <button type="button" data-modal-toggle="tambah-pengguna" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Tambah Pengguna
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

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-metric-card label="Total Pengguna" icon="users" :value="number_format($totalPengguna, 0, ',', '.')" />
        <x-metric-card label="Petugas & Admin" tone="info" icon="shield" :value="number_format($totalPetugas, 0, ',', '.')" />
        <x-metric-card label="Siswa" icon="graduation-cap" :value="number_format($totalSiswa, 0, ',', '.')" />
        <x-metric-card label="Akun Nonaktif" :tone="$totalNonaktif > 0 ? 'warning' : 'success'" icon="alert-triangle" :value="number_format($totalNonaktif, 0, ',', '.')" />
    </div>

    <div class="card card-pad mb-5">
        <form method="GET" action="{{ route('admin.pengguna') }}" class="grid gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="field-label" for="q">Pencarian</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="Nama atau email…">
            </div>
            <div>
                <label class="field-label" for="role">Peran</label>
                <select id="role" name="role" class="select">
                    <option value="">Semua Peran</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                    @endforeach
                    <option value="siswa" @selected($filters['role'] === 'siswa')>Siswa</option>
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
            <div class="flex items-end gap-2 md:col-span-4">
                <button class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> Terapkan</button>
                <a href="{{ route('admin.pengguna') }}" class="btn btn-ghost"><x-icon name="refresh" class="h-4 w-4" /> Reset</a>
            </div>
        </form>
    </div>

    @if ($users->isEmpty())
        <x-empty-state icon="users" title="Tidak ada pengguna" description="Sesuaikan filter pencarian atau tambahkan pengguna baru." />
    @else
        <div class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Peran</th>
                            <th>Transaksi Ditangani</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-[12px] font-bold text-[var(--color-primary-800)]">
                                            {{ $user->initials() }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-[var(--color-ink-strong)]">{{ $user->name }}</p>
                                            <p class="text-[11px] text-[var(--color-ink-muted)]">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $user->isSiswa() ? 'badge-neutral' : 'badge-info' }}">{{ $user->roleLabel() }}</span>
                                </td>
                                <td class="text-mono text-[12px]">{{ $user->diproses_count }}</td>
                                <td><x-status-badge :status="$user->status" /></td>
                                <td>
                                    <div class="flex justify-end gap-1.5">
                                        @if ($user->isSiswa())
                                            <span class="self-center text-[11px] font-semibold text-[var(--color-ink-muted)]">Kelola via Data Anggota</span>
                                        @else
                                            <button type="button" data-modal-toggle="edit-user-{{ $user->id }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                                            <form method="POST" action="{{ route('admin.pengguna.status', $user) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn {{ $user->isAktif() ? 'btn-ghost' : 'btn-success' }} btn-sm" title="{{ $user->isAktif() ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    <x-icon name="refresh" class="h-3.5 w-3.5" />
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.pengguna.destroy', $user) }}" data-confirm="Hapus akun '{{ $user->name }}'?">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-danger-ghost btn-sm"><x-icon name="trash" class="h-3.5 w-3.5" /></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <x-pagination :paginator="$users" />
    @endif

    {{-- Modal tambah --}}
    <div data-modal="tambah-pengguna" class="modal-backdrop hidden">
        <div class="modal-panel">
            <form method="POST" action="{{ route('admin.pengguna.store') }}">
                @csrf
                <div class="flex items-center justify-between border-b border-[var(--color-line)] p-5">
                    <h3 class="section-title">Tambah Pengguna Internal</h3>
                    <button type="button" data-modal-close="tambah-pengguna" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="field-label" for="p-name">Nama Lengkap</label>
                        <input id="p-name" name="name" type="text" value="{{ old('name') }}" class="input" required>
                    </div>
                    <div>
                        <label class="field-label" for="p-email">Email</label>
                        <input id="p-email" name="email" type="email" value="{{ old('email') }}" class="input" required>
                    </div>
                    <div>
                        <label class="field-label" for="p-role">Peran</label>
                        <select id="p-role" name="role" class="select" required>
                            <option value="">Pilih peran…</option>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label" for="p-password">Kata Sandi Awal</label>
                        <input id="p-password" name="password" type="text" class="input" placeholder="Minimal 8 karakter" required>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                    <button type="button" data-modal-close="tambah-pengguna" class="btn btn-ghost">Batal</button>
                    <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal edit --}}
    @foreach ($users as $user)
        @unless ($user->isSiswa())
            <div data-modal="edit-user-{{ $user->id }}" class="modal-backdrop hidden">
                <div class="modal-panel">
                    <form method="POST" action="{{ route('admin.pengguna.update', $user) }}">
                        @csrf @method('PUT')
                        <div class="flex items-center justify-between border-b border-[var(--color-line)] p-5">
                            <h3 class="section-title">Ubah Pengguna</h3>
                            <button type="button" data-modal-close="edit-user-{{ $user->id }}" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                        </div>
                        <div class="grid gap-4 p-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="field-label" for="u-name-{{ $user->id }}">Nama Lengkap</label>
                                <input id="u-name-{{ $user->id }}" name="name" type="text" value="{{ $user->name }}" class="input" required>
                            </div>
                            <div>
                                <label class="field-label" for="u-email-{{ $user->id }}">Email</label>
                                <input id="u-email-{{ $user->id }}" name="email" type="email" value="{{ $user->email }}" class="input" required>
                            </div>
                            <div>
                                <label class="field-label" for="u-role-{{ $user->id }}">Peran</label>
                                <select id="u-role-{{ $user->id }}" name="role" class="select" required>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="field-label" for="u-status-{{ $user->id }}">Status</label>
                                <select id="u-status-{{ $user->id }}" name="status" class="select" required>
                                    <option value="aktif" @selected($user->isAktif())>Aktif</option>
                                    <option value="nonaktif" @selected(! $user->isAktif())>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                            <button type="button" data-modal-close="edit-user-{{ $user->id }}" class="btn btn-ghost">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    @endforeach
@endsection
