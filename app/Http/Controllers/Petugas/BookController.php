<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'kategori' => $request->query('kategori'),
            'jurusan' => $request->query('jurusan'),
            'status' => $request->query('status'),
        ];

        $query = Book::query()->with(['kategori', 'authors'])
            ->withCount(['loanItems as dipinjam_count' => fn ($q) => $q->whereHas('loan', fn ($l) => $l->whereIn('status', Loan::STATUS_AKTIF))]);

        if ($filters['q'] !== '') {
            $keyword = $filters['q'];
            $query->where(function ($q) use ($keyword) {
                $q->where('judul', 'like', "%{$keyword}%")
                    ->orWhere('kode_buku', 'like', "%{$keyword}%")
                    ->orWhere('isbn', 'like', "%{$keyword}%")
                    ->orWhereHas('authors', fn ($a) => $a->where('nama', 'like', "%{$keyword}%"));
            });
        }

        if ($filters['kategori']) {
            $query->where('kategori_id', $filters['kategori']);
        }

        if ($filters['jurusan']) {
            $query->where('jurusan', $filters['jurusan']);
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        $books = $query->latest()->paginate(12)->withQueryString();

        $semua = Book::withCount(['loanItems as dipinjam_count' => fn ($q) => $q->whereHas('loan', fn ($l) => $l->whereIn('status', Loan::STATUS_AKTIF))])->get();

        return view('petugas.koleksi.index', [
            'books' => $books,
            'filters' => $filters,
            'categories' => Category::orderBy('nama')->get(),
            'jurusanList' => Book::JURUSAN,
            'totalJudul' => $semua->count(),
            'totalEksemplar' => (int) $semua->sum('stok'),
            'totalTersedia' => (int) $semua->sum(fn (Book $b) => $b->tersedia()),
            'totalDipinjam' => (int) $semua->sum(fn (Book $b) => $b->dipinjamCount()),
        ]);
    }

    public function create(): View
    {
        return view('petugas.koleksi.form', [
            'book' => null,
            'categories' => Category::orderBy('nama')->get(),
            'jurusanList' => Book::JURUSAN,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($data, $request) {
            $book = Book::create($this->atribut($data));
            $this->sinkronPenulis($book, $request->input('penulis'));
        });

        return redirect()->route('petugas.koleksi.index')
            ->with('success', 'Buku "'.$data['judul'].'" berhasil ditambahkan ke katalog.');
    }

    public function edit(Book $book): View
    {
        $book->load('authors');

        return view('petugas.koleksi.form', [
            'book' => $book,
            'categories' => Category::orderBy('nama')->get(),
            'jurusanList' => Book::JURUSAN,
        ]);
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $data = $this->validasi($request, $book);

        DB::transaction(function () use ($book, $data, $request) {
            $book->update($this->atribut($data));
            $this->sinkronPenulis($book, $request->input('penulis'));
        });

        return redirect()->route('petugas.koleksi.index')
            ->with('success', 'Data buku "'.$book->judul.'" berhasil diperbarui.');
    }

    public function toggleStatus(Book $book): RedirectResponse
    {
        $book->update([
            'status' => $book->isNonaktif() ? Book::STATUS_AKTIF : Book::STATUS_NONAKTIF,
        ]);

        return back()->with('success', 'Status buku "'.$book->judul.'" diubah menjadi '.$book->fresh()->statusLabel().'.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        // BR-08: buku dengan transaksi aktif tidak boleh dihapus.
        if ($book->punyaTransaksiAktif()) {
            return back()->with('error', 'Buku "'.$book->judul.'" masih memiliki transaksi aktif. Nonaktifkan buku sebagai gantinya.');
        }

        $judul = $book->judul;
        $book->delete();

        return back()->with('success', 'Buku "'.$judul.'" berhasil dihapus dari katalog.');
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Book $book = null): array
    {
        return $request->validate([
            'kode_buku' => ['required', 'string', 'max:30', Rule::unique('books', 'kode_buku')->ignore($book?->id)],
            'judul' => ['required', 'string', 'max:255'],
            'kategori_id' => ['required', 'exists:categories,id'],
            'penulis' => ['nullable', 'string', 'max:255'],
            'penerbit' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'between:1900,'.(date('Y') + 1)],
            'isbn' => ['nullable', 'string', 'max:30'],
            'tebal_halaman' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'bahasa' => ['nullable', 'string', 'max:30'],
            'deskripsi' => ['nullable', 'string'],
            'nomor_panggil' => ['nullable', 'string', 'max:50'],
            'jurusan' => ['required', Rule::in(Book::JURUSAN)],
            'lokasi_rak' => ['nullable', 'string', 'max:50'],
            'stok' => ['required', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ], [], [
            'kode_buku' => 'kode buku',
            'kategori_id' => 'kategori',
            'jurusan' => 'program keahlian',
            'stok' => 'stok',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function atribut(array $data): array
    {
        return collect($data)->except('penulis')->map(fn ($value) => $value === '' ? null : $value)->all();
    }

    private function sinkronPenulis(Book $book, ?string $penulis): void
    {
        $nama = collect(explode(',', (string) $penulis))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        $ids = $nama->map(fn ($n) => Author::firstOrCreate(['nama' => $n])->id);

        $book->authors()->sync($ids);
    }
}
