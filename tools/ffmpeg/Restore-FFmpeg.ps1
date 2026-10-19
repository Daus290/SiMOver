<#
.SYNOPSIS
    Memulihkan executable FFmpeg dan FFprobe ke writable/bin/ dari arsip ffmpeg-windows.zip.

.DESCRIPTION
    Skrip ini digunakan setelah melakukan clone repositori SiMover pada lingkungan baru.
    Mengekstrak binary ffmpeg.exe dan ffprobe.exe ke folder writable/bin/ dengan verifikasi integritas SHA-256.
    Aman dijalankan berulang kali: jika berkas sudah ada dan hash cocok, pemulihan dilewati.
    Jika berkas ada dengan hash berbeda, skrip melaporkan konflik tanpa menimpa proses aktif.
#>

[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$scriptDir = $PSScriptRoot
$repoRoot = (Resolve-Path (Join-Path $scriptDir "..\..")).Path
$archivePath = Join-Path $scriptDir "ffmpeg-windows.zip"
$targetDir = Join-Path $repoRoot "writable\bin"

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "  PEMULIHAN BINARY FFMPEG & FFPROBE - SiMover Dishub Banjarbaru  " -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "Repo Root : $repoRoot"
Write-Host "Arsip     : $archivePath"
Write-Host "Target    : $targetDir"
Write-Host ""

if (-not (Test-Path -Path $archivePath -PathType Leaf)) {
    Write-Error "Berkas arsip tidak ditemukan: $archivePath"
    exit 1
}

if (-not (Test-Path -Path $targetDir -PathType Container)) {
    Write-Host "Membuat direktori target: $targetDir" -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
}

$expectedHashes = @{
    'ffmpeg.exe'  = '3256173F3F8BFFD7DF12227C68ADF68025EDB1832273A9530688A7BB1ED8EDEC'
    'ffprobe.exe' = 'F0D36ECBBDD3BCFAC3EFA078C96C7271C2E68B3810595552AC3B7F17E9A65C52'
}

# Verifikasi keberadaan file lokal terlebih dahulu
$needExtraction = $false
foreach ($binary in $expectedHashes.Keys) {
    $destFile = Join-Path $targetDir $binary
    if (Test-Path -Path $destFile -PathType Leaf) {
        $actualHash = (Get-FileHash -Path $destFile -Algorithm SHA256).Hash.ToUpperInvariant()
        if ($actualHash -eq $expectedHashes[$binary]) {
            Write-Host "[OK] $binary sudah ada di writable\bin\ dan SHA-256 cocok. Tidak perlu ditimpa." -ForegroundColor Green
        } else {
            Write-Warning "[PERINGATAN] $binary ada di target namun memiliki hash berbeda ($actualHash). File tidak ditimpa otomatis."
        }
    } else {
        $needExtraction = $true
    }
}

if (-not $needExtraction) {
    Write-Host ""
    Write-Host "Seluruh binary FFmpeg/FFprobe sudah lengkap dan terverifikasi. Tidak ada tindakan ekstraksi." -ForegroundColor Green
    exit 0
}

Write-Host ""
Write-Host "Mengekstrak binary yang belum ada dari $archivePath..." -ForegroundColor Yellow

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($archivePath)

try {
    foreach ($entry in $zip.Entries) {
        $name = [System.IO.Path]::GetFileName($entry.FullName)
        if (-not $expectedHashes.ContainsKey($name)) {
            Write-Host "Melewati berkas tak terdaftar dalam manifest: $($entry.FullName)" -ForegroundColor DarkGray
            continue
        }

        $destFile = Join-Path $targetDir $name
        if (Test-Path -Path $destFile -PathType Leaf) {
            $currentHash = (Get-FileHash -Path $destFile -Algorithm SHA256).Hash.ToUpperInvariant()
            if ($currentHash -eq $expectedHashes[$name]) {
                continue
            }
        }

        Write-Host "Mengekstrak $name ke $destFile..."
        [System.IO.Compression.ZipFileExtensions]::ExtractToFile($entry, $destFile, $false)

        $extractedHash = (Get-FileHash -Path $destFile -Algorithm SHA256).Hash.ToUpperInvariant()
        if ($extractedHash -eq $expectedHashes[$name]) {
            Write-Host "[SUKSES] $name berhasil dipulihkan (SHA-256 Terverifikasi: $extractedHash)" -ForegroundColor Green
        } else {
            Write-Error "[GAGAL] Hash $name tidak cocok setelah ekstraksi! Ditemukan: $extractedHash, Harapan: $($expectedHashes[$name])"
        }
    }
}
finally {
    $zip.Dispose()
}

Write-Host ""
Write-Host "Pemulihan selesai. Layanan relay CCTV SiMover siap dijalankan." -ForegroundColor Cyan
