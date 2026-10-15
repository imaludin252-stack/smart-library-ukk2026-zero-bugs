@extends('layouts.public')

@section('title', 'Registrasi Anggota')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-5">
            <span class="badge badge-info">PENDAFTARAN ANGGOTA PERPUSTAKAAN</span>
            <h1 class="page-title mt-3">Buat Akun Siswa</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Lengkapi data berikut untuk mendaftar sebagai anggota Smart Library. Gunakan NISN yang terdaftar di sekolah.</p>
        </div>

        <div class="card card-pad">
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

            <form method="POST" action="{{ route('register.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label for="name" class="field-label">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="input" placeholder="Contoh: Rizky Pratama">
                </div>
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="input" placeholder="nama@siswa.smkbb.sch.id">
                </div>
                <div>
                    <label for="nisn" class="field-label">NISN</label>
                    <input id="nisn" name="nisn" inputmode="numeric" value="{{ old('nisn') }}" required class="input text-mono" placeholder="0062819230">
                </div>
                <div>
                    <label for="kelas" class="field-label">Kelas</label>
                    <input id="kelas" name="kelas" type="text" value="{{ old('kelas') }}" required class="input" placeholder="XII RPL 1">
                </div>
                <div>
                    <label for="jurusan" class="field-label">Program Keahlian</label>
                    <select id="jurusan" name="jurusan" required class="select">
                        <option value="">Pilih jurusan</option>
                        @foreach (['RPL', 'TKJ', 'AKL', 'OTKP'] as $jurusan)
                            <option value="{{ $jurusan }}" @selected(old('jurusan') === $jurusan)>{{ $jurusan }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="password" class="field-label">Kata Sandi</label>
                    <input id="password" name="password" type="password" required class="input" placeholder="Minimal 8 karakter">
                </div>
                <div>
                    <label for="password_confirmation" class="field-label">Konfirmasi Kata Sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="input" placeholder="Ulangi kata sandi">
                </div>

                <div class="sm:col-span-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <x-icon name="check-circle" class="h-4 w-4" /> Daftar Sebagai Anggota
                    </button>
                    <p class="text-[13px] text-[var(--color-ink-muted)]">
                        Sudah punya akun? <a href="{{ route('login') }}" class="link">Masuk di sini</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
@endsection
