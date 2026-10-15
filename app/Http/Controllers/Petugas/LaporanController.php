<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        [$dari, $sampai] = $this->periode($request);

        $base = Loan::query()
            ->whereDate('tanggal_pinjam', '>=', $dari)
            ->whereDate('tanggal_pinjam', '<=', $sampai);

        $totalTransaksi = (clone $base)->count();
        $siswaAktif = (clone $base)->distinct('member_id')->count('member_id');
        $dendaTerkumpul = (int) (clone $base)->sum('denda');
        $totalSelesai = (clone $base)->whereNotNull('tanggal_kembali')->count();
        $totalTerlambat = (clone $base)->where('denda', '>', 0)->count();

        $trenBulanan = collect(range(5, 0))->map(function ($back) {
            $bulan = now()->subMonths($back);

            return [
                'label' => $bulan->translatedFormat('M Y'),
                'total' => Loan::whereYear('tanggal_pinjam', $bulan->year)
                    ->whereMonth('tanggal_pinjam', $bulan->month)
                    ->count(),
            ];
        });

        $maxTren = max(1, (int) $trenBulanan->max('total'));

        $bukuPopuler = Book::query()
            ->with('kategori')
            ->withCount(['loanItems as total_pinjam' => fn ($q) => $q->whereHas('loan', fn ($l) => $l
                ->whereNotIn('status', [Loan::STATUS_DITOLAK, Loan::STATUS_DIBATALKAN])
                ->whereDate('tanggal_pinjam', '>=', $dari)
                ->whereDate('tanggal_pinjam', '<=', $sampai))])
            ->having('total_pinjam', '>', 0)
            ->orderByDesc('total_pinjam')
            ->limit(8)
            ->get();

        $maxPopuler = max(1, (int) $bukuPopuler->max('total_pinjam'));

        $rekapKelas = DB::table('loans')
            ->join('members', 'members.id', '=', 'loans.member_id')
            ->whereDate('loans.tanggal_pinjam', '>=', $dari)
            ->whereDate('loans.tanggal_pinjam', '<=', $sampai)
            ->select('members.kelas', DB::raw('count(*) as total'))
            ->groupBy('members.kelas')
            ->orderByDesc('total')
            ->get();

        $distribusiJurusan = DB::table('loans')
            ->join('members', 'members.id', '=', 'loans.member_id')
            ->whereDate('loans.tanggal_pinjam', '>=', $dari)
            ->whereDate('loans.tanggal_pinjam', '<=', $sampai)
            ->select('members.jurusan', DB::raw('count(*) as total'))
            ->groupBy('members.jurusan')
            ->pluck('total', 'jurusan');

        $maxJurusan = max(1, (int) $distribusiJurusan->max());

        return view('petugas.laporan.index', [
            'dari' => $dari,
            'sampai' => $sampai,
            'totalTransaksi' => $totalTransaksi,
            'siswaAktif' => $siswaAktif,
            'dendaTerkumpul' => $dendaTerkumpul,
            'totalSelesai' => $totalSelesai,
            'totalTerlambat' => $totalTerlambat,
            'trenBulanan' => $trenBulanan,
            'maxTren' => $maxTren,
            'bukuPopuler' => $bukuPopuler,
            'maxPopuler' => $maxPopuler,
            'rekapKelas' => $rekapKelas,
            'distribusiJurusan' => $distribusiJurusan,
            'maxJurusan' => $maxJurusan,
        ]);
    }

    public function pdf(Request $request): Response
    {
        [$dari, $sampai] = $this->periode($request);
        $jenis = $request->query('jenis', 'transaksi');

        $judul = match ($jenis) {
            'terlambat' => 'Laporan Buku Terlambat',
            'populer' => 'Laporan Buku Terpopuler',
            default => 'Laporan Transaksi Peminjaman',
        };

        $loans = collect();
        $populer = collect();

        if ($jenis === 'transaksi') {
            $loans = Loan::with(['member.user', 'books', 'petugas'])
                ->whereDate('tanggal_pinjam', '>=', $dari)
                ->whereDate('tanggal_pinjam', '<=', $sampai)
                ->oldest('tanggal_pinjam')
                ->get();
        } elseif ($jenis === 'terlambat') {
            $loans = Loan::with(['member.user', 'books'])
                ->where(function ($q) {
                    $q->where('denda', '>', 0)->orWhere('status', Loan::STATUS_TERLAMBAT);
                })
                ->oldest('tanggal_pinjam')
                ->get();
        } else {
            $populer = Book::query()
                ->with('kategori')
                ->withCount(['loanItems as total_pinjam' => fn ($q) => $q->whereHas('loan', fn ($l) => $l
                    ->whereNotIn('status', [Loan::STATUS_DITOLAK, Loan::STATUS_DIBATALKAN])
                    ->whereDate('tanggal_pinjam', '>=', $dari)
                    ->whereDate('tanggal_pinjam', '<=', $sampai))])
                ->having('total_pinjam', '>', 0)
                ->orderByDesc('total_pinjam')
                ->limit(20)
                ->get();
        }

        $pdf = Pdf::loadView('reports.laporan', [
            'judul' => $judul,
            'jenis' => $jenis,
            'loans' => $loans,
            'populer' => $populer,
            'dari' => $dari,
            'sampai' => $sampai,
            'sekolah' => Setting::get('nama_sekolah', 'SMK Budi Bakti Ciwidey'),
            'petugas' => $request->user(),
            'dicetak' => now()->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', $jenis === 'populer' ? 'portrait' : 'landscape');

        return $pdf->download('laporan-'.str_replace(' ', '-', strtolower($judul)).'-'.$dari.'-'.$sampai.'.pdf');
    }

    /** @return array{0: string, 1: string} */
    private function periode(Request $request): array
    {
        $dari = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->toDateString());

        try {
            $dari = Carbon::parse($dari)->toDateString();
            $sampai = Carbon::parse($sampai)->toDateString();
        } catch (\Throwable) {
            $dari = now()->startOfMonth()->toDateString();
            $sampai = now()->toDateString();
        }

        return [$dari, $sampai];
    }
}
