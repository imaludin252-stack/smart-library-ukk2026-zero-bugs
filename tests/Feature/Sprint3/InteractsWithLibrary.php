<?php

namespace Tests\Feature\Sprint3;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;

/**
 * Helper data uji fungsional Sprint 3 (S3-03).
 */
trait InteractsWithLibrary
{
    /** @return array{0: User, 1: Member} */
    protected function buatSiswa(array $atribut = []): array
    {
        $user = User::factory()->create(array_merge([
            'role' => User::ROLE_SISWA,
            'status' => User::STATUS_AKTIF,
        ], $atribut));

        $member = Member::factory()->create([
            'id_user' => $user->id_user,
            'status' => 'aktif',
        ]);

        return [$user, $member];
    }

    protected function buatPetugas(array $atribut = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_PETUGAS,
            'status' => User::STATUS_AKTIF,
        ], $atribut));
    }

    protected function buatAdmin(array $atribut = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_AKTIF,
        ], $atribut));
    }

    protected function buatBuku(array $atribut = []): Book
    {
        return Book::factory()->create(array_merge([
            'stok' => 2,
            'status' => Book::STATUS_AKTIF,
        ], $atribut));
    }

    protected function buatPinjaman(
        Member $member,
        Book $book,
        string $status = Loan::STATUS_DIAJUKAN,
        array $atribut = []
    ): Loan {
        $loan = Loan::create(array_merge([
            'kode_transaksi' => Loan::generateKodeTransaksi(),
            'id_anggota' => $member->id_anggota,
            'tanggal_pinjam' => now()->subDays(2)->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(5)->toDateString(),
            'status' => $status,
        ], $atribut));

        $loan->books()->attach($book->id_buku);

        return $loan;
    }
}
