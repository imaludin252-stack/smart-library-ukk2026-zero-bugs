<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnggotaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'jurusan' => $request->query('jurusan'),
            'status' => $request->query('status'),
        ];

        $query = Member::query()->with('user')
            ->withCount(['loans as aktif_count' => fn ($q) => $q->whereIn('status', Loan::STATUS_AKTIF)]);

        if ($filters['q'] !== '') {
            $keyword = $filters['q'];
            $query->where(function ($q) use ($keyword) {
                $q->where('nisn', 'like', "%{$keyword}%")
                    ->orWhere('kelas', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
            });
        }

        if ($filters['jurusan']) {
            $query->where('jurusan', $filters['jurusan']);
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        $members = $query->orderBy('nisn')->paginate(12)->withQueryString();

        return view('petugas.anggota', [
            'members' => $members,
            'filters' => $filters,
            'jurusanList' => ['RPL', 'TKJ', 'AKL', 'OTKP'],
            'totalAnggota' => Member::count(),
            'anggotaAktif' => Member::where('status', 'aktif')->count(),
            'sedangMeminjam' => Member::whereHas('loans', fn ($q) => $q->whereIn('status', Loan::STATUS_AKTIF))->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'nisn' => ['required', 'string', 'digits_between:6,20', 'unique:members,nisn'],
            'kelas' => ['required', 'string', 'max:30'],
            'jurusan' => ['required', 'in:RPL,TKJ,AKL,OTKP'],
            'password' => ['required', 'string', 'min:8'],
        ], [], [
            'name' => 'nama lengkap',
            'nisn' => 'NISN',
            'kelas' => 'kelas',
            'jurusan' => 'jurusan',
            'password' => 'kata sandi',
        ]);

        DB::transaction(function () use ($data) {
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
        });

        return back()->with('success', 'Anggota "'.$data['name'].'" berhasil ditambahkan.');
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($member->user_id)],
            'nisn' => ['required', 'string', 'digits_between:6,20', Rule::unique('members', 'nisn')->ignore($member->id)],
            'kelas' => ['required', 'string', 'max:30'],
            'jurusan' => ['required', 'in:RPL,TKJ,AKL,OTKP'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [], [
            'name' => 'nama lengkap',
            'nisn' => 'NISN',
        ]);

        DB::transaction(function () use ($member, $data) {
            $member->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => $data['status'],
            ]);

            $member->update([
                'nisn' => $data['nisn'],
                'kelas' => $data['kelas'],
                'jurusan' => $data['jurusan'],
                'status' => $data['status'],
            ]);
        });

        return back()->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        if ($member->loans()->whereIn('status', Loan::STATUS_AKTIF)->exists()) {
            return back()->with('error', 'Anggota masih memiliki transaksi aktif. Nonaktifkan akun sebagai gantinya.');
        }

        DB::transaction(function () use ($member) {
            $user = $member->user;
            $member->delete();
            $user?->delete();
        });

        return back()->with('success', 'Data anggota berhasil dihapus.');
    }
}
