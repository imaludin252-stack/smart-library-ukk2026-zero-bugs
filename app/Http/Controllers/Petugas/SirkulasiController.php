<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SirkulasiController extends Controller
{
    public function index(Request $request): View
    {
        Loan::segarkanKeterlambatan();

        $tab = $request->query('tab', 'aktif');

        $permohonan = Loan::with(['member.user', 'books'])
            ->where('status', Loan::STATUS_DIAJUKAN)
            ->oldest()
            ->get();

        $aktif = Loan::with(['member.user', 'books'])
            ->whereIn('status', [Loan::STATUS_DIPINJAM, Loan::STATUS_TERLAMBAT])
            ->orderBy('tanggal_jatuh_tempo')
            ->get();

        $selesaiHariIni = Loan::with(['member.user', 'books', 'petugas'])
            ->whereDate('tanggal_kembali', now()->toDateString())
            ->latest('updated_at')
            ->get();

        return view('petugas.sirkulasi', [
            'tab' => $tab,
            'permohonan' => $permohonan,
            'aktif' => $aktif,
            'selesaiHariIni' => $selesaiHariIni,
            'members' => Member::with('user')->aktif()->orderBy('nisn')->get(),
            'books' => Book::with('authors')->where('status', Book::STATUS_AKTIF)->orderBy('judul')->get(),
            'durasi' => (int) Setting::get('durasi_pinjam', 7),
            'tarifDenda' => (int) Setting::get('tarif_denda', 1000),
            'batas' => (int) Setting::get('batas_pinjam_aktif', 3),
        ]);
    }

    /** Peminjaman manual langsung dari meja sirkulasi. */
    public function pinjam(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'book_id' => ['required', 'exists:books,id'],
        ]);

        $member = Member::with('user')->findOrFail($data['member_id']);
        $book = Book::findOrFail($data['book_id']);

        if (! $member->user->isAktif() || $member->status !== 'aktif') {
            return back()->with('error', 'Anggota '.$member->nama.' sedang tidak aktif.');
        }

        if ($member->punyaKeterlambatan()) {
            return back()->with('error', 'Anggota memiliki pinjaman terlambat. Selesaikan terlebih dahulu.');
        }

        if (! $book->isTersedia()) {
            return back()->with('error', 'Stok buku "'.$book->judul.'" tidak tersedia.');
        }

        $batas = (int) Setting::get('batas_pinjam_aktif', 3);
        if ($member->pinjamanAktif() >= $batas) {
            return back()->with('error', 'Anggota telah mencapai batas maksimal '.$batas.' peminjaman aktif.');
        }

        $durasi = (int) Setting::get('durasi_pinjam', 7);

        $loan = Loan::create([
            'kode_transaksi' => Loan::generateKodeTransaksi(),
            'member_id' => $member->id,
            'petugas_id' => $request->user()->id,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays($durasi)->toDateString(),
            'status' => Loan::STATUS_DIPINJAM,
        ]);
        $loan->books()->attach($book->id);

        return redirect()->route('petugas.sirkulasi.index')
            ->with('success', 'Peminjaman '.$loan->kode_transaksi.' untuk '.$member->nama.' berhasil dicatat.');
    }

    public function verifikasi(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->isMenunggu(), 404);

        $loan->verifikasi($request->user());

        return back()->with('success', 'Transaksi '.$loan->kode_transaksi.' diverifikasi. Buku dapat diserahkan.');
    }

    public function tolak(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->isMenunggu(), 404);

        $data = $request->validate([
            'alasan' => ['nullable', 'string', 'max:255'],
        ]);

        $loan->tolak($request->user(), $data['alasan'] ?? null);

        return back()->with('success', 'Permohonan '.$loan->kode_transaksi.' ditolak.');
    }

    public function kembalikan(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->isDapatDiproses() && ! $loan->isMenunggu(), 404, 'Transaksi tidak dalam status dapat dikembalikan.');

        $data = $request->validate([
            'denda' => ['required', 'integer', 'min:0'],
            'kondisi_buku' => ['required', 'in:baik,rusak_ringan,rusak_berat,hilang'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $loan->kembalikan($request->user(), (int) $data['denda'], $data['kondisi_buku'], $data['catatan'] ?? null);

        $terlambat = $data['denda'] > 0;

        return redirect()->route('petugas.sirkulasi.index')
            ->with('success', 'Pengembalian '.$loan->kode_transaksi.' tercatat'.($terlambat ? ' dengan denda Rp '.number_format($data['denda'], 0, ',', '.') : '').'.');
    }
}
