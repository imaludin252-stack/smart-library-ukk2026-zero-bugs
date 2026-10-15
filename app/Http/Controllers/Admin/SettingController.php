<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.pengaturan', [
            'settings' => Setting::semua(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'durasi_pinjam' => ['required', 'integer', 'min:1', 'max:90'],
            'batas_pinjam_aktif' => ['required', 'integer', 'min:1', 'max:20'],
            'tarif_denda' => ['required', 'integer', 'min:0', 'max:1000000'],
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'jam_buka' => ['required', 'string', 'max:100'],
        ], [], [
            'durasi_pinjam' => 'durasi pinjam',
            'batas_pinjam_aktif' => 'batas pinjam aktif',
            'tarif_denda' => 'tarif denda',
            'nama_sekolah' => 'nama sekolah',
            'tahun_ajaran' => 'tahun ajaran',
            'jam_buka' => 'jam buka',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        Cache::forget('settings.all');

        return back()->with('success', 'Konfigurasi sistem berhasil disimpan.');
    }
}
