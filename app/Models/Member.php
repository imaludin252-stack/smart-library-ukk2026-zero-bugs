<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_anggota';

    protected $fillable = [
        'id_user',
        'nisn',
        'kelas',
        'jurusan',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'id_anggota', 'id_anggota');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function getNamaAttribute(): string
    {
        return $this->user?->name ?? '-';
    }

    public function pinjamanAktif(): int
    {
        return $this->loans()->whereIn('status', Loan::STATUS_AKTIF)->count();
    }

    public function punyaKeterlambatan(): bool
    {
        return $this->loans()->where(function ($query) {
            $query->where('status', Loan::STATUS_TERLAMBAT)
                ->orWhere(function ($query) {
                    $query->where('status', Loan::STATUS_DIPINJAM)
                        ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString());
                });
        })->exists();
    }
}
