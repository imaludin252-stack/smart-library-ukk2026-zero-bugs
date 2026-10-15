@props(['book', 'class' => 'aspect-[2/3] w-full'])

@if ($book->cover_path && file_exists(public_path($book->cover_path)))
    <img src="{{ asset($book->cover_path) }}" alt="Cover {{ $book->judul }}"
        class="{{ $class }} rounded-xl object-cover" />
@else
    <div class="{{ $class }} cover-placeholder rounded-xl px-2">
        <div class="flex flex-col items-center gap-1">
            <x-icon name="book-open" class="h-7 w-7 opacity-70" />
            <span class="line-clamp-3">{{ $book->judulInisial() }}</span>
        </div>
    </div>
@endif
