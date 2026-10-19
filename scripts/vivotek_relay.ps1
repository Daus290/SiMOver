<#
.SYNOPSIS
    Vivotek RTSP-to-HLS Relay Manager (Dishub Kota Banjarbaru)
.DESCRIPTION
    Mengelola proses background relay RTSP ke segmen HLS lokal untuk seluruh kamera Banjarbaru
    menggunakan FFmpeg portabel (writable\bin\ffmpeg.exe) dan HLS.js.
    Mendukung kontrol per kamera, per persimpangan, atau seluruh kamera terpasang.
.EXAMPLE
    .\scripts\vivotek_relay.ps1 -Action status
    .\scripts\vivotek_relay.ps1 -Action start -Camera BJB-BRIMOB-F1
    .\scripts\vivotek_relay.ps1 -Action start -Intersection s3-brimob
    .\scripts\vivotek_relay.ps1 -Action stop -Camera BJB-BRIMOB-F1
    .\scripts\vivotek_relay.ps1 -Action diag -Camera BJB-BRIMOB-F1
#>

param(
    [ValidateSet('start', 'stop', 'status', 'restart', 'clean', 'diag')]
    [string]$Action = 'status',
    
    [string]$Camera = '',

    [ValidateSet('', 's3-brimob', 's3-ro-ulin', 's4-samsat', 's4-adipura', 'all')]
    [string]$Intersection = ''
)

$rootDir = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $rootDir '.env'
$cacheBase = Join-Path $rootDir 'writable\cache\vivotek_stream'

if (-not (Test-Path $cacheBase)) {
    New-Item -ItemType Directory -Force -Path $cacheBase | Out-Null
}

# 1. Baca konfigurasi .env lokal secara privat
$envConfig = @{}
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -and -not $line.StartsWith('#') -and $line.Contains('=')) {
            $parts = $line.Split('=', 2)
            $k = $parts[0].Trim()
            $v = $parts[1].Trim().Trim("'").Trim('"')
            $envConfig[$k] = $v
        }
    }
}

$nvrHost = if ($envConfig['vivotek.nvrHost']) { $envConfig['vivotek.nvrHost'] } else { '10.0.1.3' }
$nvrPort = if ($envConfig['vivotek.nvrRtspPort']) { $envConfig['vivotek.nvrRtspPort'] } else { '8554' }
$nvrUser = $envConfig['vivotek.nvrUsername']
$nvrPass = $envConfig['vivotek.nvrPassword']

# 2. Registri Terpusat 18 Kamera Banjarbaru (Ground Truth NVR ND9541)
$cameraRegistry = @{
    # Simpang RO Ulin
    'BJB-ROULIN-F1'  = @{ Channel = 1;  Stream = 2; Loc = 's3-ro-ulin'; Name = 'RO Ulin Fase 1'; Relayable = $true;  Codec = 'H.264' }
    'BJB-ROULIN-F2'  = @{ Channel = 2;  Stream = 1; Loc = 's3-ro-ulin'; Name = 'RO Ulin Fase 2'; Relayable = $true;  Codec = 'H.265' }
    'BJB-ROULIN-F3'  = @{ Channel = 3;  Stream = 1; Loc = 's3-ro-ulin'; Name = 'RO Ulin Fase 3'; Relayable = $false; Codec = 'H.265'; Note = 'Belum Tersedia di Lapangan' }
    'BJB-ROULIN-PTZ' = @{ Channel = 4;  Stream = 1; Loc = 's3-ro-ulin'; Name = 'RO Ulin PTZ';    Relayable = $true;  Codec = 'H.264' }

    # Simpang Brimob
    'BJB-BRIMOB-F1'  = @{ Channel = 5;  Stream = 2; Loc = 's3-brimob';  Name = 'Brimob Fase 1';  Relayable = $true;  Codec = 'H.264' }
    'BJB-BRIMOB-F2'  = @{ Channel = 6;  Stream = 2; Loc = 's3-brimob';  Name = 'Brimob Fase 2';  Relayable = $true;  Codec = 'H.264' }
    'BJB-BRIMOB-F3'  = @{ Channel = 7;  Stream = 2; Loc = 's3-brimob';  Name = 'Brimob Fase 3';  Relayable = $true;  Codec = 'H.264' }
    'BJB-BRIMOB-PTZ' = @{ Channel = 8;  Stream = 3; Loc = 's3-brimob';  Name = 'Brimob PTZ';     Relayable = $true;  Codec = 'H.264' }

    # Simpang Samsat
    'BJB-SAMSAT-F1'  = @{ Channel = 9;  Stream = 1; Loc = 's4-samsat';  Name = 'Samsat Fase 1';  Relayable = $false; Codec = 'H.264'; Note = 'Tidak Aktif (Kerusakan Perangkat)' }
    'BJB-SAMSAT-F2'  = @{ Channel = 10; Stream = 2; Loc = 's4-samsat';  Name = 'Samsat Fase 2';  Relayable = $true;  Codec = 'H.264' }
    'BJB-SAMSAT-F3'  = @{ Channel = 11; Stream = 2; Loc = 's4-samsat';  Name = 'Samsat Fase 3';  Relayable = $true;  Codec = 'H.264' }
    'BJB-SAMSAT-F4'  = @{ Channel = 12; Stream = 2; Loc = 's4-samsat';  Name = 'Samsat Fase 4';  Relayable = $true;  Codec = 'H.264' }
    'BJB-SAMSAT-PTZ' = @{ Channel = 13; Stream = 2; Loc = 's4-samsat';  Name = 'Samsat PTZ';     Relayable = $true;  Codec = 'H.264' }

    # Simpang Adipura
    'BJB-ADIPURA-F1'  = @{ Channel = 15; Stream = 3; Loc = 's4-adipura'; Name = 'Adipura Fase 1'; Relayable = $true;  Codec = 'H.264' }
    'BJB-ADIPURA-F2'  = @{ Channel = 16; Stream = 2; Loc = 's4-adipura'; Name = 'Adipura Fase 2'; Relayable = $true;  Codec = 'H.264' }
    'BJB-ADIPURA-F3'  = @{ Channel = 17; Stream = 2; Loc = 's4-adipura'; Name = 'Adipura Fase 3'; Relayable = $true;  Codec = 'H.264' }
    'BJB-ADIPURA-F4'  = @{ Channel = 18; Stream = 2; Loc = 's4-adipura'; Name = 'Adipura Fase 4'; Relayable = $true;  Codec = 'H.264' }
    'BJB-ADIPURA-PTZ' = @{ Channel = 19; Stream = 3; Loc = 's4-adipura'; Name = 'Adipura PTZ';    Relayable = $true;  Codec = 'H.264' }
}

# 3. Cari FFmpeg Portabel Terlebih Dahulu (Prioritas: writable\bin\ffmpeg.exe)
$ffmpegCmd = $null
$candidatePaths = @(
    (Join-Path $rootDir 'writable\bin\ffmpeg.exe'),
    (Join-Path $rootDir 'tools\ffmpeg\bin\ffmpeg.exe')
)
foreach ($p in $candidatePaths) {
    if (Test-Path $p) {
        $ffmpegCmd = $p
        break
    }
}
if (-not $ffmpegCmd) {
    $sysCmd = Get-Command ffmpeg -ErrorAction SilentlyContinue
    if ($sysCmd) {
        $ffmpegCmd = if ($sysCmd.Source) { $sysCmd.Source } else { $sysCmd.Path }
    }
}

function Resolve-TargetCodes {
    param([string]$CamParam, [string]$InterParam)

    if ($CamParam -and $CamParam -ne 'all') {
        $normalized = $CamParam.ToUpper().Trim()
        if ($cameraRegistry.ContainsKey($normalized)) {
            return @($normalized)
        } else {
            Write-Host "Peringatan: Kode kamera '$CamParam' tidak ditemukan dalam registri." -ForegroundColor Red
            return @()
        }
    }

    if ($InterParam -and $InterParam -ne 'all') {
        $matched = @()
        foreach ($k in $cameraRegistry.Keys) {
            if ($cameraRegistry[$k].Loc -eq $InterParam) {
                $matched += $k
            }
        }
        return ($matched | Sort-Object)
    }

    # Default 'all'
    return ($cameraRegistry.Keys | Sort-Object)
}

function Test-RelayProcess {
    param([string]$Code)

    $camDir = Join-Path $cacheBase $Code
    $pidFile = Join-Path $camDir "$Code.pid"
    if (-not (Test-Path $pidFile)) {
        return $null
    }

    $rawId = Get-Content $pidFile -ErrorAction SilentlyContinue
    if (-not $rawId) {
        return $null
    }

    $proc = Get-Process -Id $rawId -ErrorAction SilentlyContinue
    if (-not $proc -or $proc.ProcessName -notlike '*ffmpeg*') {
        Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
        return $null
    }

    return $proc
}

function Show-Status {
    Write-Host "`n=== STATUS RELAY VIVOTEK DISHUB KOTA BANJARBARU ===" -ForegroundColor Cyan
    Write-Host "NVR Host     : $nvrHost"
    Write-Host "NVR RTSP Port: $nvrPort"
    $userDisp = if ($nvrUser) { "$nvrUser (Terkonfigurasi)" } else { "Belum diisi di .env" }
    Write-Host "Kredensial   : $userDisp"
    
    if ($ffmpegCmd) {
        Write-Host "FFmpeg       : Tersedia ($ffmpegCmd)" -ForegroundColor Green
    } else {
        Write-Host "FFmpeg       : TIDAK DITEMUKAN di writable\bin\ffmpeg.exe" -ForegroundColor Yellow
        Write-Host "               Jalankan: powershell scripts\setup_ffmpeg.ps1" -ForegroundColor Gray
    }

    Write-Host "`nRegistri 18 Kamera Banjarbaru:"
    $sortedCodes = $cameraRegistry.Keys | Sort-Object
    foreach ($code in $sortedCodes) {
        $info = $cameraRegistry[$code]
        $proc = Test-RelayProcess -Code $code
        $camDir = Join-Path $cacheBase $code
        $m3u8 = Join-Path $camDir 'index.m3u8'
        $snap = Join-Path $camDir 'snapshot.jpg'

        if (-not $info.Relayable) {
            $statusStr = "[DILEWATI - $($info.Note)]"
            $color = 'DarkYellow'
            $mediaStatus = 'Relay dinonaktifkan'
        } elseif ($proc) {
            $statusStr = "[AKTIF - PID $($proc.Id)]"
            $color = 'Green'
            $m3u8Status = if (Test-Path $m3u8) {
                $age = [int]((Get-Date) - (Get-Item $m3u8).LastWriteTime).TotalSeconds
                "HLS ${age}s lalu"
            } else {
                "HLS inisialisasi"
            }
            $snapStatus = if (Test-Path $snap) {
                $sage = [int]((Get-Date) - (Get-Item $snap).LastWriteTime).TotalSeconds
                "JPEG ${sage}s lalu"
            } else {
                "JPEG belum dibuat"
            }
            $mediaStatus = "$m3u8Status, $snapStatus"
        } else {
            $statusStr = "[BERHENTI]"
            $color = 'DarkGray'
            $mediaStatus = if (Test-Path $m3u8) {
                $age = [int]((Get-Date) - (Get-Item $m3u8).LastWriteTime).TotalSeconds
                "Tersimpan (${age}s lalu)"
            } else {
                "Belum ada segmen"
            }
        }

        Write-Host " - $code ($($info.Name)) : " -NoNewline
        Write-Host "$statusStr " -ForegroundColor $color -NoNewline
        Write-Host "- Kanal C_$($info.Channel) ($($info.Codec)) - $mediaStatus"
    }
    Write-Host ""
}

function Stop-Relay {
    param([string[]]$TargetCodes)

    foreach ($code in $TargetCodes) {
        $proc = Test-RelayProcess -Code $code
        $camDir = Join-Path $cacheBase $code
        $pidFile = Join-Path $camDir "$code.pid"

        if ($proc) {
            try {
                Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
                Write-Host "Relay untuk $code (PID $($proc.Id)) dihentikan." -ForegroundColor Yellow
            } catch {
                Write-Host "Peringatan saat menghentikan PID $($proc.Id): $($_.Exception.Message)" -ForegroundColor DarkYellow
            }
        }
        if (Test-Path $pidFile) {
            Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
        }
    }
}

function Clean-Cache {
    param([string[]]$TargetCodes)
    Write-Host "Membersihkan direktori cache segmen video..." -ForegroundColor Gray
    foreach ($code in $TargetCodes) {
        $camDir = Join-Path $cacheBase $code
        $realCamDir = [System.IO.Path]::GetFullPath($camDir)
        $realCacheBase = [System.IO.Path]::GetFullPath($cacheBase)

        # Validasi target absolut di bawah direktori cache proyek
        if ($realCamDir.StartsWith($realCacheBase) -and (Test-Path $camDir)) {
            Get-ChildItem -Path $camDir -Include '*.ts', '*.m3u8', '*.jpg', '*.tmp' -Recurse -File | Remove-Item -Force -ErrorAction SilentlyContinue
        }
    }
}

function Show-Diag {
    param([string]$TargetCode)
    if (-not $TargetCode) {
        Write-Host "Tentukan kamera untuk diagnostik, contoh: -Action diag -Camera BJB-BRIMOB-F1" -ForegroundColor Yellow
        return
    }

    $camCode = $TargetCode.ToUpper().Trim()
    if (-not $cameraRegistry.ContainsKey($camCode)) {
        Write-Host "Kamera '$camCode' tidak terdaftar." -ForegroundColor Red
        return
    }

    $info = $cameraRegistry[$camCode]
    $camDir = Join-Path $cacheBase $camCode
    $proc = Test-RelayProcess -Code $camCode

    Write-Host "`n=== DIAGNOSTIK RELAY: $camCode ($($info.Name)) ===" -ForegroundColor Cyan
    if ($proc) {
        Write-Host "Status Proses : AKTIF (PID $($proc.Id))" -ForegroundColor Green
    } else {
        Write-Host "Status Proses : BERHENTI / TIDAK AKTIF" -ForegroundColor Yellow
    }

    $logFile = Join-Path $camDir 'ffmpeg.log'
    if (Test-Path $logFile) {
        Write-Host "`nCatatan Log FFmpeg:" -ForegroundColor Gray
        $lines = Get-Content $logFile -Tail 20
        foreach ($l in $lines) {
            $redacted = $l -replace 'rtsp://[^@]+@', 'rtsp://***:***@'
            Write-Host "  $redacted" -ForegroundColor Gray
        }
    }

    # Uji koneksi RTSP langsung ke NVR untuk verifikasi upstream
    if ($nvrUser -and $nvrPass -and $ffmpegCmd) {
        Write-Host "`nPengujian Koneksi RTSP Upstream (NVR ${nvrHost}:${nvrPort}, Kanal C_$($info.Channel))..." -ForegroundColor Cyan
        $chan = $info.Channel
        $streamIdx = $info.Stream
        $auth = [Uri]::EscapeDataString($nvrUser) + ':' + [Uri]::EscapeDataString($nvrPass) + '@'
        $rtspUrl = "rtsp://${auth}${nvrHost}:${nvrPort}/Media/Live/Normal?camera=C_${chan}&streamindex=${streamIdx}"

        $probeOutput = & $ffmpegCmd -hide_banner -loglevel warning -rtsp_transport tcp -timeout 4000000 -i $rtspUrl -t 1 -f null - 2>&1
        $probeStr = ($probeOutput | Out-String).Trim()
        if (-not $probeStr) {
            Write-Host "Hasil Probe   : [OK] Aliran RTSP upstream normal dan merespons." -ForegroundColor Green
        } else {
            $redactedErr = $probeStr -replace 'rtsp://[^@]+@', 'rtsp://***:***@'
            Write-Host "Hasil Probe   : [PERINGATAN/GAGAL]" -ForegroundColor Yellow
            Write-Host "  $redactedErr" -ForegroundColor DarkYellow
            if ($probeStr -like "*462*") {
                Write-Host "Catatan Solusi: NVR merespons 462 Destination Unreachable. Perangkat kamera fisik di persimpangan sedang mati/tidak terhubung ke NVR." -ForegroundColor Red
            }
        }
    }
    Write-Host ""
}

function Start-Relay {
    param([string[]]$TargetCodes)
    
    if (-not $ffmpegCmd -or -not (Test-Path $ffmpegCmd)) {
        Write-Host "Error: FFmpeg tidak ditemukan pada '$ffmpegCmd'." -ForegroundColor Red
        Write-Host "Jalankan: powershell scripts\setup_ffmpeg.ps1 untuk memasang FFmpeg portabel." -ForegroundColor Yellow
        return
    }

    if (-not $nvrUser -or -not $nvrPass) {
        Write-Host "Error: Kredensial vivotek.nvrUsername / vivotek.nvrPassword belum lengkap di .env." -ForegroundColor Red
        return
    }

    foreach ($code in $TargetCodes) {
        $info = $cameraRegistry[$code]
        if (-not $info.Relayable) {
            Write-Host "Melewati ${code}: $($info.Note)" -ForegroundColor DarkYellow
            continue
        }

        # Cek apakah relay untuk kamera ini sudah berjalan (cegah duplikasi proses)
        $existingProc = Test-RelayProcess -Code $code
        if ($existingProc) {
            Write-Host "Relay untuk $code sudah berjalan aktif (PID $($existingProc.Id)). Tidak membuat duplikat." -ForegroundColor Cyan
            continue
        }

        $camDir = Join-Path $cacheBase $code
        if (-not (Test-Path $camDir)) {
            New-Item -ItemType Directory -Force -Path $camDir | Out-Null
        } else {
            # Hapus manifest lama agar tidak terbaca sebagai live sebelum frame baru tiba
            Remove-Item (Join-Path $camDir 'index.m3u8') -Force -ErrorAction SilentlyContinue
        }

        $chan = $info.Channel
        $streamIdx = $info.Stream
        $auth = [Uri]::EscapeDataString($nvrUser) + ':' + [Uri]::EscapeDataString($nvrPass) + '@'
        $rtspUrl = "rtsp://${auth}${nvrHost}:${nvrPort}/Media/Live/Normal?camera=C_${chan}&streamindex=${streamIdx}"
        
        $outFile    = 'index.m3u8'
        $snapFile   = 'snapshot.jpg'
        $segPattern = 'seg_%03d.ts'

        # Pilihan transcode video: H.264 copy passthrough, H.265 transcode ultrafast ke H.264 dengan repeat-headers
        $hlsCodec = if ($info.Codec -eq 'H.265') {
            @('-c:v', 'libx264', '-preset', 'ultrafast', '-tune', 'zerolatency', '-crf', '26', '-x264-params', 'repeat-headers=1:keyint=30:min-keyint=30:scenecut=0')
        } else {
            @('-c:v', 'copy')
        }

        # Format argumen terisolasi per output (-map 0:v) untuk HLS dan Snapshot
        $rawArgs = @(
            '-hide_banner',
            '-loglevel', 'warning',
            '-nostdin',
            '-rtsp_transport', 'tcp',
            '-timeout', '8000000',
            '-i', "`"$rtspUrl`"",
            '-map', '0:v'
        ) + $hlsCodec + @(
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

        # Pengutipan nilai argumen berspasi sesuai format command-line Windows
        $argString = ($rawArgs | ForEach-Object {
            if ($_ -match '\s' -and -not ($_.StartsWith('"') -and $_.EndsWith('"'))) {
                '"' + ($_ -replace '"', '\"') + '"'
            } else {
                $_
            }
        }) -join ' '

        # Pindah ke direktori kamera sebelum menjalankan FFmpeg
        $pushd = Get-Location
        Set-Location $camDir

        try {
            $fullCmd = "`"$ffmpegCmd`" $argString"
            
            # Eksekusi via CIM/WMI detached agar proses terlepas dari terminal dan hidup mandiri
            $wmi = $null
            try {
                $wmi = Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{
                    CommandLine      = $fullCmd
                    CurrentDirectory = $camDir
                }
            } catch {
                $wmi = Invoke-WmiMethod -Class Win32_Process -Name Create -ArgumentList $fullCmd
            }
            
            Start-Sleep -Milliseconds 1500
            
            if ($wmi.ReturnValue -eq 0 -and $wmi.ProcessId) {
                $proc = Get-Process -Id $wmi.ProcessId -ErrorAction SilentlyContinue
                if ($proc -and -not $proc.HasExited) {
                    Set-Content (Join-Path $camDir "$code.pid") $proc.Id
                    Write-Host "Relay dimulai: $code ($($info.Name), Kanal C_${chan}) [PID $($proc.Id)]" -ForegroundColor Green
                } else {
                    Write-Host "FFmpeg gagal bertahan untuk ${code}. Periksa log diagnostik (-Action diag -Camera ${code})." -ForegroundColor Red
                }
            } else {
                Write-Host "Gagal memanggil WMI Win32_Process. ReturnValue: $($wmi.ReturnValue)" -ForegroundColor Red
            }
        } catch {
            $errMsg = $_.Exception.Message -replace 'rtsp://[^@]+@', 'rtsp://***:***@'
            Write-Host "Gagal memulai relay untuk ${code}: $errMsg" -ForegroundColor Red
        } finally {
            Set-Location $pushd
        }
    }
}

$targets = Resolve-TargetCodes -CamParam $Camera -InterParam $Intersection

switch ($Action) {
    'status'  { Show-Status }
    'stop'    { Stop-Relay -TargetCodes $targets; Show-Status }
    'start'   { Start-Relay -TargetCodes $targets; Show-Status }
    'restart' { Stop-Relay -TargetCodes $targets; Start-Relay -TargetCodes $targets; Show-Status }
    'clean'   { Stop-Relay -TargetCodes $targets; Clean-Cache -TargetCodes $targets; Show-Status }
    'diag'    { Show-Diag -TargetCode $Camera }
}
