@extends('layouts.public')

@section('title', 'Panduan Penggunaan')

@section('content')
    <div class="mb-6">
        <span class="badge badge-info">PANDUAN PENGGUNAAN</span>
        <h1 class="page-title mt-3">Cara Menggunakan Smart Library</h1>
        <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Panduan singkat untuk siswa dan petugas perpustakaan.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <section class="card card-pad">
            <h2 class="section-title flex items-center gap-2"><x-icon name="graduation-cap" class="h-5 w-5 text-[var(--color-primary-800)]" /> Untuk Siswa</h2>
            <ol class="mt-4 space-y-3 text-sm text-[var(--color-ink-body)]">
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">01</span><span><b>Registrasi/Login.</b> Buat akun dengan NISN, atau masuk dengan akun yang telah dibuat petugas.</span></li>
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">02</span><span><b>Cari buku.</b> Gunakan kolom pencarian pada halaman Katalog berdasarkan judul, penulis, kategori, atau kode buku.</span></li>
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">03</span><span><b>Ajukan peminjaman.</b> Buka detail buku berstatus Tersedia lalu tekan <i>Pinjam Buku Ini Sekarang</i> dan setujui tata tertib.</span></li>
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">04</span><span><b>Verifikasi di meja sirkulasi.</b> Petugas memverifikasi pengajuan dan menyerahkan buku fisik.</span></li>
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">05</span><span><b>Pantau jatuh tempo.</b> Lihat sisa hari pada Beranda &amp; Peminjaman Saya. Kembalikan sebelum tenggat untuk menghindari denda.</span></li>
            </ol>
        </section>

        <section class="card card-pad">
            <h2 class="section-title flex items-center gap-2"><x-icon name="clipboard" class="h-5 w-5 text-[var(--color-primary-800)]" /> Untuk Petugas</h2>
            <ol class="mt-4 space-y-3 text-sm text-[var(--color-ink-body)]">
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">01</span><span><b>Kelola koleksi.</b> Tambah, ubah, atau nonaktifkan buku pada menu Data Koleksi Buku.</span></li>
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">02</span><span><b>Verifikasi permohonan.</b> Di Meja Sirkulasi, proses pengajuan siswa: serahkan atau tolak.</span></li>
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">03</span><span><b>Pengembalian.</b> Pilih transaksi aktif, catat kondisi buku, sistem menghitung denda otomatis bila terlambat.</span></li>
                <li class="flex gap-3"><span class="text-mono font-bold text-[var(--color-primary-800)]">04</span><span><b>Laporan.</b> Unduh laporan transaksi, buku terlambat, atau buku terpopuler dalam format PDF per periode.</span></li>
            </ol>
        </section>

        <section class="card card-pad md:col-span-2">
            <h2 class="section-title">Aturan Peminjaman</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                    <p class="label-caps">Durasi Pinjam</p>
                    <p class="mt-1 text-2xl font-extrabold text-[var(--color-ink-strong)]">{{ \App\Models\Setting::get('durasi_pinjam', 7) }} hari</p>
                </div>
                <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                    <p class="label-caps">Maksimal Buku</p>
                    <p class="mt-1 text-2xl font-extrabold text-[var(--color-ink-strong)]">{{ \App\Models\Setting::get('batas_pinjam_aktif', 3) }} buku</p>
                </div>
                <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                    <p class="label-caps">Denda Keterlambatan</p>
                    <p class="mt-1 text-2xl font-extrabold text-[var(--color-ink-strong)]">Rp {{ number_format((int) \App\Models\Setting::get('tarif_denda', 1000), 0, ',', '.') }}/hari</p>
                </div>
            </div>
        </section>
    </div>
@endsection
