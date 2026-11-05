<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    protected $primaryKey = 'id_penulis';

    protected $fillable = [
        'nama',
    ];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_authors', 'id_penulis', 'id_buku', 'id_penulis', 'id_buku');
    }
}
