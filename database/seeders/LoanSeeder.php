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

        $this->buat($siswa->firstWhere('nisn', '0062819230'), $buku->firstWhere('kode_buku', 'RPL-PWD-001'),
            now()->subDays(5), now()->subDays(5)->addDays($durasi), Loan::STATUS_DIPINJAM, $petugas);

        $this->buat($siswa->firstWhere('nisn', '0062819230'), $buku->firstWhere('kode_buku', 'RPL-ALG-004'),
            now()->subDays(2), now()->subDays(2)->addDays($durasi), Loan::STATUS_DIPINJAM, $petugas);

        $this->buat($siswa->firstWhere('nisn', '0062819230'), $buku->firstWhere('kode_buku', 'UMK-BIN-002'),
            now()->subDay(), now()->subDay()->addDays($durasi), Loan::STATUS_DIAJUKAN, null);

        $this->buat($siswa->firstWhere('nisn', '0068491022'), $buku->firstWhere('kode_buku', 'RPL-PBD-002'),
            now()->subDays(9), now()->subDays(2), Loan::STATUS_TERLAMBAT, $petugas2);

        $this->buat($siswa->firstWhere('nisn', '0057731188'), $buku->firstWhere('kode_buku', 'TKJ-JAR-001'),
            now()->subDays(3), now()->subDays(3)->addDays($durasi), Loan::STATUS_DIPINJAM, $petugas);

        $this->buat($siswa->firstWhere('nisn', '0069123401'), $buku->firstWhere('kode_buku', 'AKL-AKD-001'),
            now()->subDays(6), now()->subDays(6)->addDays($durasi), Loan::STATUS_DIAJUKAN, null);

        $this->buat($siswa->firstWhere('nisn', '0063345512'), $buku->firstWhere('kode_buku', 'OTKP-MPB-001'),
            now()->subDays(4), now()->subDays(4)->addDays($durasi), Loan::STATUS_DIAJUKAN, null);

        $this->buat($siswa->firstWhere('nisn', '0067782210'), $buku->firstWhere('kode_buku', 'TKJ-KJD-003'),
            now()->subDays(8), now()->subDays(1), Loan::STATUS_DIPINJAM, $petugas2);

        $selesai = [
            ['UMK-MTD-001', 20, 14, 14, 0],
            ['UMK-PKN-001', 35, 28, 28, 0],
            ['UMK-INF-002', 30, 23, 23, 0],
            ['RPL-PAM-003', 50, 43, 40, 3],
            ['UMK-BIN-001', 45, 38, 38, 0],
            ['TKJ-TKN-002', 40, 33, 33, 0],
        ];

        foreach ($selesai as [$kode, $lalu, $pinjam, $kembali, $terlambatHari]) {
            $book = $buku->firstWhere('kode_buku', $kode);
            if (! $book) {
                continue;
            }

            $loan = $this->buat(
                $siswa->firstWhere('nisn', '0062819230'),
                $book,
                now()->subDays($lalu),
                now()->subDays($lalu)->addDays($durasi),
                $terlambatHari > 0 ? Loan::STATUS_TERLAMBAT : Loan::STATUS_DIKEMBALIKAN,
                $petugas,
            );

            $loan->update([
                'tanggal_kembali' => now()->subDays($lalu)->addDays($kembali)->toDateString(),
                'denda' => $terlambatHari * (int) Setting::get('tarif_denda', 1000),
                'kondisi_buku' => 'baik',
            ]);
        }
    }

    private function buat(?Member $member, ?Book $book, $pinjam, $jatuhTempo, string $status, ?User $petugas): ?Loan
    {
        if (! $member || ! $book) {
            return null;
        }

        $loan = Loan::create([
            'kode_transaksi' => Loan::generateKodeTransaksi(),
            'member_id' => $member->id,
            'petugas_id' => $petugas?->id,
            'tanggal_pinjam' => $pinjam->toDateString(),
            'tanggal_jatuh_tempo' => $jatuhTempo->toDateString(),
            'tanggal_kembali' => in_array($status, [Loan::STATUS_DIKEMBALIKAN, Loan::STATUS_DITOLAK, Loan::STATUS_DIBATALKAN], true)
                ? now()->toDateString() : null,
            'status' => $status,
        ]);

        $loan->books()->attach($book->id);

        return $loan;
    }
}
