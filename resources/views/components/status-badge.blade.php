@props(['status', 'label' => null])

@php
    $map = [
        'diajukan' => ['badge-warning', 'Menunggu Verifikasi'],
        'dipinjam' => ['badge-info', 'Sedang Dipinjam'],
        'dikembalikan' => ['badge-success', 'Dikembalikan'],
        'terlambat' => ['badge-danger', 'Terlambat'],
        'ditolak' => ['badge-danger', 'Ditolak'],
        'dibatalkan' => ['badge-neutral', 'Dibatalkan'],
        'tersedia' => ['badge-success', 'Tersedia'],
        'habis' => ['badge-neutral', 'Dipinjam Semua'],
        'nonaktif' => ['badge-neutral', 'Nonaktif'],
        'aktif' => ['badge-success', 'Aktif'],
        'mendekati' => ['badge-warning', 'Mendekati Tempo'],
    ];

    [$class, $default] = $map[$status] ?? ['badge-neutral', ucfirst((string) $status)];
@endphp

<span {{ $attributes->merge(['class' => 'badge badge-dot '.$class]) }}>
    {{ $label ?? $default }}
</span>
