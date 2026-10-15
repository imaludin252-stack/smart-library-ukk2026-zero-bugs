<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Loan extends Model
{
    public const STATUS_DIAJUKAN = 'diajukan';
    public const STATUS_DIPINJAM = 'dipinjam';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';
    public const STATUS_TERLAMBAT = 'terlambat';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    /** Status yang masih menahan stok buku (BR-01). */
    public const STATUS_AKTIF = [self::STATUS_DIAJUKAN, self::STATUS_DIPINJAM, self::STATUS_TERLAMBAT];

    public const STATUS_LABEL = [
        self::STATUS_DIAJUKAN => 'Menunggu Verifikasi',
        self::STATUS_DIPINJAM => 'Sedang Dipinjam',
        self::STATUS_DIKEMBALIKAN => 'Dikembalikan',
        self::STATUS_TERLAMBAT => 'Terlambat',
        self::STATUS_DITOLAK => 'Ditolak',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    protected $fillable = [
        'kode_transaksi',
        'member_id',
        'petugas_id',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'tanggal_kembali',
        'status',
        'denda',
        'kondisi_buku',
        'sudah_diperpanjang',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pinjam' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'tanggal_kembali' => 'date',
            'denda' => 'integer',
            'sudah_diperpanjang' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'loan_items');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', self::STATUS_AKTIF);
    }

    public function scopeUntukMember(Builder $query, Member $member): Builder
    {
        return $query->where('member_id', $member->id);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABEL[$this->status] ?? ucfirst($this->status);
    }

    public function isMenunggu(): bool
    {
        return $this->status === self::STATUS_DIAJUKAN;
    }

    public function isDipinjam(): bool
    {
        return $this->status === self::STATUS_DIPINJAM;
    }

    public function isTerlambat(): bool
    {
        return $this->status === self::STATUS_TERLAMBAT;
    }

    public function isAktif(): bool
    {
        return in_array($this->status, self::STATUS_AKTIF, true);
    }

    public function isSelesai(): bool
    {
        return $this->tanggal_kembali !== null;
    }

    public function isDapatDiproses(): bool
    {
        return in_array($this->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPINJAM, self::STATUS_TERLAMBAT], true);
    }

    public function sisaHari(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->tanggal_jatuh_tempo->startOfDay(), false);
    }

    public function hariTerlambat(): int
    {
        if (! $this->isAktif() || ! $this->tanggal_jatuh_tempo->lt(now()->startOfDay())) {
            return 0;
        }

        return (int) $this->tanggal_jatuh_tempo->startOfDay()->diffInDays(now()->startOfDay());
    }

    public function hitungDenda(?int $tarif = null): int
    {
        $tarif ??= (int) Setting::get('tarif_denda', 1000);

        return $this->hariTerlambat() * $tarif;
    }

    public function kemajuanWaktu(): float
    {
        $total = $this->tanggal_pinjam->startOfDay()->diffInDays($this->tanggal_jatuh_tempo->startOfDay());
        if ($total <= 0) {
            return 100;
        }

        $lewat = $this->tanggal_pinjam->startOfDay()->diffInDays(now()->startOfDay());

        return min(100, max(0, ($lewat / $total) * 100));
    }

    public function judulBukuPertama(): string
    {
        return $this->books->first()?->judul ?? '-';
    }

    /** Tandai pinjaman aktif yang melewati jatuh tempo menjadi Terlambat (BR-05). */
    public static function segarkanKeterlambatan(?int $memberId = null): void
    {
        $query = self::query()
            ->where('status', self::STATUS_DIPINJAM)
            ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString());

        if ($memberId !== null) {
            $query->where('member_id', $memberId);
        }

        $query->update(['status' => self::STATUS_TERLAMBAT]);
    }

    public static function generateKodeTransaksi(): string
    {
        $prefix = 'TRX-'.now()->format('Y-m-d').'-';
        $terakhir = self::where('kode_transaksi', 'like', $prefix.'%')
            ->orderByDesc('kode_transaksi')
            ->value('kode_transaksi');

        $urut = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }

    /** Ajukan peminjaman baru oleh siswa (dibungkus transaction — NFR-005). */
    public static function ajukan(Member $member, Book $book): self
    {
        return DB::transaction(function () use ($member, $book) {
            $durasi = (int) Setting::get('durasi_pinjam', 7);

            $loan = self::create([
                'kode_transaksi' => self::generateKodeTransaksi(),
                'member_id' => $member->id,
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays($durasi)->toDateString(),
                'status' => self::STATUS_DIAJUKAN,
            ]);

            $loan->books()->attach($book->id);

            return $loan;
        });
    }

    /** Verifikasi & serahkan buku oleh petugas. */
    public function verifikasi(User $petugas): void
    {
        DB::transaction(function () use ($petugas) {
            $this->update([
                'status' => self::STATUS_DIPINJAM,
                'petugas_id' => $petugas->id,
            ]);
        });
    }

    public function tolak(User $petugas, ?string $alasan = null): void
    {
        DB::transaction(function () use ($petugas, $alasan) {
            $this->update([
                'status' => self::STATUS_DITOLAK,
                'petugas_id' => $petugas->id,
                'catatan' => $alasan,
            ]);
        });
    }

    public function batalkan(): void
    {
        $this->update(['status' => self::STATUS_DIBATALKAN]);
    }

    /** Proses pengembalian oleh petugas (BR-07). */
    public function kembalikan(User $petugas, int $denda, string $kondisi = 'baik', ?string $catatan = null): void
    {
        DB::transaction(function () use ($petugas, $denda, $kondisi, $catatan) {
            $terlambat = now()->startOfDay()->gt($this->tanggal_jatuh_tempo->startOfDay());

            $this->update([
                'status' => $terlambat ? self::STATUS_TERLAMBAT : self::STATUS_DIKEMBALIKAN,
                'tanggal_kembali' => now()->toDateString(),
                'petugas_id' => $petugas->id,
                'denda' => max(0, $denda),
                'kondisi_buku' => $kondisi,
                'catatan' => $catatan,
            ]);
        });
    }

    public function perpanjang(): void
    {
        $durasi = (int) Setting::get('durasi_pinjam', 7);

        $this->update([
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo->addDays($durasi)->toDateString(),
            'sudah_diperpanjang' => true,
            'status' => self::STATUS_DIPINJAM,
        ]);
    }
}
