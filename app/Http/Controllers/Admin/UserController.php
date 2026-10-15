<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'role' => $request->query('role'),
            'status' => $request->query('status'),
        ];

        $query = User::query()->withCount([
            'loansDiproses as diproses_count',
        ]);

        if ($filters['q'] !== '') {
            $keyword = $filters['q'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
        }

        if ($filters['role']) {
            $query->where('role', $filters['role']);
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        $users = $query->orderBy('role')->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.pengguna', [
            'users' => $users,
            'filters' => $filters,
            'totalPengguna' => User::count(),
            'totalPetugas' => User::whereIn('role', [User::ROLE_PETUGAS, User::ROLE_ADMIN])->count(),
            'totalSiswa' => User::where('role', User::ROLE_SISWA)->count(),
            'totalNonaktif' => User::where('status', User::STATUS_NONAKTIF)->count(),
            'roles' => [User::ROLE_PETUGAS => 'Petugas Perpustakaan', User::ROLE_ADMIN => 'Administrator'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_PETUGAS, User::ROLE_ADMIN])],
            'password' => ['required', 'string', 'min:8'],
        ], [], [
            'name' => 'nama lengkap',
            'role' => 'peran',
            'password' => 'kata sandi',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => User::STATUS_AKTIF,
        ]);

        return back()->with('success', 'Akun "'.$data['name'].'" ('.$data['role'].') berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_PETUGAS, User::ROLE_ADMIN])],
            'status' => ['required', Rule::in([User::STATUS_AKTIF, User::STATUS_NONAKTIF])],
        ], [], ['name' => 'nama lengkap', 'role' => 'peran']);

        if ($user->id === $request->user()->id && $data['status'] === User::STATUS_NONAKTIF) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        if ($user->id === $request->user()->id && $data['role'] !== User::ROLE_ADMIN) {
            return back()->with('error', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
        }

        $user->update($data);

        return back()->with('success', 'Akun "'.$user->name.'" berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update([
            'status' => $user->isAktif() ? User::STATUS_NONAKTIF : User::STATUS_AKTIF,
        ]);

        return back()->with('success', 'Status akun "'.$user->name.'" kini '.$user->fresh()->status.'.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if (Loan::where('petugas_id', $user->id)->exists()) {
            return back()->with('error', 'Akun ini memiliki riwayat transaksi. Nonaktifkan akun sebagai gantinya.');
        }

        $nama = $user->name;
        $user->delete();

        return back()->with('success', 'Akun "'.$nama.'" berhasil dihapus.');
    }
}
