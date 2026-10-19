$ErrorActionPreference = 'Stop'
$rootDir = "D:\Project Coding Daus\Web\Sistem Monitoring Over Dimension Dishub"
$ffmpeg = Join-Path $rootDir "writable\bin\ffmpeg.exe"
$cacheTest = Join-Path $rootDir "writable\cache\test_brimob_f2"

if (Test-Path $cacheTest) {
    Remove-Item -Path $cacheTest -Recurse -Force
}
New-Item -ItemType Directory -Path $cacheTest -Force | Out-Null

$envFile = Join-Path $rootDir '.env'
$envConfig = @{}
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -and -not $line.StartsWith('#') -and $line.Contains('=')) {
            $parts = $line.Split('=', 2)
            $envConfig[$parts[0].Trim()] = $parts[1].Trim().Trim("'").Trim('"')
        }
    }
}
$auth = [Uri]::EscapeDataString($envConfig['vivotek.nvrUsername']) + ':' + [Uri]::EscapeDataString($envConfig['vivotek.nvrPassword']) + '@'
$chan = 6 # BJB-BRIMOB-F2
$streamIdx = 2 # Stream 2 H.264
$rtspUrl = "rtsp://${auth}10.0.1.3:8554/Media/Live/Normal?camera=C_${chan}&streamindex=${streamIdx}"

$outFile = Join-Path $cacheTest 'index.m3u8'
$segPattern = Join-Path $cacheTest 'seg_%03d.ts'
$snapFile = Join-Path $cacheTest 'snapshot.jpg'
$logFile = Join-Path $cacheTest 'ffmpeg.log'

$cmdArgs = @(
    '-hide_banner',
    '-loglevel', 'info',
    '-nostdin',
    '-rtsp_transport', 'tcp',
    '-timeout', '8000000',
    '-i', $rtspUrl,
    '-map', '0:v',
    '-c:v', 'copy',
    '-an',
    '-f', 'hls',
    '-hls_time', '2',
    '-hls_list_size', '5',
    '-hls_flags', 'delete_segments+temp_file',
    '-hls_segment_filename', $segPattern,
    $outFile,
    '-map', '0:v',
    '-vf', 'fps=1/5',
    '-update', '1',
    '-y',
    $snapFile
)

Write-Host "Menjalankan FFmpeg dengan parameter lengkap vivotek_relay..."
$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = $ffmpeg
$psi.Arguments = ($cmdArgs | ForEach-Object {
    if ($_ -match '\s') { "`"$_`"" } else { $_ }
}) -join ' '
$psi.RedirectStandardError = $true
$psi.RedirectStandardOutput = $true
$psi.UseShellExecute = $false
$psi.WorkingDirectory = $cacheTest

$proc = [System.Diagnostics.Process]::Start($psi)
Start-Sleep -Seconds 12

$hasExited = $proc.HasExited
Write-Host "Proses aktif: $(-not $hasExited) (PID $($proc.Id))"

$segFiles = Get-ChildItem -Path $cacheTest -Filter "seg_*.ts"
Write-Host "Jumlah segmen: $($segFiles.Count)"
foreach ($s in $segFiles | Select-Object -First 3) {
    Write-Host ("  Segmen: " + $s.Name + " (" + [math]::Round($s.Length / 1KB, 1) + " KB)")
}

if (-not $hasExited) {
    $proc.Kill()
}

$stderr = $proc.StandardError.ReadToEnd()
Write-Host "FFmpeg Stderr (last 15 lines):"
($stderr -split "`r?`n") | Select-Object -Last 15

# Cleanup
Remove-Item -Path $cacheTest -Recurse -Force -ErrorAction SilentlyContinue
