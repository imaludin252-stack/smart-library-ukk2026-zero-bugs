<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('member');
        $member = $user->member;

        $statistik = null;
        if ($member) {
            Loan::segarkanKeterlambatan($member->id);
            $statistik = [
                'aktif' => Loan::untukMember($member)->whereIn('status', Loan::STATUS_AKTIF)->count(),
                'selesai' => Loan::untukMember($member)->whereNotNull('tanggal_kembali')->count(),
                'terlambat' => Loan::untukMember($member)->where('status', Loan::STATUS_TERLAMBAT)->count(),
                'denda' => (int) Loan::untukMember($member)->sum('denda'),
            ];
        }

        return view('profil.index', [
            'user' => $user,
            'member' => $member,
            'statistik' => $statistik,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'password_lama' => 'kata sandi saat ini',
            'password' => 'kata sandi baru',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
