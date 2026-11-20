<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Program keahlian yang ditampilkan pada grafik distribusi koleksi. */
    private const PROGRAM_KEAHLIAN = ['RPL', 'DKV', 'BDP'];

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
            ->whereIn('jurusan', self::PROGRAM_KEAHLIAN)
            ->groupBy('jurusan')
            ->pluck('total', 'jurusan');

        $maxDistribusi = max(1, (int) $distribusi->max());

        // SKKNI 4: rekap agregat COUNT + SUM per kategori koleksi.
        // "total_stok" adalah adaptasi "total nominal" pada proyek perpustakaan.
        $rekapKategori = Category::query()
            ->leftJoin('books', function ($join) {
                $join->on('books.id_kategori', '=', 'categories.id_kategori')
                    ->where('books.status', '=', Book::STATUS_AKTIF);
            })
            ->select([
                'categories.id_kategori',
                'categories.nama',
                DB::raw('COUNT(books.id_buku) as total_data'),
                DB::raw('COALESCE(SUM(books.stok), 0) as total_stok'),
            ])
            ->groupBy('categories.id_kategori', 'categories.nama')
            ->orderBy('categories.nama')
            ->get();

        $totalJudul = (int) $rekapKategori->sum('total_data');
        $totalEksemplar = (int) $rekapKategori->sum('total_stok');

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
            'rekapKategori' => $rekapKategori,
            'totalJudul' => $totalJudul,
            'totalEksemplar' => $totalEksemplar,
            'programKeahlian' => self::PROGRAM_KEAHLIAN,
            'petugas' => $request->user(),
            'tanggal' => Carbon::now()->translatedFormat('l, d F Y'),
        ]);
    }
}
