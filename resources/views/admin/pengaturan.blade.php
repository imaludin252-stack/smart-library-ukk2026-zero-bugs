@extends('layouts.app')

@section('title', 'Konfigurasi Sistem')

@section('content')
    <div class="mb-5">
        <h1 class="page-title">Konfigurasi Sistem</h1>
        <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Atur identitas sekolah dan aturan sirkulasi perpustakaan.</p>
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

    <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="grid gap-5 lg:grid-cols-3">
        @csrf @method('PUT')

        <div class="space-y-5 lg:col-span-2">
            <div class="card card-pad">
                <h2 class="section-title flex items-center gap-2"><x-icon name="building" class="h-5 w-5 text-[var(--color-primary-800)]" /> Identitas Sekolah</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="field-label" for="nama_sekolah">Nama Sekolah</label>
                        <input id="nama_sekolah" name="nama_sekolah" type="text" value="{{ old('nama_sekolah', $settings['nama_sekolah'] ?? '') }}" class="input" required>
                    </div>
                    <div>
                        <label class="field-label" for="tahun_ajaran">Tahun Ajaran</label>
                        <input id="tahun_ajaran" name="tahun_ajaran" type="text" value="{{ old('tahun_ajaran', $settings['tahun_ajaran'] ?? '') }}" class="input" placeholder="2026/2027" required>
                    </div>
                    <div>
                        <label class="field-label" for="jam_buka">Jam Buka Layanan</label>
                        <input id="jam_buka" name="jam_buka" type="text" value="{{ old('jam_buka', $settings['jam_buka'] ?? '') }}" class="input" placeholder="Senin–Jumat, 07.30–15.30 WIB" required>
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="section-title flex items-center gap-2"><x-icon name="sliders" class="h-5 w-5 text-[var(--color-primary-800)]" /> Aturan Sirkulasi</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="field-label" for="durasi_pinjam">Durasi Pinjam (hari)</label>
                        <input id="durasi_pinjam" name="durasi_pinjam" type="number" min="1" max="90" value="{{ old('durasi_pinjam', $settings['durasi_pinjam'] ?? 7) }}" class="input text-mono" required>
                    </div>
                    <div>
                        <label class="field-label" for="batas_pinjam_aktif">Batas Pinjam Aktif</label>
                        <input id="batas_pinjam_aktif" name="batas_pinjam_aktif" type="number" min="1" max="20" value="{{ old('batas_pinjam_aktif', $settings['batas_pinjam_aktif'] ?? 3) }}" class="input text-mono" required>
                    </div>
                    <div>
                        <label class="field-label" for="tarif_denda">Tarif Denda (Rp/hari)</label>
                        <input id="tarif_denda" name="tarif_denda" type="number" min="0" max="1000000" value="{{ old('tarif_denda', $settings['tarif_denda'] ?? 1000) }}" class="input text-mono" required>
                    </div>
                </div>
                <p class="mt-3 rounded-lg bg-[var(--color-info-bg)] px-3 py-2 text-[12px] font-medium text-[var(--color-info)]">
                    Perubahan aturan berlaku untuk transaksi baru. Transaksi yang sedang berjalan tetap mengikuti tenggat yang sudah ditetapkan.
                </p>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card card-pad">
                <h2 class="section-title">Pratinjau Aturan</h2>
                <div class="mt-4 space-y-3">
                    <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                        <p class="label-caps">Durasi Peminjaman</p>
                        <p class="text-[22px] font-extrabold text-[var(--color-ink-strong)]">{{ old('durasi_pinjam', $settings['durasi_pinjam'] ?? 7) }} hari</p>
                    </div>
                    <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                        <p class="label-caps">Maksimal Buku Aktif</p>
                        <p class="text-[22px] font-extrabold text-[var(--color-ink-strong)]">{{ old('batas_pinjam_aktif', $settings['batas_pinjam_aktif'] ?? 3) }} buku</p>
                    </div>
                    <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                        <p class="label-caps">Denda Keterlambatan</p>
                        <p class="text-[22px] font-extrabold text-[var(--color-ink-strong)]">Rp {{ number_format((int) old('tarif_denda', $settings['tarif_denda'] ?? 1000), 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="card card-pad">
                <button type="submit" class="btn btn-primary btn-full">
                    <x-icon name="check" class="h-4 w-4" /> Simpan Konfigurasi
                </button>
            </div>
        </div>
    </form>
@endsection
