<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'durasi_pinjam' => '7',
            'batas_pinjam_aktif' => '3',
            'tarif_denda' => '1000',
            'nama_sekolah' => 'SMK Budi Bakti Ciwidey',
            'tahun_ajaran' => '2026/2027',
            'jam_buka' => 'Senin–Jumat, 07.30–15.30 WIB',
        ];

        foreach ($defaults as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
