@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    @php $isSiswaProfil = $user->isSiswa(); @endphp

    <div class="mb-5">
        <h1 class="page-title">Profil Saya</h1>
        <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Kelola informasi akun dan kata sandi portal Smart Library.</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Kolom identitas --}}
        <div class="space-y-5">
            <div class="card card-pad text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-[26px] font-extrabold text-[var(--color-primary-800)]">
                    {{ $user->initials() }}
                </div>
                <h2 class="mt-3 text-[18px] font-extrabold text-[var(--color-ink-strong)]">{{ $user->name }}</h2>
                <p class="text-[13px] text-[var(--color-ink-muted)]">{{ $user->email }}</p>
                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    <span class="badge badge-info">{{ $user->roleLabel() }}</span>
                    <x-status-badge :status="$user->isAktif() ? 'aktif' : 'nonaktif'" :label="$user->isAktif() ? 'Akun Aktif' : 'Akun Nonaktif'" />
                </div>
                @if ($statistik)
                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <div class="rounded-xl bg-[var(--color-bg-muted)] p-2.5">
                            <p class="text-[18px] font-extrabold text-[var(--color-ink-strong)]">{{ $statistik['aktif'] }}</p>
                            <p class="label-caps">Aktif</p>
                        </div>
                        <div class="rounded-xl bg-[var(--color-bg-muted)] p-2.5">
                            <p class="text-[18px] font-extrabold text-[var(--color-ink-strong)]">{{ $statistik['selesai'] }}</p>
                            <p class="label-caps">Selesai</p>
                        </div>
                        <div class="rounded-xl bg-[var(--color-bg-muted)] p-2.5">
                            <p class="text-[18px] font-extrabold {{ $statistik['terlambat'] > 0 ? 'text-[var(--color-danger)]' : 'text-[var(--color-ink-strong)]' }}">{{ $statistik['terlambat'] }}</p>
                            <p class="label-caps">Telat</p>
                        </div>
                    </div>
                @endif
            </div>

            @if ($member)
                <div class="card overflow-hidden">
                    <div class="bg-gradient-to-br from-[var(--color-primary-800)] to-[var(--color-primary-600)] p-5 text-white">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-white/70">Kartu Anggota Digital</span>
                            <x-icon name="qr-code" class="h-5 w-5 text-white/80" />
                        </div>
                        <p class="mt-4 text-[15px] font-extrabold">{{ $member->nama }}</p>
                        <p class="text-mono text-[12px] text-white/80">NISN {{ $member->nisn }}</p>
                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wide text-white/60">Kelas / Jurusan</p>
                                <p class="text-[13px] font-bold">{{ $member->kelas }} · {{ $member->jurusan }}</p>
                            </div>
                            <span class="badge badge-success">{{ $member->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3 text-[11px] font-semibold text-[var(--color-ink-muted)]">
                        <span>{{ \App\Models\Setting::get('nama_sekolah', 'SMK Budi Bakti Ciwidey') }}</span>
                        <span>TA {{ \App\Models\Setting::get('tahun_ajaran', '2026/2027') }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Kolom detail --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="card card-pad">
                <h2 class="section-title">Informasi Akun</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="label-caps">Nama Lengkap</p>
                        <p class="mt-1 text-[14px] font-semibold text-[var(--color-ink-strong)]">{{ $user->name }}</p>
                    </div>
                    <div>
                        <p class="label-caps">Email</p>
                        <p class="mt-1 text-[14px] font-semibold text-[var(--color-ink-strong)]">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="label-caps">Peran</p>
                        <p class="mt-1 text-[14px] font-semibold text-[var(--color-ink-strong)]">{{ $user->roleLabel() }}</p>
                    </div>
                    <div>
                        <p class="label-caps">Status Akun</p>
                        <p class="mt-1 text-[14px] font-semibold text-[var(--color-ink-strong)]">{{ $user->isAktif() ? 'Aktif' : 'Nonaktif' }}</p>
                    </div>
                    @if ($member)
                        <div>
                            <p class="label-caps">NISN</p>
                            <p class="text-mono mt-1 text-[13px] font-semibold text-[var(--color-ink-strong)]">{{ $member->nisn }}</p>
                        </div>
                        <div>
                            <p class="label-caps">Kelas</p>
                            <p class="mt-1 text-[14px] font-semibold text-[var(--color-ink-strong)]">{{ $member->kelas }}</p>
                        </div>
                        <div>
                            <p class="label-caps">Program Keahlian</p>
                            <p class="mt-1 text-[14px] font-semibold text-[var(--color-ink-strong)]">{{ $member->jurusan }}</p>
                        </div>
                    @endif
                </div>
                <p class="mt-4 rounded-lg bg-[var(--color-info-bg)] px-3 py-2 text-[12px] font-medium text-[var(--color-info)]">
                    Perubahan data identitas hanya dapat dilakukan oleh petugas perpustakaan.
                </p>
            </div>

            <div class="card card-pad">
                <h2 class="section-title">Ubah Kata Sandi</h2>
                <p class="mt-1 text-[13px] text-[var(--color-ink-muted)]">Gunakan kata sandi minimal 8 karakter.</p>
                <form method="POST" action="{{ route('profil.password') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <div class="sm:col-span-2">
                        <label class="field-label" for="password_lama">Kata Sandi Saat Ini</label>
                        <input id="password_lama" name="password_lama" type="password" class="input" required>
                        @error('password_lama') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label" for="password_baru">Kata Sandi Baru</label>
                        <input id="password_baru" name="password" type="password" class="input" required>
                        @error('password') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="field-label" for="password_konfirmasi">Konfirmasi Kata Sandi Baru</label>
                        <input id="password_konfirmasi" name="password_confirmation" type="password" class="input" required>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="btn btn-primary"><x-icon name="key" class="h-4 w-4" /> Perbarui Kata Sandi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
