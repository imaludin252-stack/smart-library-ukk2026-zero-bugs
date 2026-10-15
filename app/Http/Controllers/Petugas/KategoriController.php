<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('petugas.master.index', [
            'kategori' => Category::withCount('books')
                ->when($q !== '', fn ($query) => $query->where('nama', 'like', "%{$q}%"))
                ->orderBy('nama')->get(),
            'penulis' => Author::withCount('books')->orderBy('nama')->get(),
            'q' => $q,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:categories,nama'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ], [], ['nama' => 'nama kategori']);

        Category::create($data);

        return back()->with('success', 'Kategori "'.$data['nama'].'" berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('categories', 'nama')->ignore($category->id)],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ], [], ['nama' => 'nama kategori']);

        $category->update($data);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->books()->exists()) {
            return back()->with('error', 'Kategori "'.$category->nama.'" masih dipakai oleh '.$category->books()->count().' buku. Pindahkan buku terlebih dahulu.');
        }

        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
