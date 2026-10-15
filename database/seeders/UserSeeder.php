<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Budi Santoso, S.Kom.',
            'email' => 'admin@smartlibrary.sch.id',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_AKTIF,
        ]);

        User::create([
            'name' => 'Siti Nurhaliza, S.Pd.',
            'email' => 'petugas@smartlibrary.sch.id',
            'password' => 'password',
            'role' => User::ROLE_PETUGAS,
            'status' => User::STATUS_AKTIF,
        ]);

        User::create([
            'name' => 'Dedi Kurniawan, S.Pd.',
            'email' => 'petugas2@smartlibrary.sch.id',
            'password' => 'password',
            'role' => User::ROLE_PETUGAS,
            'status' => User::STATUS_AKTIF,
        ]);

        $siswa = [
            ['Rizky Pratama', 'rizky@siswa.smkbb.sch.id', '0062819230', 'XII RPL 1', 'RPL'],
            ['Aisyah Rahmawati', 'aisyah@siswa.smkbb.sch.id', '0068491022', 'XII RPL 1', 'RPL'],
            ['Fajar Nugroho', 'fajar@siswa.smkbb.sch.id', '0057731188', 'XI TKJ 2', 'TKJ'],
            ['Dewi Anggraini', 'dewi@siswa.smkbb.sch.id', '0069123401', 'XI AKL 1', 'AKL'],
            ['Rafi Ahmad Maulana', 'rafi@siswa.smkbb.sch.id', '0063345512', 'X OTKP 1', 'OTKP'],
            ['Nabila Putri Ramadhani', 'nabila@siswa.smkbb.sch.id', '0067782210', 'XII RPL 2', 'RPL'],
            ['Gilang Ramadhan', 'gilang@siswa.smkbb.sch.id', '0058890034', 'XI TKJ 1', 'TKJ'],
            ['Salsabila Ayu', 'salsabila@siswa.smkbb.sch.id', '0064451190', 'XI AKL 2', 'AKL'],
        ];

        foreach ($siswa as [$nama, $email, $nisn, $kelas, $jurusan]) {
            $user = User::create([
                'name' => $nama,
                'email' => $email,
                'password' => 'password',
                'role' => User::ROLE_SISWA,
                'status' => User::STATUS_AKTIF,
            ]);

            Member::create([
                'user_id' => $user->id,
                'nisn' => $nisn,
                'kelas' => $kelas,
                'jurusan' => $jurusan,
                'status' => 'aktif',
            ]);
        }
    }
}
