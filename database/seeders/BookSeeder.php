<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = collect([
            'Pemrograman' => 'Koleksi buku pemrograman dan pengembangan perangkat lunak.',
            'Jaringan Komputer' => 'Koleksi administrasi dan teknologi jaringan komputer.',
            'Akuntansi' => 'Koleksi dasar dan praktikum akuntansi keuangan.',
            'Manajemen Perkantoran' => 'Koleksi tata kelola perkantoran dan layanan bisnis.',
            'Bahasa & Sastra' => 'Koleksi bahasa Indonesia, bahasa Inggris, dan sastra.',
            'Matematika' => 'Koleksi matematika umum dan terapan SMK.',
            'Sejarah & Kewarganegaraan' => 'Koleksi pendidikan kewarganegaraan dan sejarah.',
        ])->mapWithKeys(function ($deskripsi, $nama) {
            return [$nama => Category::create(['nama' => $nama, 'deskripsi' => $deskripsi])];
        });

        $penulis = collect([
            'Andi Widya Nugroho', 'Yudi Utomo', 'Rina Kartika', 'Ferdian Saputra',
            'Agus Setiawan', 'Dwi Hariyanto', 'Siti Aminah', 'Endang Susilowati',
            'Nurul Hidayah', 'Darmawan', 'Sarah Wijaya', 'Bambang Suryono',
            'Hartono', 'Kurniawan', 'Bayu Prasetyo',
        ])->mapWithKeys(fn ($nama) => [$nama => Author::create(['nama' => $nama])]);

        $buku = [
            ['RPL-PWD-001', 'Pemrograman Web Dasar', 'Pemrograman', ['Andi Widya Nugroho'], 'Andi Media', 2023, '978-602-1234-01-1', 'RPL', 'Lantai 1 – Rak RPL Blok B Nomor 04', 5, '005.133 WID p', 'Membahas fondasi pengembangan web modern: HTML5, CSS3, dan JavaScript sesuai Kurikulum Merdeka. Dilengkapi studi kasus proyek nyata di perpustakaan sekolah.'],
            ['RPL-PBD-002', 'Pemrograman Berbasis Objek', 'Pemrograman', ['Yudi Utomo'], 'Informatika', 2022, '978-602-1234-02-8', 'RPL', 'Lantai 1 – Rak RPL Blok B Nomor 05', 4, '005.136 YUD p', 'Konsep OOP dengan bahasa Java dan PHP: class, object, inheritance, polymorphism, hingga studi kasus aplikasi perpustakaan.'],
            ['RPL-PAM-003', 'Pemrograman Aplikasi Mobile', 'Pemrograman', ['Rina Kartika'], 'Andi Media', 2024, '978-602-1234-03-5', 'RPL', 'Lantai 1 – Rak RPL Blok B Nomor 06', 3, '005.137 RIN p', 'Pengembangan aplikasi Android dan iOS lintas platform menggunakan Flutter, dari wireframe hingga publikasi aplikasi.'],
            ['RPL-ALG-004', 'Algoritma dan Struktur Data', 'Pemrograman', ['Yudi Utomo', 'Kurniawan'], 'Informatika', 2021, '978-602-1234-04-2', 'RPL', 'Lantai 1 – Rak RPL Blok B Nomor 07', 4, '005.131 YUD a', 'Algoritma dasar, flowchart, sorting, searching, serta implementasi struktur data dalam pemrograman imperatif.'],
            ['RPL-DGS-005', 'Desain Grafis untuk Multimedia', 'Pemrograman', ['Bayu Prasetyo'], 'Elex Media', 2023, '978-602-1234-05-9', 'RPL', 'Lantai 1 – Rak RPL Blok C Nomor 01', 3, '006.6 BAY d', 'Prinsip desain grafis, tipografi, dan pemanfaatan Adobe Illustrator serta CorelDRAW untuk kebutuhan percetakan dan media digital.'],
            ['TKJ-JAR-001', 'Administrasi Sistem Jaringan', 'Jaringan Komputer', ['Ferdian Saputra'], 'Andi Media', 2023, '978-602-1234-06-6', 'TKJ', 'Lantai 1 – Rak TKJ Blok D Nomor 02', 4, '004.6 FER a', 'Instalasi dan konfigurasi server Linux dan Windows: DNS, DHCP, file server, hingga monitoring jaringan.'],
            ['TKJ-TKN-002', 'Teknologi Jaringan Kabel dan Nirkabel', 'Jaringan Komputer', ['Agus Setiawan'], 'Gava Media', 2022, '978-602-1234-07-3', 'TKJ', 'Lantai 1 – Rak TKJ Blok D Nomor 03', 3, '004.6 AGU t', 'Topologi jaringan, kabel twisted pair dan fiber optic, konfigurasi access point, serta keamanan jaringan nirkabel.'],
            ['TKJ-KJD-003', 'Komputer dan Jaringan Dasar', 'Jaringan Komputer', ['Dwi Hariyanto'], 'Elex Media', 2021, '978-602-1234-08-0', 'TKJ', 'Lantai 1 – Rak TKJ Blok D Nomor 01', 5, '004.6 DWI k', 'Pengenalan perangkat keras dan lunak jaringan, model referensi OSI dan TCP/IP untuk siswa SMK kelas X.'],
            ['TKJ-SJL-004', 'Sistem Jaringan Lanjutan', 'Jaringan Komputer', ['Ferdian Saputra', 'Agus Setiawan'], 'Andi Media', 2024, '978-602-1234-09-7', 'TKJ', 'Lantai 1 – Rak TKJ Blok D Nomor 04', 2, '004.6 FER s', 'Routing statis dan dinamis, VLAN, VPN, serta simulasi jaringan enterprise menggunakan Cisco Packet Tracer.'],
            ['AKL-AKD-001', 'Akuntansi Dasar', 'Akuntansi', ['Siti Aminah'], 'Salemba Empat', 2022, '978-602-1234-10-3', 'AKL', 'Lantai 1 – Rak AKL Blok A Nomor 01', 4, '657.22 SIT a', 'Prinsip pembukuan double entry, jurnal, buku besar, neraca saldo, hingga laporan keuangan sederhana.'],
            ['AKL-PAK-002', 'Praktikum Akuntansi Keuangan', 'Akuntansi', ['Rina Kartika'], 'Salemba Empat', 2023, '978-602-1234-11-0', 'AKL', 'Lantai 1 – Rak AKL Blok A Nomor 02', 3, '657.22 RIN p', 'Pendamping praktikum siklus akuntansi perusahaan dagang dan jasa dengan studi kasus aplikasi akuntansi digital.'],
            ['OTKP-MPB-001', 'Manajemen Perkantoran dan Layanan Bisnis', 'Manajemen Perkantoran', ['Endang Susilowati'], 'Grafindo', 2022, '978-602-1234-12-7', 'OTKP', 'Lantai 1 – Rak OTKP Blok E Nomor 01', 3, '651 END m', 'Tata kelola administrasi perkantoran modern: manajemen arsip, agenda, rapat, dan layanan pelanggan.'],
            ['OTKP-KDD-002', 'Korespondensi dan Dokumentasi Digital', 'Manajemen Perkantoran', ['Nurul Hidayah'], 'Grafindo', 2023, '978-602-1234-13-4', 'OTKP', 'Lantai 1 – Rak OTKP Blok E Nomor 02', 2, '651.5 NUR k', 'Penulisan surat dinas, notulensi, dan pengelolaan dokumen digital berbasis cloud untuk era industri 4.0.'],
            ['UMK-BIN-001', 'Bahasa Indonesia untuk SMK', 'Bahasa & Sastra', ['Darmawan'], 'Yudhistira', 2021, '978-602-1234-14-1', 'Umum', 'Lantai 2 – Rak Umum Blok F Nomor 01', 6, '499.22 DAR b', 'Materi bahasa Indonesia SMA/SMK: teks deskripsi, argumentasi, karya ilmiah, dan tata bahasa praktis.'],
            ['UMK-BIN-002', 'English for Professional', 'Bahasa & Sastra', ['Sarah Wijaya', 'Darmawan'], 'Yudhistira', 2022, '978-602-1234-15-8', 'Umum', 'Lantai 2 – Rak Umum Blok F Nomor 02', 5, '428 SAR e', 'English for workplace correspondence, presentation, dan interview untuk siswa SMK menuju dunia kerja.'],
            ['UMK-MTD-001', 'Matematika SMK Program Umum', 'Matematika', ['Bambang Suryono'], 'Erlangga', 2021, '978-602-1234-16-5', 'Umum', 'Lantai 2 – Rak Umum Blok F Nomor 03', 5, '510 BAM m', 'Aljabar, geometri, statistika, dan peluang dengan pendekatan konteks kejuruan dan penerapan sehari-hari.'],
            ['UMK-PKN-001', 'Pendidikan Kewarganegaraan', 'Sejarah & Kewarganegaraan', ['Hartono'], 'Yudhistira', 2021, '978-602-1234-17-2', 'Umum', 'Lantai 2 – Rak Umum Blok F Nomor 04', 4, '342 HAR p', 'Kedudukan Pancasila, UUD NRI Tahun 1945, hak dan kewajiban warga negara, serta kehidupan bermasyarakat berbangsa dan bernegara.'],
            ['UMK-INF-002', 'Informatika untuk SMK', 'Pemrograman', ['Kurniawan'], 'Informatika', 2024, '978-602-1234-18-9', 'Umum', 'Lantai 2 – Rak Umum Blok F Nomor 05', 5, '004 KUR i', 'Algoritma berpikir komputasional, dasar pemrograman Python, analisis data, dan pengenalan kecerdasan artifisial.'],
        ];

        foreach ($buku as [$kode, $judul, $kategoriNama, $penulisNama, $penerbit, $tahun, $isbn, $jurusan, $rak, $stok, $panggil, $deskripsi]) {
            $book = Book::create([
                'kode_buku' => $kode,
                'judul' => $judul,
                'kategori_id' => $kategori[$kategoriNama]->id,
                'penerbit' => $penerbit,
                'tahun' => $tahun,
                'isbn' => $isbn,
                'tebal_halaman' => 120 + (strlen($judul) * 3),
                'bahasa' => 'Indonesia',
                'deskripsi' => $deskripsi,
                'nomor_panggil' => $panggil,
                'jurusan' => $jurusan,
                'lokasi_rak' => $rak,
                'stok' => $stok,
                'status' => Book::STATUS_AKTIF,
            ]);

            foreach ($penulisNama as $namaPenulis) {
                $book->authors()->attach($penulis[$namaPenulis]->id);
            }
        }
    }
}
