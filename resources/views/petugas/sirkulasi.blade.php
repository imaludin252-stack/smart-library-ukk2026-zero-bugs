@extends('layouts.app')

@section('title', 'Meja Sirkulasi')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Meja Sirkulasi</h1>
            <p class="mt-1 text-sm text-[var(--color-ink-muted)]">Verifikasi permohonan, serah-terima buku, dan proses pengembalian.</p>
        </div>
        <button type="button" data-modal-toggle="pinjam-manual" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Catat Peminjaman Manual
        </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('petugas.sirkulasi.index', ['tab' => 'permohonan']) }}" class="filter-pill {{ $tab === 'permohonan' ? 'filter-pill-active' : '' }}">
            Permohonan <span class="rounded-full bg-black/5 px-1.5 text-[11px] font-bold">{{ $permohonan->count() }}</span>
        </a>
        <a href="{{ route('petugas.sirkulasi.index', ['tab' => 'aktif']) }}" class="filter-pill {{ $tab === 'aktif' ? 'filter-pill-active' : '' }}">
            Pinjaman Aktif <span class="rounded-full bg-black/5 px-1.5 text-[11px] font-bold">{{ $aktif->count() }}</span>
        </a>
        <a href="{{ route('petugas.sirkulasi.index', ['tab' => 'selesai']) }}" class="filter-pill {{ $tab === 'selesai' ? 'filter-pill-active' : '' }}">
            Selesai Hari Ini <span class="rounded-full bg-black/5 px-1.5 text-[11px] font-bold">{{ $selesaiHariIni->count() }}</span>
        </a>
    </div>

    @if ($tab === 'permohonan')
        @if ($permohonan->isEmpty())
            <x-empty-state icon="check-circle" title="Tidak ada permohonan baru" description="Semua permohonan peminjaman sudah diverifikasi." />
        @else
            <div class="space-y-4">
                @foreach ($permohonan as $loan)
                    @php $b = $loan->books->first(); @endphp
                    <div class="card card-pad">
                        <div class="flex flex-wrap items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-100)] text-[14px] font-bold text-[var(--color-primary-800)]">
                                {{ $loan->member->user->initials() }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-mono text-[11px] font-semibold text-[var(--color-ink-muted)]">{{ $loan->kode_transaksi }}</span>
                                    <x-status-badge status="diajukan" />
                                </div>
                                <p class="mt-1 text-[15px] font-bold text-[var(--color-ink-strong)]">{{ $loan->member->nama }}</p>
                                <p class="text-mono text-[12px] text-[var(--color-ink-muted)]">NISN {{ $loan->member->nisn }} · {{ $loan->member->kelas }} · {{ $loan->member->jurusan }}</p>
                                <p class="mt-2 text-[13px] font-semibold text-[var(--color-ink-body)]">Buku: {{ $b->judul }}</p>
                                <p class="text-mono text-[11px] text-[var(--color-ink-muted)]">{{ $b->kode_buku }} · Diajukan {{ $loan->created_at->translatedFormat('d M Y H:i') }}</p>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('petugas.sirkulasi.verifikasi', $loan) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-success"><x-icon name="check" class="h-4 w-4" /> Serahkan Buku</button>
                                </form>
                                <button type="button" data-modal-toggle="tolak-{{ $loan->id }}" class="btn btn-danger-ghost">
                                    <x-icon name="x" class="h-4 w-4" /> Tolak
                                </button>
                            </div>
                        </div>
                    </div>

                    <div data-modal="tolak-{{ $loan->id }}" class="modal-backdrop hidden">
                        <div class="modal-panel">
                            <form method="POST" action="{{ route('petugas.sirkulasi.tolak', $loan) }}">
                                @csrf @method('PATCH')
                                <div class="flex items-start justify-between border-b border-[var(--color-line)] p-5">
                                    <h3 class="section-title">Tolak Permohonan {{ $loan->kode_transaksi }}</h3>
                                    <button type="button" data-modal-close="tolak-{{ $loan->id }}" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                                </div>
                                <div class="p-5">
                                    <label class="field-label" for="alasan-{{ $loan->id }}">Alasan Penolakan</label>
                                    <textarea id="alasan-{{ $loan->id }}" name="alasan" class="textarea" placeholder="Contoh: stok fisik sedang diperbaiki.">{{ old('alasan') }}</textarea>
                                </div>
                                <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                                    <button type="button" data-modal-close="tolak-{{ $loan->id }}" class="btn btn-ghost">Batal</button>
                                    <button type="submit" class="btn btn-danger"><x-icon name="x" class="h-4 w-4" /> Tolak Permohonan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @elseif ($tab === 'aktif')
        @if ($aktif->isEmpty())
            <x-empty-state icon="book-open" title="Tidak ada pinjaman aktif" description="Belum ada buku yang sedang dipinjam siswa saat ini." />
        @else
            <div class="card">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($aktif as $loan)
                                @php $b = $loan->books->first(); @endphp
                                <tr>
                                    <td class="text-mono text-[12px] font-semibold">{{ $loan->kode_transaksi }}</td>
                                    <td>
                                        <p class="font-semibold text-[var(--color-ink-strong)]">{{ $loan->member->nama }}</p>
                                        <p class="text-mono text-[11px] text-[var(--color-ink-muted)]">{{ $loan->member->kelas }}</p>
                                    </td>
                                    <td class="max-w-[220px]">
                                        <p class="line-clamp-1 font-semibold text-[var(--color-ink-strong)]">{{ $b->judul }}</p>
                                        <p class="text-mono text-[11px] text-[var(--color-ink-muted)]">{{ $b->kode_buku }}</p>
                                    </td>
                                    <td class="text-[12px]">{{ $loan->tanggal_pinjam->translatedFormat('d M Y') }}</td>
                                    <td class="text-[12px] {{ $loan->isTerlambat() ? 'font-bold text-[var(--color-danger)]' : '' }}">{{ $loan->tanggal_jatuh_tempo->translatedFormat('d M Y') }}</td>
                                    <td><x-status-badge :status="$loan->status" /></td>
                                    <td class="text-right">
                                        <button type="button" data-modal-toggle="kembali-{{ $loan->id }}" class="btn btn-primary btn-sm">
                                            <x-icon name="refresh" class="h-3.5 w-3.5" /> Kembalikan
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @foreach ($aktif as $loan)
                @php $b = $loan->books->first(); @endphp
                <div data-modal="kembali-{{ $loan->id }}" class="modal-backdrop hidden">
                    <div class="modal-panel">
                        <form method="POST" action="{{ route('petugas.sirkulasi.kembalikan', $loan) }}">
                            @csrf @method('PATCH')
                            <div class="flex items-start justify-between border-b border-[var(--color-line)] p-5">
                                <div>
                                    <h3 class="section-title">Proses Pengembalian</h3>
                                    <p class="text-mono mt-1 text-[12px] text-[var(--color-ink-muted)]">{{ $loan->kode_transaksi }} · {{ $loan->member->nama }}</p>
                                </div>
                                <button type="button" data-modal-close="kembali-{{ $loan->id }}" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                            </div>
                            <div class="space-y-4 p-5">
                                <div class="rounded-xl bg-[var(--color-primary-50)] p-4">
                                    <p class="text-[14px] font-bold text-[var(--color-ink-strong)]">{{ $b->judul }}</p>
                                    <p class="text-[12px] text-[var(--color-ink-muted)]">Jatuh tempo {{ $loan->tanggal_jatuh_tempo->translatedFormat('d M Y') }}
                                        @if ($loan->isTerlambat())
                                            · <span class="font-bold text-[var(--color-danger)]">Terlambat {{ $loan->hariTerlambat() }} hari</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="field-label" for="denda-{{ $loan->id }}">Denda (Rp)</label>
                                        <input id="denda-{{ $loan->id }}" name="denda" type="number" min="0" value="{{ $loan->hitungDenda() }}" class="input text-mono" required>
                                        <p class="mt-1 text-[11px] text-[var(--color-ink-muted)]">Tarif Rp {{ number_format($tarifDenda, 0, ',', '.') }}/hari. Isi 0 bila tidak ada denda.</p>
                                    </div>
                                    <div>
                                        <label class="field-label" for="kondisi-{{ $loan->id }}">Kondisi Buku</label>
                                        <select id="kondisi-{{ $loan->id }}" name="kondisi_buku" class="select" required>
                                            <option value="baik">Baik</option>
                                            <option value="rusak_ringan">Rusak Ringan</option>
                                            <option value="rusak_berat">Rusak Berat</option>
                                            <option value="hilang">Hilang</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="field-label" for="catatan-{{ $loan->id }}">Catatan (opsional)</label>
                                        <input id="catatan-{{ $loan->id }}" name="catatan" type="text" class="input" placeholder="Mis. sampul sedikit lecet.">
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                                <button type="button" data-modal-close="kembali-{{ $loan->id }}" class="btn btn-ghost">Batal</button>
                                <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Konfirmasi Pengembalian</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    @else
        @if ($selesaiHariIni->isEmpty())
            <x-empty-state icon="clock" title="Belum ada pengembalian hari ini" description="Pengembalian yang diproses hari ini akan tampil di sini." />
        @else
            <div class="card">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Kondisi</th>
                                <th>Petugas</th>
                                <th class="text-right">Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selesaiHariIni as $loan)
                                @php $b = $loan->books->first(); @endphp
                                <tr>
                                    <td class="text-mono text-[12px] font-semibold">{{ $loan->kode_transaksi }}</td>
                                    <td class="font-semibold text-[var(--color-ink-strong)]">{{ $loan->member->nama }}</td>
                                    <td class="max-w-[220px]"><span class="line-clamp-1">{{ $b->judul }}</span></td>
                                    <td class="text-[12px] capitalize">{{ str_replace('_', ' ', $loan->kondisi_buku ?? '-') }}</td>
                                    <td class="text-[12px]">{{ $loan->petugas?->name ?? '-' }}</td>
                                    <td class="text-right text-[12px] font-bold {{ $loan->denda > 0 ? 'text-[var(--color-danger)]' : 'text-[var(--color-ink-muted)]' }}">
                                        Rp {{ number_format($loan->denda, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- Modal peminjaman manual --}}
    <div data-modal="pinjam-manual" class="modal-backdrop hidden">
        <div class="modal-panel">
            <form method="POST" action="{{ route('petugas.sirkulasi.pinjam') }}">
                @csrf
                <div class="flex items-start justify-between border-b border-[var(--color-line)] p-5">
                    <div>
                        <h3 class="section-title">Catat Peminjaman Manual</h3>
                        <p class="mt-1 text-[12px] text-[var(--color-ink-muted)]">Untuk peminjaman langsung di meja sirkulasi.</p>
                    </div>
                    <button type="button" data-modal-close="pinjam-manual" class="rounded-lg p-1.5 hover:bg-[var(--color-primary-50)]"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="field-label" for="member_id">Anggota Peminjam</label>
                        <select id="member_id" name="member_id" class="select" required>
                            <option value="">Pilih anggota…</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>{{ $member->nama }} — {{ $member->kelas }} ({{ $member->nisn }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="book_id">Buku</label>
                        <select id="book_id" name="book_id" class="select" required>
                            <option value="">Pilih buku…</option>
                            @foreach ($books as $book)
                                <option value="{{ $book->id }}" @selected(old('book_id') == $book->id)>{{ $book->judul }} ({{ $book->kode_buku }}) — sisa {{ $book->tersedia() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rounded-xl border border-[var(--color-line)] p-4">
                        <p class="label-caps">Jadwal Otomatis</p>
                        <div class="mt-2 grid grid-cols-2 gap-3 text-center">
                            <div>
                                <p class="text-[11px] text-[var(--color-ink-muted)]">Batas Kembali</p>
                                <p class="text-[13px] font-bold text-[var(--color-ink-strong)]">{{ now()->addDays($durasi)->translatedFormat('d M Y') }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-[var(--color-ink-muted)]">Denda / Hari</p>
                                <p class="text-[13px] font-bold text-[var(--color-danger)]">Rp {{ number_format($tarifDenda, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] text-[var(--color-ink-muted)]">Batas maksimal {{ $batas }} pinjaman aktif per anggota. Anggota dengan keterlambatan tidak dapat meminjam.</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-[var(--color-line)] p-5">
                    <button type="button" data-modal-close="pinjam-manual" class="btn btn-ghost">Batal</button>
                    <button type="submit" class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Catat Peminjaman</button>
                </div>
            </form>
        </div>
    </div>
@endsection
