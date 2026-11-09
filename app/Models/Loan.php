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

    public const STATUS_DENDA_BELUM_LUNAS = 'belum_lunas';

    public const STATUS_DENDA_LUNAS = 'lunas';

    public const STATUS_DENDA_LABEL = [
        self::STATUS_DENDA_BELUM_LUNAS => 'Belum Lunas',
        self::STATUS_DENDA_LUNAS => 'Lunas',
    ];

    protected $primaryKey = 'id_peminjaman';

    protected $fillable = [
        'kode_transaksi',
        'id_anggota',
        'id_user',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'tanggal_kembali',
        'status',
        'denda',
        'status_denda',
        'tanggal_bayar_denda',
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
            'tanggal_bayar_denda' => 'date',
            'sudah_diperpanjang' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'id_anggota', 'id_anggota');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'loan_items', 'id_peminjaman', 'id_buku', 'id_peminjaman', 'id_buku');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', self::STATUS_AKTIF);
    }

    public function scopeUntukMember(Builder $query, Member $member): Builder
    {
        return $query->where('id_anggota', $member->id_anggota);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABEL[$this->status] ?? ucfirst($this->status);
    }

    public function statusDendaLabel(): string
    {
        return self::STATUS_DENDA_LABEL[$this->status_denda] ?? 'Belum Lunas';
    }

    public function isDendaLunas(): bool
    {
        return $this->status_denda === self::STATUS_DENDA_LUNAS;
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
            $query->where('id_anggota', $memberId);
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
                'id_anggota' => $member->id_anggota,
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays($durasi)->toDateString(),
                'status' => self::STATUS_DIAJUKAN,
            ]);

            $loan->books()->attach($book->id_buku);

            return $loan;
        });
    }

    /** Verifikasi & serahkan buku oleh petugas. */
    public function verifikasi(User $petugas): void
    {
        DB::transaction(function () use ($petugas) {
            $this->update([
                'status' => self::STATUS_DIPINJAM,
                'id_user' => $petugas->id_user,
            ]);
        });
    }

    public function tolak(User $petugas, ?string $alasan = null): void
    {
        DB::transaction(function () use ($petugas, $alasan) {
            $this->update([
                'status' => self::STATUS_DITOLAK,
                'id_user' => $petugas->id_user,
                'catatan' => $alasan,
            ]);
        });
    }

    public function batalkan(): void
    {
        $this->update(['status' => self::STATUS_DIBATALKAN]);
    }

    /**
     * Proses pengembalian oleh petugas (BR-07).
     *
     * Status selalu menjadi "dikembalikan" agar transaksi keluar dari daftar
     * pinjaman aktif. Keterlambatan tetap tercatat melalui nominal denda.
     */
    public function kembalikan(User $petugas, int $denda, string $kondisi = 'baik', ?string $catatan = null): void
    {
        DB::transaction(function () use ($petugas, $denda, $kondisi, $catatan) {
            $this->update([
                'status' => self::STATUS_DIKEMBALIKAN,
                'tanggal_kembali' => now()->toDateString(),
                'id_user' => $petugas->id_user,
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
