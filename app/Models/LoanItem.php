<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends Model
{
    protected $primaryKey = 'id_item';

    protected $fillable = [
        'id_peminjaman',
        'id_buku',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'id_buku', 'id_buku');
    }
}
