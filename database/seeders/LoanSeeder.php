<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    public function run(): void
    {
        $petugas = User::where('email', 'petugas@smartlibrary.sch.id')->first();
        $petugas2 = User::where('email', 'petugas2@smartlibrary.sch.id')->first();
        $siswa = Member::with('user')->get();
        $buku = Book::all();

        if ($siswa->count() < 3 || $buku->count() < 6) {
            return;
        }

        $durasi = (int) Setting::get('durasi_pinjam', 7);
        $tarif = (int) Setting::get('tarif_denda', 1000);

        // Satu pinjaman aktif dan satu pinjaman terlambat sehari.
        $this->buat($siswa->firstWhere('nisn', '0062819230'), $buku->firstWhere('kode_buku', 'RPL-PWD-001'),
            now()->subDays(5), now()->subDays(5)->addDays($durasi), Loan::STATUS_DIPINJAM, $petugas);

        $this->buat($siswa->firstWhere('nisn', '0062819230'), $buku->firstWhere('kode_buku', 'RPL-PBD-002'),
            now()->subDays(9), now()->subDay(), Loan::STATUS_TERLAMBAT, $petugas);

        // Satu pengajuan baru agar Meja Sirkulasi memiliki antrean saat demo.
        $this->buat($siswa->firstWhere('nisn', '0062819230'), $buku->firstWhere('kode_buku', 'UMK-BIN-002'),
            now()->subDay(), now()->subDay()->addDays($durasi), Loan::STATUS_DIAJUKAN, null);

        // Delapan transaksi dikembalikan dengan denda, total Rp194.000.
        $denda = [
            ['0062819230', 'RPL-PAM-003', 80, 73, 10, 63],
            ['0062819230', 'RPL-ALG-004', 45, 38, 16, 22],
            ['0068491022', 'RPL-PBD-002', 40, 33, 18, 15],
            ['0057731188', 'TKJ-JAR-001', 38, 31, 14, 17],
            ['0069123401', 'AKL-AKD-001', 42, 35, 13, 22],
            ['0067782210', 'TKJ-KJD-003', 36, 29, 13, 16],
            ['0063345512', 'OTKP-MPB-001', 48, 41, 23, 18],
            ['0058890034', 'TKJ-TKN-002', 35, 28, 7, 21],
        ];

        foreach ($denda as [$nisn, $kode, $pinjamHari, $jatuhHari, $kembaliHari, $terlambatHari]) {
            $this->buat(
                $siswa->firstWhere('nisn', $nisn),
                $buku->firstWhere('kode_buku', $kode),
                now()->subDays($pinjamHari),
                now()->subDays($jatuhHari),
                Loan::STATUS_DIKEMBALIKAN,
                $petugas,
                now()->subDays($kembaliHari),
                $terlambatHari * $tarif,
            );
        }

        // Sembilan transaksi dikembalikan tepat waktu tanpa denda.
        $tepatWaktu = [
            ['0062819230', 'UMK-MTD-001', 20, 13, 14],
            ['0062819230', 'UMK-PKN-001', 35, 28, 28],
            ['0062819230', 'UMK-INF-002', 30, 23, 23],
            ['0062819230', 'UMK-BIN-001', 45, 38, 39],
            ['0064451190', 'AKL-PAK-002', 25, 18, 19],
            ['0064451190', 'UMK-BIN-002', 32, 25, 26],
            ['0067782210', 'RPL-DGS-005', 28, 21, 22],
            ['0057731188', 'TKJ-SJL-004', 33, 26, 27],
        ];

        foreach ($tepatWaktu as [$nisn, $kode, $pinjamHari, $jatuhHari, $kembaliHari]) {
            $this->buat(
                $siswa->firstWhere('nisn', $nisn),
                $buku->firstWhere('kode_buku', $kode),
                now()->subDays($pinjamHari),
                now()->subDays($jatuhHari),
                Loan::STATUS_DIKEMBALIKAN,
                $petugas2,
                now()->subDays($kembaliHari),
            );
        }
    }

    private function buat(?Member $member, ?Book $book, $pinjam, $jatuhTempo, string $status, ?User $petugas, $kembali = null, int $denda = 0): ?Loan
    {
        if (! $member || ! $book) {
            return null;
        }

        $loan = Loan::create([
            'kode_transaksi' => Loan::generateKodeTransaksi(),
            'id_anggota' => $member->id_anggota,
            'id_user' => $petugas?->id_user,
            'tanggal_pinjam' => $pinjam->toDateString(),
            'tanggal_jatuh_tempo' => $jatuhTempo->toDateString(),
            'tanggal_kembali' => $kembali?->toDateString(),
            'status' => $status,
            'denda' => $denda,
            'kondisi_buku' => $kembali ? 'baik' : null,
        ]);

        $loan->books()->attach($book->id_buku);

        return $loan;
    }
}
