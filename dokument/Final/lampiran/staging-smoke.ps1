param(
    [string]$BaseUrl = 'http://127.0.0.1:8010'
)

$ErrorActionPreference = 'Stop'
$tmp = Join-Path $env:TEMP ('smartlib-staging-smoke-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $tmp | Out-Null

$script:gagal = 0
$script:lulus = 0

function Hasil([string]$nama, [bool]$lulus, [string]$detail = '') {
    if ($lulus) {
        $script:lulus++
        $ekor = if ($detail) { " -> $detail" } else { '' }
        Write-Output ("[PASS] {0}{1}" -f $nama, $ekor)
    } else {
        $script:gagal++
        $ekor = if ($detail) { " -> $detail" } else { '' }
        Write-Output ("[FAIL] {0}{1}" -f $nama, $ekor)
    }
}

function Ambil([string]$jalur, [string]$jar, [string]$nama) {
    $body = Join-Path $tmp "$nama.html"
    $headers = Join-Path $tmp "$nama.headers"
    $argumen = @('-s', '-b', $jar, '-c', $jar, '-o', $body, '-D', $headers, '-w', '%{http_code}|%{redirect_url}', "$BaseUrl$jalur")
    $meta = & curl.exe @argumen
    $bagian = $meta -split '\|', 2
    return @{
        Status = [int]$bagian[0]
        Redirect = if ($bagian.Count -gt 1) { $bagian[1] } else { '' }
        Body = $body
        Headers = $headers
        Isi = Get-Content -LiteralPath $body -Raw -ErrorAction SilentlyContinue
    }
}

function TokenLogin([string]$jar, [string]$nama) {
    $halaman = Ambil '/login' $jar $nama
    if ($halaman.Status -ne 200) { throw "Halaman login tidak dapat dibuka ($($halaman.Status))." }
    $cocok = [regex]::Match($halaman.Isi, 'name="_token"\s+value="([^"]+)"')
    if (-not $cocok.Success) { throw 'Token CSRF login tidak ditemukan.' }

    return $cocok.Groups[1].Value
}

function LoginKe([string]$email, [string]$jar, [string]$nama) {
    $token = TokenLogin $jar "$nama-login"
    $argumen = @(
        '-s', '-b', $jar, '-c', $jar, '-o', 'NUL', '-w', '%{http_code}|%{redirect_url}',
        '--data-urlencode', "_token=$token",
        '--data-urlencode', "email=$email",
        '--data-urlencode', 'password=password',
        "$BaseUrl/login"
    )
    $meta = & curl.exe @argumen
    $bagian = $meta -split '\|', 2

    return @{ Status = [int]$bagian[0]; Redirect = if ($bagian.Count -gt 1) { $bagian[1] } else { '' } }
}

Write-Output 'Smart Library — staging smoke test'
Write-Output "Base URL : $BaseUrl"
Write-Output "Waktu    : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-Output ''

# ------------------------------------------------------------------
# Publik
# ------------------------------------------------------------------
$root = & curl.exe -s -o NUL -w '%{http_code}|%{redirect_url}' "$BaseUrl/"
$rootBagian = $root -split '\|', 2
Hasil 'GET / mengarah ke katalog' ($rootBagian[0] -eq '302' -and $rootBagian[1] -like '*/katalog') ($rootBagian -join ' -> ')

$katalog = Ambil '/katalog' '' 'katalog'
Hasil 'GET /katalog merespons 200' ($katalog.Status -eq 200) "status $($katalog.Status)"
Hasil 'Katalog memuat judul aplikasi' ($katalog.Isi -match 'Smart Library') ''
$headerKatalog = Get-Content -LiteralPath $katalog.Headers -Raw
Hasil 'Header X-Content-Type-Options: nosniff' ($headerKatalog -match '(?im)^X-Content-Type-Options:\s*nosniff') ''
Hasil 'Header X-Frame-Options: SAMEORIGIN' ($headerKatalog -match '(?im)^X-Frame-Options:\s*SAMEORIGIN') ''

$login = Ambil '/login' '' 'login'
Hasil 'GET /login merespons 200' ($login.Status -eq 200) "status $($login.Status)"

# ------------------------------------------------------------------
# Sesi siswa
# ------------------------------------------------------------------
$jarSiswa = Join-Path $tmp 'siswa.cookies'
$postSiswa = LoginKe 'rizky@siswa.smkbb.sch.id' $jarSiswa 'siswa'
Hasil 'Login siswa berhasil' ($postSiswa.Status -eq 302 -and $postSiswa.Redirect -like '*/beranda') "$($postSiswa.Status) -> $($postSiswa.Redirect)"

$beranda = Ambil '/beranda' $jarSiswa 'beranda'
Hasil 'GET /beranda merespons 200' ($beranda.Status -eq 200) "status $($beranda.Status)"
Hasil 'Beranda memuat nama siswa' ($beranda.Isi -match 'Rizky Pratama') ''

$pinjaman = Ambil '/pinjaman' $jarSiswa 'pinjaman'
Hasil 'GET /pinjaman merespons 200' ($pinjaman.Status -eq 200) "status $($pinjaman.Status)"
Hasil 'Tab RC-07 Ditolak / Dibatalkan tampil' ($pinjaman.Isi -match 'Ditolak / Dibatalkan') ''

$riwayat = Ambil '/riwayat' $jarSiswa 'riwayat'
Hasil 'GET /riwayat merespons 200' ($riwayat.Status -eq 200) "status $($riwayat.Status)"

$bebas = Ambil '/riwayat/bebas-pustaka' $jarSiswa 'bebas-pustaka'
Hasil 'Bebas pustaka diblokir untuk anggota berpinjaman (RC-02)' ($bebas.Status -eq 403) "status $($bebas.Status)"

# ------------------------------------------------------------------
# Sesi petugas
# ------------------------------------------------------------------
$jarPetugas = Join-Path $tmp 'petugas.cookies'
$postPetugas = LoginKe 'petugas@smartlibrary.sch.id' $jarPetugas 'petugas'
Hasil 'Login petugas berhasil' ($postPetugas.Status -eq 302 -and $postPetugas.Redirect -like '*/petugas*') "$($postPetugas.Status) -> $($postPetugas.Redirect)"

$dashboard = Ambil '/petugas' $jarPetugas 'dashboard-petugas'
Hasil 'GET /petugas merespons 200' ($dashboard.Status -eq 200) "status $($dashboard.Status)"

$sirkulasi = Ambil '/petugas/sirkulasi' $jarPetugas 'sirkulasi'
Hasil 'GET /petugas/sirkulasi merespons 200' ($sirkulasi.Status -eq 200) "status $($sirkulasi.Status)"

$pdfBody = Join-Path $tmp 'laporan.pdf'
$pdfHeaders = Join-Path $tmp 'laporan.headers'
$argumenPdf = @(
    '-s', '-b', $jarPetugas, '-o', $pdfBody, '-D', $pdfHeaders, '-w', '%{http_code}|%{content_type}',
    "$BaseUrl/petugas/laporan/pdf?jenis=transaksi&dari=2026-01-01&sampai=2026-12-31"
)
$metaPdf = & curl.exe @argumenPdf
$pdfBagian = $metaPdf -split '\|', 2
Hasil 'Unduh laporan PDF berhasil' ($pdfBagian[0] -eq '200' -and $pdfBagian[1] -match 'application/pdf') ($pdfBagian -join ' -> ')
$magic = [System.IO.File]::ReadAllBytes($pdfBody)[0..3] -join ','
Hasil 'Berkas PDF memiliki magic bytes %PDF' ($magic -eq '37,80,68,70') $magic

# ------------------------------------------------------------------
# Sesi admin
# ------------------------------------------------------------------
$jarAdmin = Join-Path $tmp 'admin.cookies'
$postAdmin = LoginKe 'admin@smartlibrary.sch.id' $jarAdmin 'admin'
Hasil 'Login admin berhasil' ($postAdmin.Status -eq 302 -and $postAdmin.Redirect -like '*/admin*') "$($postAdmin.Status) -> $($postAdmin.Redirect)"

$pengguna = Ambil '/admin/pengguna' $jarAdmin 'pengguna-admin'
Hasil 'GET /admin/pengguna merespons 200' ($pengguna.Status -eq 200) "status $($pengguna.Status)"

Write-Output ''
Write-Output ("Ringkasan: {0} lulus, {1} gagal" -f $script:lulus, $script:gagal)
Write-Output "Artefak sementara: $tmp"

if ($script:gagal -gt 0) { exit 1 }
exit 0
