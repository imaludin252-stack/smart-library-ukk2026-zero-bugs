<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_kategori';

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'id_kategori', 'id_kategori');
    }
}
