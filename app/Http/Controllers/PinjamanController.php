<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PinjamanController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Member $member */
        $member = $request->user()->member;
        abort_unless($member, 403);

        Loan::segarkanKeterlambatan($member->id);

        $tab = $request->query('tab', 'semua');
        $batas = (int) Setting::get('batas_pinjam_aktif', 3);

        $query = Loan::untukMember($member)->with(['books.authors', 'books.kategori'])->latest();

        match ($tab) {
            'aktif' => $query->whereIn('status', [Loan::STATUS_DIPINJAM, Loan::STATUS_TERLAMBAT]),
            'verifikasi' => $query->where('status', Loan::STATUS_DIAJUKAN),
            'selesai' => $query->whereNotNull('tanggal_kembali'),
            'terlambat' => $query->where('status', Loan::STATUS_TERLAMBAT),
            default => $query->where(function ($q) {
                $q->whereIn('status', Loan::STATUS_AKTIF)->orWhereNotNull('tanggal_kembali');
            }),
        };

        $loans = $query->paginate(8)->withQueryString();

        $semua = Loan::untukMember($member);

        return view('siswa.pinjaman', [
            'member' => $member,
            'loans' => $loans,
            'tab' => $tab,
            'batas' => $batas,
            'jumlahAktif' => (clone $semua)->whereIn('status', [Loan::STATUS_DIPINJAM, Loan::STATUS_TERLAMBAT])->count(),
            'jumlahVerifikasi' => (clone $semua)->where('status', Loan::STATUS_DIAJUKAN)->count(),
            'jumlahTerlambat' => (clone $semua)->where('status', Loan::STATUS_TERLAMBAT)->count(),
            'jumlahSelesai' => (clone $semua)->whereNotNull('tanggal_kembali')->count(),
            'totalDenda' => (int) (clone $semua)->sum('denda'),
            'kuota' => $semua->whereIn('status', Loan::STATUS_AKTIF)->count(),
            'dapatPinjam' => ! $member->punyaKeterlambatan(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Member $member */
        $member = $request->user()->member;
        abort_unless($member, 403);

        $data = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
        ]);

        $book = Book::findOrFail($data['book_id']);

        if ($book->isNonaktif()) {
            return back()->with('error', 'Buku ini sedang dinonaktifkan dan tidak dapat dipinjam.');
        }

        if (! $member->user->isAktif() || $member->status !== 'aktif') {
            return back()->with('error', 'Akun anggota Anda tidak aktif. Hubungi petugas perpustakaan.');
        }

        // BR-09: anggota dengan keterlambatan tidak dapat mengajukan pinjaman baru.
        if ($member->punyaKeterlambatan()) {
            return back()->with('error', 'Anda memiliki peminjaman terlambat. Selesaikan terlebih dahulu sebelum meminjam buku baru.');
        }

        // BR-01: buku harus tersedia.
        if (! $book->isTersedia()) {
            return back()->with('error', 'Stok buku "'.$book->judul.'" sedang tidak tersedia.');
        }

        // BR-04: batas maksimal peminjaman aktif.
        $batas = (int) Setting::get('batas_pinjam_aktif', 3);
        if ($member->pinjamanAktif() >= $batas) {
            return back()->with('error', 'Batas maksimal peminjaman aktif ('.$batas.' buku) telah tercapai. Kembalikan buku terlebih dahulu.');
        }

        // Tidak boleh mengajukan buku yang sama dua kali.
        $sudahAda = Loan::untukMember($member)
            ->whereIn('status', Loan::STATUS_AKTIF)
            ->whereHas('books', fn ($q) => $q->where('books.id', $book->id))
            ->exists();

        if ($sudahAda) {
            return back()->with('error', 'Anda sudah memiliki pengajuan/pinjaman aktif untuk buku ini.');
        }

        $loan = Loan::ajukan($member, $book);

        return redirect()->route('pinjaman.index')
            ->with('success', 'Pengajuan peminjaman "'.$book->judul.'" berhasil dibuat ('.$loan->kode_transaksi.'). Silakan verifikasi di meja sirkulasi.');
    }

    public function batalkan(Request $request, Loan $loan): RedirectResponse
    {
        $this->autoritaskan($request, $loan);

        if (! $loan->isMenunggu()) {
            return back()->with('error', 'Hanya permohonan yang menunggu verifikasi dapat dibatalkan.');
        }

        $loan->batalkan();

        return back()->with('success', 'Permohonan peminjaman dibatalkan.');
    }

    public function perpanjang(Request $request, Loan $loan): RedirectResponse
    {
        $this->autoritaskan($request, $loan);

        if (! $loan->isDipinjam()) {
            return back()->with('error', 'Pinjaman ini tidak dapat diperpanjang.');
        }

        if ($loan->sudah_diperpanjang) {
            return back()->with('error', 'Pinjaman ini sudah pernah diperpanjang.');
        }

        if ($loan->isTerlambat()) {
            return back()->with('error', 'Pinjaman yang sudah terlambat tidak dapat diperpanjang.');
        }

        $loan->perpanjang();

        return back()->with('success', 'Pinjaman diperpanjang hingga '.$loan->fresh()->tanggal_jatuh_tempo->translatedFormat('d F Y').'.');
    }

    private function autoritaskan(Request $request, Loan $loan): void
    {
        $member = $request->user()->member;
        abort_unless($member && $loan->member_id === $member->id, 403, 'Anda tidak berhak mengakses transaksi ini.');
    }
}
