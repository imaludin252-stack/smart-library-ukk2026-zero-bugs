@props(['book'])

@php
    $jurusanColor = [
        'RPL' => 'bg-[var(--color-jurusan-rpl)]',
        'TKJ' => 'bg-[var(--color-jurusan-tkj)]',
        'AKL' => 'bg-[var(--color-jurusan-akl)]',
        'OTKP' => 'bg-[var(--color-jurusan-otkp)]',
        'Umum' => 'bg-[var(--color-ink-muted)]',
    ][$book->jurusan] ?? 'bg-[var(--color-ink-muted)]';

    $tersedia = $book->isTersedia();
@endphp

<article {{ $attributes->merge(['class' => 'card card-hover flex flex-col overflow-hidden']) }}>
    <div class="relative p-3">
        <x-cover :book="$book" />
        <span class="absolute left-5 top-5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white {{ $jurusanColor }}">
            {{ $book->jurusan }}
        </span>
        <span class="absolute right-5 top-5">
            <x-status-badge :status="$tersedia ? 'tersedia' : ($book->isNonaktif() ? 'nonaktif' : 'habis')"
                :label="$tersedia ? 'Tersedia: '.$book->tersedia() : ($book->isNonaktif() ? 'Nonaktif' : 'Dipinjam Semua')" />
        </span>
    </div>
    <div class="flex flex-1 flex-col gap-2 px-4 pb-4">
        <h3 class="line-clamp-2 text-[15px] font-bold leading-5 text-[var(--color-ink-strong)]">{{ $book->judul }}</h3>
        <p class="line-clamp-1 text-[12px] font-medium text-[var(--color-ink-muted)]">{{ $book->penulisLabel() }}</p>
        <p class="flex items-center gap-1 text-[11px] font-semibold text-[var(--color-ink-muted)]">
            <x-icon name="map-pin" class="h-3.5 w-3.5" />
            {{ $book->lokasi_rak ?? 'Lokasi belum diatur' }}
        </p>
        <a href="{{ route('catalog.show', $book->kode_buku) }}" class="btn btn-primary btn-sm mt-auto w-full">
            <x-icon name="book-open" class="h-4 w-4" />
            Detail &amp; Pinjam
        </a>
    </div>
</article>
