param(
    [string]$TargetDir = (Join-Path $PSScriptRoot '..\writable\bin'),
    [string]$ExpectedHash = '60f467265b1e312373dbcd92200c2618a74850f98d3d078e94296bb3fa2047ba'
)

$TargetDir = [System.IO.Path]::GetFullPath($TargetDir)
if (-not (Test-Path $TargetDir)) {
    New-Item -ItemType Directory -Force -Path $TargetDir | Out-Null
}

$ffmpegExe = Join-Path $TargetDir 'ffmpeg.exe'
$ffprobeExe = Join-Path $TargetDir 'ffprobe.exe'

if ((Test-Path $ffmpegExe) -and (Test-Path $ffprobeExe)) {
    Write-Host "FFmpeg dan FFprobe portabel sudah ada di: $TargetDir" -ForegroundColor Green
    & $ffmpegExe -version | Select-Object -First 1
    & $ffprobeExe -version | Select-Object -First 1
    exit 0
}

$tempZip = Join-Path $TargetDir 'ffmpeg-release-essentials.zip'
$extractFolder = Join-Path $TargetDir 'temp_extract'
$url = 'https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip'

Write-Host "Mengunduh paket resmi FFmpeg portabel (gyan.dev)..." -ForegroundColor Cyan
Write-Host "URL: $url" -ForegroundColor Gray
Write-Host "Tujuan: $tempZip" -ForegroundColor Gray

# Unduh via curl.exe dengan progress
& curl.exe -L -o $tempZip $url

if (-not (Test-Path $tempZip)) {
    Write-Host "Gagal mengunduh file zip FFmpeg." -ForegroundColor Red
    exit 1
}

Write-Host "Memverifikasi SHA256 checksum..." -ForegroundColor Cyan
$actualHash = (Get-FileHash -Path $tempZip -Algorithm SHA256).Hash.ToLower()

if ($actualHash -ne $ExpectedHash.ToLower()) {
    Write-Host "CHECKSUM TIDAK COCOK!" -ForegroundColor Red
    Write-Host "Ekspektasi: $ExpectedHash" -ForegroundColor Red
    Write-Host "Aktual    : $actualHash" -ForegroundColor Red
    Remove-Item $tempZip -Force -ErrorAction SilentlyContinue
    exit 1
}

Write-Host "Checksum terverifikasi: $actualHash" -ForegroundColor Green
Write-Host "Mengekstrak binary ffmpeg.exe dan ffprobe.exe..." -ForegroundColor Cyan

if (Test-Path $extractFolder) {
    Remove-Item -Recurse -Force $extractFolder -ErrorAction SilentlyContinue
}

Expand-Archive -Path $tempZip -DestinationPath $extractFolder -Force

$foundFfmpeg = Get-ChildItem -Path $extractFolder -Filter 'ffmpeg.exe' -Recurse | Select-Object -First 1
$foundFfprobe = Get-ChildItem -Path $extractFolder -Filter 'ffprobe.exe' -Recurse | Select-Object -First 1

if ($foundFfmpeg -and $foundFfprobe) {
    Copy-Item $foundFfmpeg.FullName -Destination $ffmpegExe -Force
    Copy-Item $foundFfprobe.FullName -Destination $ffprobeExe -Force
    Write-Host "Binary berhasil ditempatkan pada:" -ForegroundColor Green
    Write-Host " - $ffmpegExe" -ForegroundColor Green
    Write-Host " - $ffprobeExe" -ForegroundColor Green
} else {
    Write-Host "Binary ffmpeg.exe / ffprobe.exe tidak ditemukan di dalam arsip zip." -ForegroundColor Red
    exit 1
}

# Bersihkan file arsip sementara
Remove-Item $tempZip -Force -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force $extractFolder -ErrorAction SilentlyContinue

Write-Host "Verifikasi eksekusi FFmpeg portabel:" -ForegroundColor Cyan
& $ffmpegExe -version | Select-Object -First 2
& $ffprobeExe -version | Select-Object -First 2
