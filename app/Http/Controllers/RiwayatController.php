<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Member;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RiwayatController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Member $member */
        $member = $request->user()->member;
        abort_unless($member, 403);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'dari' => $request->query('dari'),
            'sampai' => $request->query('sampai'),
        ];

        $query = Loan::untukMember($member)
            ->with(['books', 'petugas'])
            ->whereNotNull('tanggal_kembali')
            ->latest('tanggal_kembali');

        if ($filters['q'] !== '') {
            $query->whereHas('books', fn ($q) => $q->where('judul', 'like', '%'.$filters['q'].'%'));
        }

        if ($filters['status'] === 'tepat_waktu') {
            $query->where('status', Loan::STATUS_DIKEMBALIKAN)->where('denda', 0);
        } elseif ($filters['status'] === 'terlambat') {
            $query->where(fn ($q) => $q->where('status', Loan::STATUS_TERLAMBAT)->orWhere('denda', '>', 0));
        }

        if ($filters['dari']) {
            $query->whereDate('tanggal_kembali', '>=', $filters['dari']);
        }

        if ($filters['sampai']) {
            $query->whereDate('tanggal_kembali', '<=', $filters['sampai']);
        }

        $loans = $query->paginate(10)->withQueryString();

        $semua = Loan::untukMember($member)->whereNotNull('tanggal_kembali');

        $totalPinjam = (clone $semua)->count();
        $tepatWaktu = (clone $semua)->where('denda', 0)->count();
        $terlambat = (clone $semua)->where('denda', '>', 0)->count();
        $totalDenda = (int) (clone $semua)->sum('denda');

        $bebasPustaka = ! $member->punyaKeterlambatan()
            && Loan::untukMember($member)->whereIn('status', Loan::STATUS_AKTIF)->where('status', '!=', Loan::STATUS_DIAJUKAN)->count() === 0;

        return view('siswa.riwayat', [
            'member' => $member,
            'loans' => $loans,
            'filters' => $filters,
            'totalPinjam' => $totalPinjam,
            'tepatWaktu' => $tepatWaktu,
            'terlambat' => $terlambat,
            'totalDenda' => $totalDenda,
            'bebasPustaka' => $bebasPustaka,
        ]);
    }

    public function bebasPustaka(Request $request): Response
    {
        /** @var Member $member */
        $member = $request->user()->member;
        abort_unless($member, 403);

        $semua = Loan::untukMember($member)->whereNotNull('tanggal_kembali');

        $pdf = Pdf::loadView('reports.bebas-pustaka', [
            'member' => $member,
            'user' => $request->user(),
            'totalPinjam' => (clone $semua)->count(),
            'totalDenda' => (int) (clone $semua)->sum('denda'),
            'sekolah' => Setting::get('nama_sekolah', 'SMK Budi Bakti Ciwidey'),
        ])->setPaper('a4');

        return $pdf->download('surat-bebas-pustaka-'.$member->nisn.'.pdf');
    }
}
