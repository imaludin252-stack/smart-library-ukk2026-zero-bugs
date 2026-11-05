# Audit Responsif Sprint 3 (S3-05)

Pengujian otomatis menggunakan Chrome headless (CDP) pada 8 halaman x 3 ukuran viewport.

Kriteria lulus: tidak ada overflow horizontal pada level dokumen (scrollWidth <= innerWidth), drawer sidebar tersembunyi di mobile/tablet dan tampil di desktop, serta tabel lebar berada di dalam .table-wrap yang dapat digeser.

| Halaman | Viewport | Overflow (px) | Elemen meluber | Bottom nav | Sidebar kiri | Tabel / scrollable |
|---|---:|---:|---:|:---:|---:|---:|
| katalog | 390 | 0 | 0 | tidak |  | 0 / ya |
| katalog | 768 | 0 | 0 | tidak |  | 0 / ya |
| katalog | 1280 | 0 | 0 | tidak |  | 0 / ya |
| login | 390 | 0 | 0 | tidak |  | 0 / ya |
| login | 768 | 0 | 0 | tidak |  | 0 / ya |
| login | 1280 | 0 | 0 | tidak |  | 0 / ya |
| siswa-beranda | 390 | 0 | 0 | ya | -240 | 0 / ya |
| siswa-beranda | 768 | 0 | 0 | ya | -240 | 0 / ya |
| siswa-beranda | 1280 | 0 | 0 | tidak | 0 | 0 / ya |
| siswa-pinjaman | 390 | 0 | 0 | ya | -240 | 0 / ya |
| siswa-pinjaman | 768 | 0 | 0 | ya | -240 | 0 / ya |
| siswa-pinjaman | 1280 | 0 | 0 | tidak | 0 | 0 / ya |
| petugas-sirkulasi | 390 | 0 | 0 | tidak | -240 | 1 / ya |
| petugas-sirkulasi | 768 | 0 | 0 | tidak | -240 | 1 / ya |
| petugas-sirkulasi | 1280 | 0 | 0 | tidak | 0 | 1 / ya |
| petugas-koleksi | 390 | 0 | 0 | tidak | -240 | 1 / ya |
| petugas-koleksi | 768 | 0 | 0 | tidak | -240 | 1 / ya |
| petugas-koleksi | 1280 | 0 | 0 | tidak | 0 | 1 / ya |
| petugas-laporan | 390 | 0 | 0 | tidak | -240 | 1 / ya |
| petugas-laporan | 768 | 0 | 0 | tidak | -240 | 1 / ya |
| petugas-laporan | 1280 | 0 | 0 | tidak | 0 | 1 / ya |
| admin-pengguna | 390 | 0 | 0 | tidak | -240 | 1 / ya |
| admin-pengguna | 768 | 0 | 0 | tidak | -240 | 1 / ya |
| admin-pengguna | 1280 | 0 | 0 | tidak | 0 | 1 / ya |

**Hasil:** 24/24 kombinasi halaman-viewport lulus dengan overflow dokumen 0 px dan 0 elemen meluber.
