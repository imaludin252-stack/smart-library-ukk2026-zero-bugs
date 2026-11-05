<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

/**
 * Otorisasi kepemilikan transaksi (S3-01, anti-IDOR).
 *
 * Siswa hanya boleh melihat/mengubah transaksi miliknya sendiri, sedangkan
 * petugas dan admin boleh mengelola seluruh transaksi melalui meja sirkulasi.
 */
class LoanPolicy
{
    public function view(User $user, Loan $loan): bool
    {
        if ($user->dapatMengelola()) {
            return true;
        }

        if (! $user->isSiswa() || ! $user->member) {
            return false;
        }

        return (int) $user->member->id_anggota === (int) $loan->id_anggota;
    }

    public function update(User $user, Loan $loan): bool
    {
        return $this->view($user, $loan);
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $this->view($user, $loan);
    }
}
