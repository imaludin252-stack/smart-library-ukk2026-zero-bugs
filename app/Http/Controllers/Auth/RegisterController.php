<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(auth()->user()->dashboardRoute());
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'nisn' => ['required', 'string', 'digits_between:6,20', 'unique:members,nisn'],
            'kelas' => ['required', 'string', 'max:30'],
            'jurusan' => ['required', 'in:RPL,TKJ,AKL,OTKP'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'name' => 'nama lengkap',
            'email' => 'email',
            'nisn' => 'NISN',
            'kelas' => 'kelas',
            'jurusan' => 'jurusan',
            'password' => 'kata sandi',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_SISWA,
                'status' => User::STATUS_AKTIF,
            ]);

            Member::create([
                'user_id' => $user->id,
                'nisn' => $data['nisn'],
                'kelas' => $data['kelas'],
                'jurusan' => $data['jurusan'],
                'status' => 'aktif',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('beranda')->with('success', 'Registrasi berhasil. Selamat datang, '.$user->name.'!');
    }
}
