$rootDir = "D:\Project Coding Daus\Web\Sistem Monitoring Over Dimension Dishub"
$vivotekRelay = Join-Path $rootDir "scripts\vivotek_relay.ps1"

Write-Host "Memulai Pemantauan 5 Menit (300 detik) untuk seluruh 16 Relay Kamera Banjarbaru..."
$startTime = Get-Date
$samples = 0
$totalDeadEvents = 0

$history = @()

while ((Get-Date) - $startTime -lt [TimeSpan]::FromMinutes(5)) {
    $samples++
    $now = Get-Date -Format 'HH:mm:ss'
    
    # Ambil status relay
    $procs = Get-Process ffmpeg -ErrorAction SilentlyContinue
    $procCount = $procs.Count
    $memMB = [math]::Round((($procs | Measure-Object -Property WorkingSet64 -Sum).Sum / 1MB), 1)
    
    # Cek apakah ada relay kamera yang mati
    $statusRaw = & powershell -ExecutionPolicy Bypass -File $vivotekRelay -Action status
    $deadLines = $statusRaw | Select-String -Pattern "\[BERHENTI\]"
    $activeLines = $statusRaw | Select-String -Pattern "\[AKTIF - PID"
    
    $deadCount = $deadLines.Count
    $activeCount = $activeLines.Count
    if ($deadCount -gt 0) {
        $totalDeadEvents += $deadCount
    }
    
    Write-Host "[$now] Sampel #$samples : Aktif = $activeCount, Berhenti = $deadCount, FFmpeg Proc = $procCount, RAM = $memMB MB"
    
    $history += [PSCustomObject]@{
        Time = $now
        Sample = $samples
        ActiveRelays = $activeCount
        StoppedRelays = $deadCount
        FfmpegProcesses = $procCount
        FfmpegRAM_MB = $memMB
    }
    
    Start-Sleep -Seconds 30
}

Write-Host "`n=== HASIL AKHIR PEMANTAUAN 5 MENIT ==="
Write-Host "Total Sampel Diambil   : $samples"
Write-Host "Total Kejadian Berhenti: $totalDeadEvents"
$avgMem = ($history | Measure-Object -Property FfmpegRAM_MB -Average).Average
Write-Host "Rata-rata Penggunaan RAM FFmpeg: $([math]::Round($avgMem, 1)) MB"

$history | Format-Table -AutoSize
$history | Export-Csv -Path (Join-Path $rootDir "scratch\monitor_5min_report.csv") -NoTypeInformation
