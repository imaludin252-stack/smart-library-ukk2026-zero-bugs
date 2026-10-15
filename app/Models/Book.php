<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    public const JURUSAN = ['Umum', 'RPL', 'TKJ', 'AKL', 'OTKP'];

    protected $fillable = [
        'kode_buku',
        'judul',
        'kategori_id',
        'penerbit',
        'tahun',
        'isbn',
        'tebal_halaman',
        'bahasa',
        'deskripsi',
        'cover_path',
        'nomor_panggil',
        'jurusan',
        'lokasi_rak',
        'stok',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'stok' => 'integer',
            'tahun' => 'integer',
            'tebal_halaman' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_authors');
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    /** Pinjaman yang sedang menahan eksemplar buku ini (diajukan, dipinjam, terlambat). */
    public function scopeSedangDipinjam(Builder $query): Builder
    {
        return $query->whereHas('loanItems', function (Builder $item) {
            $item->whereHas('loan', function (Builder $loan) {
                $loan->whereIn('status', Loan::STATUS_AKTIF);
            });
        });
    }

    public function jumlahDipinjam(): int
    {
        return $this->loanItems()
            ->whereHas('loan', fn (Builder $loan) => $loan->whereIn('status', Loan::STATUS_AKTIF))
            ->count();
    }

    public function dipinjamCount(): int
    {
        if (array_key_exists('dipinjam_count', $this->attributes)) {
            return (int) $this->attributes['dipinjam_count'];
        }

        return $this->jumlahDipinjam();
    }

    public function tersedia(): int
    {
        return max(0, (int) $this->stok - $this->dipinjamCount());
    }

    public function isTersedia(): bool
    {
        return $this->status === self::STATUS_AKTIF && $this->tersedia() > 0;
    }

    public function isNonaktif(): bool
    {
        return $this->status === self::STATUS_NONAKTIF;
    }

    public function punyaTransaksiAktif(): bool
    {
        return $this->jumlahDipinjam() > 0;
    }

    public function statusLabel(): string
    {
        if ($this->isNonaktif()) {
            return 'Nonaktif';
        }

        return $this->tersedia() > 0 ? 'Tersedia' : 'Sedang Dipinjam';
    }

    public function penulisLabel(): string
    {
        if ($this->authors->isEmpty()) {
            return '-';
        }

        return $this->authors->pluck('nama')->implode(', ');
    }

    public function judulInisial(): string
    {
        $kata = preg_split('/\s+/', trim($this->judul));
        $inisial = '';
        foreach (array_slice($kata, 0, 3) as $k) {
            $inisial .= strtoupper(substr($k, 0, 1));
        }

        return $inisial ?: 'BK';
    }
}
