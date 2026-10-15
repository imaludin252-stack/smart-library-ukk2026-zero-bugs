<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        Loan::segarkanKeterlambatan();

        $totalKoleksi = (int) Book::where('status', Book::STATUS_AKTIF)->sum('stok');
        $sedangDipinjam = Loan::whereIn('status', [Loan::STATUS_DIPINJAM, Loan::STATUS_TERLAMBAT])->count();
        $jatuhTempoHariIni = Loan::where('status', Loan::STATUS_DIPINJAM)
            ->whereDate('tanggal_jatuh_tempo', now()->toDateString())
            ->count();
        $terlambatQuery = Loan::where('status', Loan::STATUS_TERLAMBAT);
        $jumlahTerlambat = (clone $terlambatQuery)->count();
        $totalDenda = (int) (clone $terlambatQuery)->sum('denda')
            + Loan::where('status', Loan::STATUS_TERLAMBAT)->get()->sum(fn (Loan $l) => $l->hitungDenda());

        $permohonan = Loan::with(['member.user', 'books'])
            ->where('status', Loan::STATUS_DIAJUKAN)
            ->oldest()
            ->get();

        $perhatian = Loan::with(['member.user', 'books'])
            ->where(function ($q) {
                $q->where('status', Loan::STATUS_TERLAMBAT)
                    ->orWhere(function ($sub) {
                        $sub->where('status', Loan::STATUS_DIPINJAM)
                            ->whereDate('tanggal_jatuh_tempo', '<=', now()->addDays(2)->toDateString());
                    });
            })
            ->orderBy('tanggal_jatuh_tempo')
            ->limit(10)
            ->get();

        $distribusi = Book::query()
            ->select('jurusan', DB::raw('sum(stok) as total'))
            ->where('status', Book::STATUS_AKTIF)
            ->groupBy('jurusan')
            ->pluck('total', 'jurusan');

        $maxDistribusi = max(1, (int) $distribusi->max());

        return view('petugas.dashboard', [
            'totalKoleksi' => $totalKoleksi,
            'sedangDipinjam' => $sedangDipinjam,
            'jatuhTempoHariIni' => $jatuhTempoHariIni,
            'jumlahTerlambat' => $jumlahTerlambat,
            'totalDenda' => $totalDenda,
            'permohonan' => $permohonan,
            'perhatian' => $perhatian,
            'distribusi' => $distribusi,
            'maxDistribusi' => $maxDistribusi,
            'petugas' => $request->user(),
            'tanggal' => Carbon::now()->translatedFormat('l, d F Y'),
        ]);
    }
}
