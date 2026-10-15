<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenulisController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:authors,nama'],
        ], [], ['nama' => 'nama penulis']);

        Author::create($data);

        return back()->with('success', 'Penulis "'.$data['nama'].'" berhasil ditambahkan.');
    }

    public function update(Request $request, Author $author): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('authors', 'nama')->ignore($author->id)],
        ], [], ['nama' => 'nama penulis']);

        $author->update($data);

        return back()->with('success', 'Data penulis berhasil diperbarui.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        if ($author->books()->exists()) {
            return back()->with('error', 'Penulis "'.$author->nama.'" masih terhubung dengan '.$author->books()->count().' buku.');
        }

        $author->delete();

        return back()->with('success', 'Penulis berhasil dihapus.');
    }
}
