<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DendaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
        ];

        $base = Loan::query()
            ->whereNotNull('tanggal_kembali')
            ->where('denda', '>', 0);

        $totalDenda = (int) (clone $base)->sum('denda');
        $dendaBelumLunas = (int) (clone $base)
            ->where('status_denda', Loan::STATUS_DENDA_BELUM_LUNAS)
            ->sum('denda');
        $dendaLunas = (int) (clone $base)
            ->where('status_denda', Loan::STATUS_DENDA_LUNAS)
            ->sum('denda');
        $jumlahTransaksi = (clone $base)->count();

        $query = (clone $base)->with(['member.user', 'books']);

        if ($filters['q'] !== '') {
            $keyword = $filters['q'];
            $query->where(function ($q) use ($keyword) {
                $q->where('kode_transaksi', 'like', "%{$keyword}%")
                    ->orWhereHas('member', function ($member) use ($keyword) {
                        $member->where('nisn', 'like', "%{$keyword}%")
                            ->orWhere('kelas', 'like', "%{$keyword}%")
                            ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%"));
                    })
                    ->orWhereHas('books', fn ($book) => $book->where('judul', 'like', "%{$keyword}%"));
            });
        }

        if (in_array($filters['status'], [Loan::STATUS_DENDA_BELUM_LUNAS, Loan::STATUS_DENDA_LUNAS], true)) {
            $query->where('status_denda', $filters['status']);
        }

        $loans = $query
            ->orderByDesc('tanggal_kembali')
            ->orderByDesc('id_peminjaman')
            ->paginate(15)
            ->withQueryString();

        return view('petugas.denda.index', [
            'loans' => $loans,
            'filters' => $filters,
            'totalDenda' => $totalDenda,
            'dendaBelumLunas' => $dendaBelumLunas,
            'dendaLunas' => $dendaLunas,
            'jumlahTransaksi' => $jumlahTransaksi,
        ]);
    }

    public function update(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->tanggal_kembali !== null, 404, 'Transaksi belum dikembalikan.');

        $data = $request->validate([
            'denda' => ['required', 'integer', 'min:0', 'max:100000000'],
            'status_denda' => ['required', Rule::in([
                Loan::STATUS_DENDA_BELUM_LUNAS,
                Loan::STATUS_DENDA_LUNAS,
            ])],
        ], [], [
            'denda' => 'denda',
            'status_denda' => 'status denda',
        ]);

        $lunas = $data['status_denda'] === Loan::STATUS_DENDA_LUNAS;

        $loan->update([
            'denda' => (int) $data['denda'],
            'status_denda' => $data['status_denda'],
            'tanggal_bayar_denda' => $lunas
                ? ($loan->tanggal_bayar_denda?->toDateString() ?? now()->toDateString())
                : null,
        ]);

        return back()->with('success', 'Denda transaksi '.$loan->kode_transaksi.' berhasil diperbarui.');
    }
}
