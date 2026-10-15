<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Member $member */
        $member = $request->user()->member;

        abort_unless($member, 403, 'Akun Anda tidak terhubung dengan data anggota.');

        Loan::segarkanKeterlambatan($member->id);

        $pinjamanAktif = Loan::untukMember($member)
            ->with(['books.authors', 'books.kategori'])
            ->whereIn('status', Loan::STATUS_AKTIF)
            ->orderBy('tanggal_jatuh_tempo')
            ->get();

        $jatuhTempoTerdekat = $pinjamanAktif
            ->filter(fn (Loan $loan) => $loan->status !== Loan::STATUS_DIAJUKAN)
            ->sortBy('tanggal_jatuh_tempo')
            ->first();

        $riwayatSelesai = Loan::untukMember($member)->whereNotNull('tanggal_kembali')->count();

        $totalDenda = (int) Loan::untukMember($member)->sum('denda');

        $batasPinjam = (int) Setting::get('batas_pinjam_aktif', 3);

        $dipinjamIds = $pinjamanAktif->pluck('id');

        $populer = Book::query()
            ->with(['authors', 'kategori'])
            ->where('status', Book::STATUS_AKTIF)
            ->whereHas('loanItems', fn ($q) => $q->whereHas('loan', fn ($l) => $l->whereNotIn('status', [Loan::STATUS_DITOLAK, Loan::STATUS_DIBATALKAN])))
            ->withCount(['loanItems as total_pinjam' => fn ($q) => $q->whereHas('loan', fn ($l) => $l->whereNotIn('status', [Loan::STATUS_DITOLAK, Loan::STATUS_DIBATALKAN]))])
            ->withCount(['loanItems as dipinjam_count' => fn ($q) => $q->whereHas('loan', fn ($l) => $l->whereIn('status', Loan::STATUS_AKTIF))])
            ->orderByDesc('total_pinjam')
            ->limit(8)
            ->get();

        return view('siswa.beranda', [
            'member' => $member,
            'pinjamanAktif' => $pinjamanAktif,
            'jatuhTempoTerdekat' => $jatuhTempoTerdekat,
            'riwayatSelesai' => $riwayatSelesai,
            'totalDenda' => $totalDenda,
            'batasPinjam' => $batasPinjam,
            'populer' => $populer,
            'dipinjamIds' => $dipinjamIds,
        ]);
    }
}
