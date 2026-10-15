<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { color: #1a1a1a; font-size: 11px; margin: 0; padding: 24px 28px; }
        .header { border-bottom: 3px double #1e40ae; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { font-size: 18px; margin: 0; color: #0b2773; letter-spacing: .5px; }
        .header h2 { font-size: 13px; margin: 2px 0 0; font-weight: normal; color: #444; }
        .header .meta { font-size: 10px; color: #666; margin-top: 6px; }
        .title { text-align: center; margin: 14px 0 4px; }
        .title h3 { margin: 0; font-size: 15px; text-transform: uppercase; color: #0b2773; }
        .title p { margin: 3px 0 0; font-size: 11px; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th { background: #eff4ff; color: #0b2773; text-align: left; padding: 7px 8px; border: 1px solid #c9d6ee; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px 8px; border: 1px solid #dbe3f1; vertical-align: top; }
        tr:nth-child(even) td { background: #f8f9ff; }
        .center { text-align: center; }
        .right { text-align: right; }
        .footer { margin-top: 26px; }
        .footer .sign { float: right; width: 220px; text-align: center; }
        .footer .sign .space { height: 54px; }
        .footer .sign .name { font-weight: bold; text-decoration: underline; }
        .muted { color: #666; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ strtoupper($sekolah) }}</h1>
        <h2>Perpustakaan Smart Library</h2>
        <div class="meta">Jalan Raya Ciwidey, Kabupaten Bandung · Tahun Ajaran {{ \App\Models\Setting::get('tahun_ajaran', '2026/2027') }}</div>
    </div>

    <div class="title">
        <h3>{{ $judul }}</h3>
        <p>Periode {{ \Illuminate\Support\Carbon::parse($dari)->translatedFormat('d F Y') }} s.d. {{ \Illuminate\Support\Carbon::parse($sampai)->translatedFormat('d F Y') }}</p>
    </div>

    @if ($jenis === 'populer')
        <table>
            <thead>
                <tr>
                    <th class="center" style="width: 34px;">No</th>
                    <th>Kode Buku</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Jurusan</th>
                    <th class="center">Jumlah Pinjam</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($populer as $i => $book)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $book->kode_buku }}</td>
                        <td>{{ $book->judul }}</td>
                        <td>{{ $book->kategori?->nama }}</td>
                        <td>{{ $book->jurusan }}</td>
                        <td class="center">{{ $book->total_pinjam }}x</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="center muted">Tidak ada data pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th class="center" style="width: 34px;">No</th>
                    <th>Kode Transaksi</th>
                    <th>Peminjam</th>
                    <th>Kelas</th>
                    <th>Buku</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Kembali</th>
                    <th>Status</th>
                    <th class="right">Denda</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $i => $loan)
                    @php $b = $loan->books->first(); @endphp
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $loan->kode_transaksi }}</td>
                        <td>{{ $loan->member?->nama }}</td>
                        <td>{{ $loan->member?->kelas }}</td>
                        <td>{{ $b?->judul }}</td>
                        <td>{{ $loan->tanggal_pinjam?->translatedFormat('d/m/Y') }}</td>
                        <td>{{ $loan->tanggal_jatuh_tempo?->translatedFormat('d/m/Y') }}</td>
                        <td>{{ $loan->tanggal_kembali?->translatedFormat('d/m/Y') ?? '-' }}</td>
                        <td>{{ $loan->statusLabel() }}</td>
                        <td class="right">Rp {{ number_format($loan->denda, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="center muted">Tidak ada data pada periode ini.</td></tr>
                @endforelse
            </tbody>
            @if ($jenis === 'transaksi' && $loans->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="9" class="right" style="font-weight: bold; background: #eff4ff;">Total Denda Periode</td>
                        <td class="right" style="font-weight: bold; background: #eff4ff;">Rp {{ number_format($loans->sum('denda'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    <div class="footer">
        <p class="muted">Dicetak pada {{ $dicetak }} oleh {{ $petugas?->name }} ({{ $petugas?->roleLabel() }}).</p>
        <div class="sign">
            <p>Ciwidey, {{ now()->translatedFormat('d F Y') }}</p>
            <p>Petugas Perpustakaan</p>
            <div class="space"></div>
            <p class="name">{{ $petugas?->name }}</p>
        </div>
        <div class="clear"></div>
    </div>
</body>
</html>
