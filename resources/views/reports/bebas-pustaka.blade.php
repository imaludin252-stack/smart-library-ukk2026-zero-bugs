<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Bebas Pustaka</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { color: #1a1a1a; font-size: 12px; margin: 0; padding: 40px 48px; }
        .kop { border-bottom: 3px double #1e40ae; padding-bottom: 12px; margin-bottom: 20px; text-align: center; }
        .kop h1 { font-size: 19px; margin: 0; color: #0b2773; letter-spacing: .5px; }
        .kop h2 { font-size: 13px; margin: 3px 0 0; font-weight: normal; color: #333; }
        .kop p { font-size: 10px; color: #666; margin: 5px 0 0; }
        .judul { text-align: center; margin: 26px 0 6px; }
        .judul h3 { margin: 0; font-size: 16px; text-transform: uppercase; text-decoration: underline; color: #0b2773; }
        .nomor { text-align: center; font-size: 11px; color: #444; margin-bottom: 24px; }
        .body p { line-height: 1.7; margin: 10px 0; }
        table.data { width: 100%; border-collapse: collapse; margin: 16px 0 20px; }
        table.data td { padding: 6px 8px; vertical-align: top; font-size: 12px; }
        table.data td.label { width: 190px; color: #444; }
        table.data td.colon { width: 12px; }
        .box { border: 1px solid #c9d6ee; background: #f8f9ff; padding: 12px 16px; margin: 18px 0; }
        .box strong { color: #0b2773; }
        .sign { margin-top: 40px; width: 240px; float: right; text-align: center; }
        .sign .space { height: 62px; }
        .sign .name { font-weight: bold; text-decoration: underline; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>{{ strtoupper($sekolah) }}</h1>
        <h2>Perpustakaan Smart Library</h2>
        <p>Jalan Raya Ciwidey, Kabupaten Bandung · Tahun Ajaran {{ \App\Models\Setting::get('tahun_ajaran', '2026/2027') }}</p>
    </div>

    <div class="judul">
        <h3>Surat Keterangan Bebas Pustaka</h3>
    </div>
    <div class="nomor">Nomor: BP/{{ $member->nisn }}/{{ now()->format('Y') }}</div>

    <div class="body">
        <p>Yang bertanda tangan di bawah ini, Kepala Perpustakaan {{ $sekolah }}, dengan ini menerangkan bahwa:</p>

        <table class="data">
            <tr><td class="label">Nama Lengkap</td><td class="colon">:</td><td><strong>{{ $user->name }}</strong></td></tr>
            <tr><td class="label">NISN</td><td class="colon">:</td><td>{{ $member->nisn }}</td></tr>
            <tr><td class="label">Kelas / Program Keahlian</td><td class="colon">:</td><td>{{ $member->kelas }} · {{ $member->jurusan }}</td></tr>
            <tr><td class="label">Email</td><td class="colon">:</td><td>{{ $user->email }}</td></tr>
        </table>

        <div class="box">
            <p style="margin: 0;">Berdasarkan catatan sistem <strong>Smart Library</strong>, siswa tersebut <strong>TIDAK MEMILIKI</strong> tanggungan peminjaman buku maupun denda yang belum diselesaikan.</p>
        </div>

        <p>Rincian rekapitulasi peminjaman:</p>
        <table class="data">
            <tr><td class="label">Total Peminjaman Selesai</td><td class="colon">:</td><td><strong>{{ $totalPinjam }}</strong> transaksi</td></tr>
            <tr><td class="label">Total Denda</td><td class="colon">:</td><td><strong>Rp {{ number_format($totalDenda, 0, ',', '.') }}</strong></td></tr>
            <tr><td class="label">Status</td><td class="colon">:</td><td><strong style="color: #0b593f;">BEBAS PUSTAKA</strong></td></tr>
        </table>

        <p>Surat keterangan ini dibuat sebagai bukti bahwa yang bersangkutan telah menyelesaikan seluruh kewajibannya di perpustakaan sekolah dan dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    <div class="sign">
        <p>Ciwidey, {{ now()->translatedFormat('d F Y') }}</p>
        <p>Kepala Perpustakaan</p>
        <div class="space"></div>
        <p class="name">{{ $user->name }}</p>
    </div>
    <div class="clear"></div>
</body>
</html>
