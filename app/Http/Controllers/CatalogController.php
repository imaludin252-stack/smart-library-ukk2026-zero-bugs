<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\LoanItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'kategori' => $request->query('kategori'),
            'jurusan' => $request->query('jurusan'),
            'status' => $request->query('status'),
            'urut' => $request->query('urut', 'terbaru'),
        ];

        $dipinjamSub = LoanItem::query()
            ->selectRaw('count(*)')
            ->whereColumn('loan_items.book_id', 'books.id')
            ->whereHas('loan', fn ($query) => $query->whereIn('status', Loan::STATUS_AKTIF));

        $query = Book::query()
            ->with(['kategori', 'authors'])
            ->where('status', Book::STATUS_AKTIF)
            ->withCount(['loanItems as dipinjam_count' => fn ($q) => $q->whereHas('loan', fn ($l) => $l->whereIn('status', Loan::STATUS_AKTIF))]);

        if ($filters['q'] !== '') {
            $keyword = $filters['q'];
            $query->where(function ($q) use ($keyword) {
                $q->where('judul', 'like', "%{$keyword}%")
                    ->orWhere('kode_buku', 'like', "%{$keyword}%")
                    ->orWhere('isbn', 'like', "%{$keyword}%")
                    ->orWhere('penerbit', 'like', "%{$keyword}%")
                    ->orWhereHas('authors', fn ($a) => $a->where('nama', 'like', "%{$keyword}%"))
                    ->orWhereHas('kategori', fn ($k) => $k->where('nama', 'like', "%{$keyword}%"));
            });
        }

        if ($filters['kategori']) {
            $query->where('kategori_id', $filters['kategori']);
        }

        if ($filters['jurusan']) {
            $query->where('jurusan', $filters['jurusan']);
        }

        if ($filters['status'] === 'tersedia') {
            $query->whereRaw('books.stok > ('.$dipinjamSub->toSql().')', $dipinjamSub->getBindings());
        } elseif ($filters['status'] === 'dipinjam') {
            $query->whereRaw('('.$dipinjamSub->toSql().') > 0', $dipinjamSub->getBindings());
        }

        match ($filters['urut']) {
            'judul_asc' => $query->orderBy('judul'),
            'judul_desc' => $query->orderByDesc('judul'),
            'populer' => $query->withCount(['loanItems as total_pinjam' => fn ($q) => $q])->orderByDesc('total_pinjam'),
            default => $query->latest(),
        };

        $books = $query->paginate(12)->withQueryString();

        $totalKoleksi = Book::where('status', Book::STATUS_AKTIF)->count();
        $totalTersedia = Book::where('status', Book::STATUS_AKTIF)
            ->whereRaw('books.stok > ('.$dipinjamSub->toSql().')', $dipinjamSub->getBindings())
            ->count();
        $totalDipinjam = Book::where('status', Book::STATUS_AKTIF)
            ->whereRaw('('.$dipinjamSub->toSql().') > 0', $dipinjamSub->getBindings())
            ->count();

        return view('catalog.index', [
            'books' => $books,
            'filters' => $filters,
            'categories' => Category::orderBy('nama')->get(),
            'jurusanList' => array_values(array_diff(Book::JURUSAN, ['Umum'])),
            'totalKoleksi' => $totalKoleksi,
            'totalTersedia' => $totalTersedia,
            'totalDipinjam' => $totalDipinjam,
        ]);
    }

    public function show(Book $book): View
    {
        $book->load(['kategori', 'authors'])
            ->loadCount(['loanItems as dipinjam_count' => fn ($q) => $q->whereHas('loan', fn ($l) => $l->whereIn('status', Loan::STATUS_AKTIF))]);

        $member = auth()->user()?->member;

        $pinjamanSaya = null;
        if ($member) {
            $pinjamanSaya = Loan::untukMember($member)
                ->whereIn('status', Loan::STATUS_AKTIF)
                ->whereHas('books', fn ($q) => $q->where('books.id', $book->id))
                ->first();
        }

        $terkait = Book::query()
            ->with(['authors'])
            ->where('kategori_id', $book->kategori_id)
            ->where('id', '!=', $book->id)
            ->where('status', Book::STATUS_AKTIF)
            ->withCount(['loanItems as dipinjam_count' => fn ($q) => $q->whereHas('loan', fn ($l) => $l->whereIn('status', Loan::STATUS_AKTIF))])
            ->limit(4)
            ->get();

        return view('catalog.show', [
            'book' => $book,
            'pinjamanSaya' => $pinjamanSaya,
            'terkait' => $terkait,
            'durasi' => (int) \App\Models\Setting::get('durasi_pinjam', 7),
            'tarifDenda' => (int) \App\Models\Setting::get('tarif_denda', 1000),
            'authorsCount' => Author::count(),
        ]);
    }
}
