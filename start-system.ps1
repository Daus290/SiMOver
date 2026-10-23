<#
.SYNOPSIS
    Script Startup Satu Perintah: Localhost Web Server, Database, dan Seluruh CCTV Relay
.DESCRIPTION
    Menyalakan seluruh komponen Sistem Monitoring Over Dimension Dishub Banjarbaru:
    1. Memverifikasi dependensi (PHP XAMPP, FFmpeg, skrip relay, konfigurasi .env).
    2. Menyalakan database MariaDB/MySQL lokal XAMPP jika belum berjalan.
    3. Menyalakan web server CodeIgniter 4 (port 8080) di latar belakang (tanpa jendela popup).
    4. Menguji keterjangkauan NVR (10.0.1.3:8554) di jaringan kantor.
    5. Menyalakan relay HLS untuk seluruh 16 kamera yang tersedia via scripts\vivotek_relay.ps1.
    6. Memberikan ringkasan kesiapan sistem dan mengembalikan kontrol ke terminal secara instan.
.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File .\start-system.ps1
    powershell -NoProfile -ExecutionPolicy Bypass -File .\start-system.ps1 -Action status
    powershell -NoProfile -ExecutionPolicy Bypass -File .\start-system.ps1 -Action stop
#>

param(
    [ValidateSet('start', 'stop', 'status', 'restart')]
    [string]$Action = 'start'
)

# 1. Determinasi Path Root Proyek
$rootDir = $PSScriptRoot
if (-not $rootDir) {
    $rootDir = (Get-Location).Path
}

$systemCacheDir = Join-Path $rootDir 'writable\cache\system'
$logsDir        = Join-Path $rootDir 'writable\logs'
$vivotekCache   = Join-Path $rootDir 'writable\cache\vivotek_stream'
$sparkPath      = Join-Path $rootDir 'spark'
$relayScript    = Join-Path $rootDir 'scripts\vivotek_relay.ps1'
$envFile        = Join-Path $rootDir '.env'
$serveLog       = Join-Path $logsDir 'spark_serve.log'
$servePidFile   = Join-Path $systemCacheDir 'spark_serve.pid'
$lockFile       = Join-Path $systemCacheDir 'start-system.lock'
$mysqlPidFile   = Join-Path $systemCacheDir 'mysqld.pid'

# Pastikan folder runtime lokal tersedia
if (-not (Test-Path $systemCacheDir)) {
    New-Item -ItemType Directory -Force -Path $systemCacheDir | Out-Null
}
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Force -Path $logsDir | Out-Null
}

# 2. Mekanisme Mutex / Lock Idempoten
function Acquire-StartupLock {
    if (Test-Path $lockFile) {
        $existingPid = Get-Content $lockFile -ErrorAction SilentlyContinue
        if ($existingPid) {
            $lockProc = Get-Process -Id $existingPid -ErrorAction SilentlyContinue
            if ($lockProc -and ($lockProc.ProcessName -like '*powershell*' -or $lockProc.ProcessName -like '*pwsh*')) {
                Write-Host "[INFO] Proses startup lain sedang berlangsung (PID $existingPid)." -ForegroundColor Yellow
                Write-Host "Harap tunggu hingga proses tersebut selesai atau hentikan jika macet.`n" -ForegroundColor Yellow
                return $false
            }
        }
        # Hapus lock basi jika proses lama sudah mati
        Remove-Item $lockFile -Force -ErrorAction SilentlyContinue
    }

    Set-Content -Path $lockFile -Value $PID -Force
    return $true
}

function Release-StartupLock {
    if (Test-Path $lockFile) {
        Remove-Item $lockFile -Force -ErrorAction SilentlyContinue
    }
}

# 3. Deteksi PHP Executable
function Find-PhpExecutable {
    $candidates = @(
        'D:\Apps\xampp\php\php.exe',
        'C:\xampp\php\php.exe'
    )
    foreach ($cand in $candidates) {
        if (Test-Path $cand) {
            return $cand
        }
    }
    $sysCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($sysCmd) {
        $p = if ($sysCmd.Source) { $sysCmd.Source } else { $sysCmd.Path }
        if (Test-Path $p) { return $p }
    }
    return $null
}

# 4. Deteksi FFmpeg Portabel
function Find-FFmpegExecutable {
    $candidates = @(
        (Join-Path $rootDir 'writable\bin\ffmpeg.exe'),
        (Join-Path $rootDir 'tools\ffmpeg\bin\ffmpeg.exe')
    )
    foreach ($cand in $candidates) {
        if (Test-Path $cand) {
            return $cand
        }
    }
    $sysCmd = Get-Command ffmpeg -ErrorAction SilentlyContinue
    if ($sysCmd) {
        $p = if ($sysCmd.Source) { $sysCmd.Source } else { $sysCmd.Path }
        if (Test-Path $p) { return $p }
    }
    return $null
}

# 5. Baca Konfigurasi .env Secara Privat (Tanpa Mengekspos Sandi)
function Get-SafeEnvConfig {
    $cfg = @{}
    if (Test-Path $envFile) {
        Get-Content $envFile | ForEach-Object {
            $line = $_.Trim()
            if ($line -and -not $line.StartsWith('#') -and $line.Contains('=')) {
                $parts = $line.Split('=', 2)
                $k = $parts[0].Trim()
                $v = $parts[1].Trim().Trim("'").Trim('"')
                $cfg[$k] = $v
            }
        }
    }
    return $cfg
}

# 6. Uji Koneksi Port TCP (Timeout Terbatas)
function Test-TcpPort {
    param(
        [string]$HostName,
        [int]$Port,
        [int]$TimeoutMs = 2500
    )
    $tcp = New-Object System.Net.Sockets.TcpClient
    try {
        $ar = $tcp.BeginConnect($HostName, $Port, $null, $null)
        if ($ar.AsyncWaitHandle.WaitOne($TimeoutMs, $false) -and $tcp.Connected) {
            $tcp.EndConnect($ar)
            return $true
        }
    } catch {
        return $false
    } finally {
        $tcp.Close()
    }
    return $false
}

# 7. Deteksi Instance XAMPP MySQL
function Find-XamppMysql {
    $candidates = @(
        'D:\Apps\xampp\mysql\bin\mysqld.exe',
        'C:\xampp\mysql\bin\mysqld.exe'
    )
    foreach ($cand in $candidates) {
        if (Test-Path $cand) {
            $binDir   = Split-Path $cand           # C:\xampp\mysql\bin
            $mysqlDir = Split-Path $binDir         # C:\xampp\mysql
            $xamppDir = Split-Path $mysqlDir       # C:\xampp
            $myIni    = Join-Path $binDir 'my.ini' # C:\xampp\mysql\bin\my.ini
            $dataDir  = Join-Path $mysqlDir 'data' # C:\xampp\mysql\data
            return @{
                Exe      = $cand
                Ini      = $myIni
                Xampp    = $xamppDir
                MysqlDir = $mysqlDir
                Data     = $dataDir
            }
        }
    }
    return $null
}

# 8. Menyalakan & Memverifikasi Database MySQL
function Start-LocalDatabase {
    param([int]$Port = 3306)

    # Cek apakah port 3306 sudah aktif merespons
    if (Test-TcpPort -HostName '127.0.0.1' -Port $Port -TimeoutMs 1500) {
        return @{
            Success = $true
            Status  = 'sudah berjalan'
            Detail  = "MySQL/MariaDB port $Port aktif dan siap digunakan"
        }
    }

    $mysqlInfo = Find-XamppMysql
    if (-not $mysqlInfo) {
        return @{
            Success = $false
            Status  = 'tidak ditemukan'
            Detail  = 'Instance XAMPP mysqld.exe tidak ditemukan di direktori default.'
        }
    }

    if (-not (Test-Path $mysqlInfo.Ini)) {
        return @{
            Success = $false
            Status  = 'konfigurasi hilang'
            Detail  = "File konfigurasi $($mysqlInfo.Ini) tidak ditemukan."
        }
    }

    Write-Host "-> Menyalakan MySQL/MariaDB XAMPP di latar belakang..." -ForegroundColor Cyan
    try {
        $proc = Start-Process -FilePath $mysqlInfo.Exe `
            -ArgumentList "--defaults-file=`"$($mysqlInfo.Ini)`" --standalone" `
            -WorkingDirectory $mysqlInfo.Xampp `
            -WindowStyle Hidden `
            -PassThru
        $startedPid = $proc.Id
    } catch {
        return @{
            Success = $false
            Status  = 'gagal eksekusi'
            Detail  = "Gagal memanggil proses mysqld: $($_.Exception.Message)"
        }
    }

    # Tunggu kesiapan TCP port 3306 hingga 15 detik
    $ready = $false
    for ($i = 0; $i -lt 30; $i++) {
        Start-Sleep -Milliseconds 500
        if (Test-TcpPort -HostName '127.0.0.1' -Port $Port -TimeoutMs 500) {
            $ready = $true
            break
        }
    }

    if ($ready) {
        if ($startedPid) {
            Set-Content -Path $mysqlPidFile -Value $startedPid -Force
        }
        return @{
            Success = $true
            Status  = 'siap'
            Detail  = "MySQL/MariaDB berhasil dinyalakan (port $Port)"
        }
    } else {
        return @{
            Success = $false
            Status  = 'gagal merespons'
            Detail  = "mysqld dijalankan tetapi port $Port belum merespons dalam 15 detik. Periksa $($mysqlInfo.Data)\mysql_error.log"
        }
    }
}

# 9. Memeriksa Status Server Web Lokal (Port 8080)
function Test-WebLocalServer {
    param([int]$Port = 8080)

    $conn = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
    if (-not $conn) {
        return @{
            Listening   = $false
            OwningPid   = $null
            ProcessName = ''
            IsOurServer = $false
            HttpOk      = $false
        }
    }

    $owningPid = $conn.OwningProcess
    $proc = Get-Process -Id $owningPid -ErrorAction SilentlyContinue
    $procName = if ($proc) { $proc.ProcessName } else { 'Unknown' }

    # Periksa command line proses via CIM
    $wmiProc = Get-CimInstance Win32_Process -Filter "ProcessId = $owningPid" -ErrorAction SilentlyContinue
    $cmdLine = if ($wmiProc) { $wmiProc.CommandLine } else { '' }

    $isOurServer = $false
    if ($procName -like '*php*' -and ($cmdLine -like "*$rootDir*" -or $cmdLine -like "*spark*")) {
        $isOurServer = $true
    }

    # Uji respons HTTP
    $httpOk = $false
    try {
        $res = Invoke-WebRequest -Uri "http://localhost:$Port/live-cctv" -UseBasicParsing -TimeoutSec 4
        if ($res.StatusCode -eq 200) {
            $httpOk = $true
            if ($res.Content -like '*Dishub*' -or $res.Content -like '*Sistem Monitoring*') {
                $isOurServer = $true
            }
        }
    } catch {
        try {
            $res2 = Invoke-WebRequest -Uri "http://localhost:$Port/" -UseBasicParsing -TimeoutSec 3
            if ($res2.StatusCode -eq 200 -and ($res2.Content -like '*Dishub*' -or $res2.Content -like '*Monitoring*')) {
                $httpOk = $true
                $isOurServer = $true
            }
        } catch {}
    }

    return @{
        Listening   = $true
        OwningPid   = $owningPid
        ProcessName = $procName
        CommandLine = $cmdLine
        IsOurServer = $isOurServer
        HttpOk      = $httpOk
    }
}

# 10. Menyalakan Server Web CodeIgniter 4
function Start-LocalWebServer {
    param([string]$PhpExe, [int]$Port = 8080)

    $check = Test-WebLocalServer -Port $Port
    if ($check.Listening) {
        if ($check.IsOurServer) {
            Set-Content -Path $servePidFile -Value $check.OwningPid -Force
            return @{
                Success = $true
                Status  = 'sudah berjalan'
                Detail  = "Server CodeIgniter 4 aktif (PID $($check.OwningPid))"
                Pid     = $check.OwningPid
            }
        } else {
            return @{
                Success = $false
                Status  = 'konflik_port'
                Detail  = "Port $Port digunakan oleh aplikasi lain (PID $($check.OwningPid), $($check.ProcessName)). Server proyek tidak dijalankan untuk mencegah konflik."
                Pid     = $check.OwningPid
            }
        }
    }

    # Bersihkan sisa proses spark serve yatim milik proyek ini jika port 8080 belum aktif
    $staleSpark = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -like "*$rootDir*" -and ($_.CommandLine -like "*spark*serve*" -or $_.CommandLine -like "*-S*localhost:*")
    }
    foreach ($sp in $staleSpark) {
        Stop-Process -Id $sp.ProcessId -Force -ErrorAction SilentlyContinue
    }

    # Pastikan port tidak sedang dalam status TIME_WAIT
    $timeWait = Get-NetTCPConnection -LocalPort $Port -State TimeWait -ErrorAction SilentlyContinue
    if ($timeWait) {
        Write-Host "-> Port $Port dalam status TIME_WAIT, menunggu pelepasan soket..." -ForegroundColor Yellow
        for ($w = 0; $w -lt 10; $w++) {
            Start-Sleep -Seconds 1
            if (-not (Get-NetTCPConnection -LocalPort $Port -State TimeWait -ErrorAction SilentlyContinue)) {
                break
            }
        }
    }

    Write-Host "-> Menyalakan server web CodeIgniter 4 (port $Port, multi-worker)..." -ForegroundColor Cyan
    $cmdLine = "cmd.exe /c `"set PHP_CLI_SERVER_WORKERS=4&& `"$PhpExe`" spark serve --host 127.0.0.1 --port $Port > `"$serveLog`" 2>&1`""

    try {
        $wmi = Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{
            CommandLine      = $cmdLine
            CurrentDirectory = $rootDir
        }
        if ($wmi.ReturnValue -ne 0) {
            $wmi = Invoke-WmiMethod -Class Win32_Process -Name Create -ArgumentList $cmdLine
        }
    } catch {
        return @{
            Success = $false
            Status  = 'gagal eksekusi'
            Detail  = "Gagal meluncurkan spark serve: $($_.Exception.Message)"
        }
    }

    # Polling HTTP readiness hingga 15 detik
    $httpReady = $false
    $actualPid = $null
    for ($i = 0; $i -lt 15; $i++) {
        Start-Sleep -Seconds 1
        $checkNow = Test-WebLocalServer -Port $Port
        if ($checkNow.Listening -and $checkNow.IsOurServer -and $checkNow.HttpOk) {
            $httpReady = $true
            $actualPid = $checkNow.OwningPid
            break
        }
    }

    if ($httpReady) {
        Set-Content -Path $servePidFile -Value $actualPid -Force
        return @{
            Success = $true
            Status  = 'siap'
            Detail  = "Server CodeIgniter 4 siap merespons (PID $actualPid)"
            Pid     = $actualPid
        }
    } else {
        # Bersihkan jika spark serve keliru meloncat ke port offset
        for ($offsetPort = ($Port + 1); $offsetPort -le ($Port + 3); $offsetPort++) {
            $offsetConn = Get-NetTCPConnection -LocalPort $offsetPort -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
            if ($offsetConn) {
                $offPid = $offsetConn.OwningProcess
                $offProc = Get-CimInstance Win32_Process -Filter "ProcessId = $offPid" -ErrorAction SilentlyContinue
                if ($offProc -and $offProc.CommandLine -like "*$rootDir*") {
                    Stop-Process -Id $offPid -Force -ErrorAction SilentlyContinue
                }
            }
        }

        $logTail = if (Test-Path $serveLog) { (Get-Content $serveLog -Tail 5) -join '; ' } else { 'Belum ada log' }
        return @{
            Success = $false
            Status  = 'gagal merespons'
            Detail  = "Server belum memberikan respons HTTP 200 pada port $Port dalam 15 detik. Log: $logTail"
        }
    }
}

# 11. Menjalankan Relay CCTV via Skrip Pengelola
function Start-CctvRelays {
    param([string]$RelayScriptPath)
    Write-Host "-> Menjalankan pengelola relay CCTV (scripts\vivotek_relay.ps1)..." -ForegroundColor Cyan
    & powershell -NoProfile -ExecutionPolicy Bypass -File "$RelayScriptPath" -Action start
}

# 12. Evaluasi Status Kesehatan Media CCTV
function Get-CctvHealthSummary {
    $sortedCodes = @(
        'BJB-ROULIN-F1', 'BJB-ROULIN-F2', 'BJB-ROULIN-F3', 'BJB-ROULIN-PTZ',
        'BJB-BRIMOB-F1', 'BJB-BRIMOB-F2', 'BJB-BRIMOB-F3', 'BJB-BRIMOB-PTZ',
        'BJB-SAMSAT-F1', 'BJB-SAMSAT-F2', 'BJB-SAMSAT-F3', 'BJB-SAMSAT-F4', 'BJB-SAMSAT-PTZ',
        'BJB-ADIPURA-F1', 'BJB-ADIPURA-F2', 'BJB-ADIPURA-F3', 'BJB-ADIPURA-F4', 'BJB-ADIPURA-PTZ'
    )
    $nonRelayable = @('BJB-ROULIN-F3', 'BJB-SAMSAT-F1')

    $ready = 0
    $waiting = 0
    $unavailable = 0
    $stopped = 0

    foreach ($code in $sortedCodes) {
        if ($nonRelayable -contains $code) {
            $unavailable++
            continue
        }

        $camDir = Join-Path $vivotekCache $code
        $pidFile = Join-Path $camDir "$code.pid"
        $m3u8 = Join-Path $camDir 'index.m3u8'

        $isProcActive = $false
        if (Test-Path $pidFile) {
            $procId = Get-Content $pidFile -ErrorAction SilentlyContinue
            if ($procId) {
                $p = Get-Process -Id $procId -ErrorAction SilentlyContinue
                if ($p -and $p.ProcessName -like '*ffmpeg*') {
                    $isProcActive = $true
                }
            }
        }

        if (-not $isProcActive) {
            $stopped++
            continue
        }

        if (Test-Path $m3u8) {
            $age = [int]((Get-Date) - (Get-Item $m3u8).LastWriteTime).TotalSeconds
            if ($age -le 25) {
                $ready++
            } else {
                $waiting++
            }
        } else {
            $waiting++
        }
    }

    return @{
        Ready       = $ready
        Waiting     = $waiting
        Unavailable = $unavailable
        Stopped     = $stopped
        Total       = $sortedCodes.Count
    }
}

# 12. Menampilkan Ringkasan Sistem
function Show-SystemSummary {
    param(
        [hashtable]$WebResult,
        [hashtable]$DbResult,
        [string]$NvrStatus,
        [string]$NvrHost,
        [string]$NvrPort,
        [hashtable]$CctvResult
    )

    Write-Host "`n============================================================" -ForegroundColor Cyan
    Write-Host "   RINGKASAN SISTEM MONITORING OVER DIMENSION DISHUB" -ForegroundColor Cyan
    Write-Host "============================================================" -ForegroundColor Cyan

    # 1. Website
    $webColor = if ($WebResult.Success) { 'Green' } else { 'Red' }
    Write-Host "Website   : " -NoNewline
    Write-Host "$($WebResult.Status.ToUpper()) " -ForegroundColor $webColor -NoNewline
    Write-Host "($($WebResult.Detail))"
    Write-Host "Alamat    : " -NoNewline
    Write-Host "http://localhost:8080/live-cctv" -ForegroundColor Yellow

    # 2. Database
    $dbColor = if ($DbResult.Success) { 'Green' } else { 'Yellow' }
    Write-Host "Database  : " -NoNewline
    Write-Host "$($DbResult.Status.ToUpper()) " -ForegroundColor $dbColor -NoNewline
    Write-Host "($($DbResult.Detail))"

    # 3. NVR
    $nvrColor = if ($NvrStatus -like '*dapat diakses*') { 'Green' } else { 'Red' }
    Write-Host "NVR       : " -NoNewline
    Write-Host "$($NvrStatus.ToUpper()) " -ForegroundColor $nvrColor -NoNewline
    Write-Host "(${nvrHost}:${nvrPort})"

    # 4. CCTV
    Write-Host "CCTV      : " -NoNewline
    if ($CctvResult) {
        $cctvColor = if ($CctvResult.Ready -gt 0) { 'Green' } else { 'Yellow' }
        Write-Host "$($CctvResult.Ready) sumber siap, " -ForegroundColor $cctvColor -NoNewline
        Write-Host "$($CctvResult.Waiting) menunggu media, " -ForegroundColor Cyan -NoNewline
        Write-Host "$($CctvResult.Unavailable) tidak tersedia (RO Ulin F3 & Samsat F1)" -ForegroundColor DarkYellow
    } else {
        Write-Host "Dilewati (NVR belum terjangkau)" -ForegroundColor Yellow
    }

    # 5. Status AI Worker
    $aiRunning = $false
    $workerPidFile = Join-Path $rootDir "ai_worker\worker.pid"
    if (Test-Path $workerPidFile) {
        $wPid = Get-Content $workerPidFile -ErrorAction SilentlyContinue
        if ($wPid) {
            $wProc = Get-Process -Id $wPid -ErrorAction SilentlyContinue
            if ($wProc -and $wProc.ProcessName -like '*python*') { $aiRunning = $true }
        }
    }
    if (-not $aiRunning) {
        $pyProcs = Get-CimInstance Win32_Process -Filter "Name = 'python.exe'" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*ai_worker.worker*" }
        if ($pyProcs) { $aiRunning = $true }
    }
    $aiColor = if ($aiRunning) { 'Green' } else { 'Yellow' }
    Write-Host "AI Worker : " -NoNewline
    Write-Host (if ($aiRunning) { "AKTIF (Kamera: Jl. Gubernur Soebardjo)" } else { "TIDAK AKTIF (Jalankan .\start-ai.bat)" }) -ForegroundColor $aiColor

    # 6. Lokasi Log & Cache
    Write-Host "Log Web   : writable\logs\spark_serve.log" -ForegroundColor Gray
    Write-Host "============================================================`n" -ForegroundColor Cyan
}

# 13. Fungsi Menghentikan Sistem
function Stop-EntireSystem {
    Write-Host "`n=== MENGHENTIKAN SISTEM MONITORING DISHUB ===" -ForegroundColor Yellow

    # 0. Hentikan AI Worker
    $aiScript = Join-Path $rootDir 'scripts\ai_worker.ps1'
    if (Test-Path $aiScript) {
        Write-Host "-> Menghentikan AI Worker..." -ForegroundColor Cyan
        & powershell -NoProfile -ExecutionPolicy Bypass -File "$aiScript" stop
    }

    # 1. Hentikan Relay CCTV
    if (Test-Path $relayScript) {
        Write-Host "-> Menghentikan seluruh relay CCTV..." -ForegroundColor Cyan
        & powershell -NoProfile -ExecutionPolicy Bypass -File "$relayScript" -Action stop
    }

    # 2. Hentikan Server Web CodeIgniter 4 (Parent & Child milik proyek ini)
    Write-Host "-> Menghentikan server web CodeIgniter 4..." -ForegroundColor Cyan
    $phpProcs = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -like "*$rootDir*" -and ($_.CommandLine -like "*spark*serve*" -or $_.CommandLine -like "*-S*localhost:*")
    }
    foreach ($p in $phpProcs) {
        Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
    }
    if (Test-Path $servePidFile) {
        Remove-Item $servePidFile -Force -ErrorAction SilentlyContinue
    }

    Write-Host "Sistem berhasil dihentikan.`n" -ForegroundColor Green
}

# ============================================================
# EKSEKUSI UTAMA (MAIN ENTRY POINT)
# ============================================================

if ($Action -eq 'stop') {
    Stop-EntireSystem
    exit 0
}

if ($Action -eq 'status') {
    $webCheck = Test-WebLocalServer -Port 8080
    $webRes = @{
        Success = ($webCheck.Listening -and $webCheck.IsOurServer -and $webCheck.HttpOk)
        Status  = if ($webCheck.Listening) { 'aktif' } else { 'berhenti' }
        Detail  = if ($webCheck.Listening) { "PID $($webCheck.OwningPid)" } else { "Port 8080 tidak aktif" }
    }
    $dbListening = Test-TcpPort -HostName '127.0.0.1' -Port 3306 -TimeoutMs 1000
    $dbRes = @{
        Success = $dbListening
        Status  = if ($dbListening) { 'aktif' } else { 'berhenti' }
        Detail  = if ($dbListening) { 'Port 3306 merespons' } else { 'Port 3306 tidak aktif' }
    }
    $cfg = Get-SafeEnvConfig
    $nvrHost = if ($cfg['vivotek.nvrHost']) { $cfg['vivotek.nvrHost'] } else { '10.0.1.3' }
    $nvrPort = if ($cfg['vivotek.nvrRtspPort']) { $cfg['vivotek.nvrRtspPort'] } else { '8554' }
    $nvrReachable = Test-TcpPort -HostName $nvrHost -Port ([int]$nvrPort) -TimeoutMs 2000
    $nvrStatus = if ($nvrReachable) { 'dapat diakses' } else { 'belum dapat diakses' }
    $cctvHealth = Get-CctvHealthSummary

    Show-SystemSummary -WebResult $webRes -DbResult $dbRes -NvrStatus $nvrStatus -NvrHost $nvrHost -NvrPort $nvrPort -CctvResult $cctvHealth
    exit 0
}

if ($Action -eq 'restart') {
    Stop-EntireSystem
    Start-Sleep -Seconds 2
}

# --- PROSES STARTUP UTAMA ---
if (-not (Acquire-StartupLock)) {
    exit 1
}

try {
    Write-Host "`n============================================================" -ForegroundColor Cyan
    Write-Host "   MEMULAI SISTEM MONITORING OVER DIMENSION DISHUB" -ForegroundColor Cyan
    Write-Host "============================================================" -ForegroundColor Cyan

    # A. Pemeriksaan Awal Komponen
    Write-Host "-> Memeriksa komponen sistem..." -ForegroundColor Cyan

    if (-not (Test-Path $sparkPath)) {
        Write-Host "[ERROR] Berkas 'spark' tidak ditemukan di root proyek ($sparkPath)." -ForegroundColor Red
        return
    }

    if (-not (Test-Path $relayScript)) {
        Write-Host "[ERROR] Skrip relay tidak ditemukan di ($relayScript)." -ForegroundColor Red
        return
    }

    $phpExe = Find-PhpExecutable
    if (-not $phpExe) {
        Write-Host "[ERROR] PHP tidak ditemukan di D:\Apps\xampp\php\php.exe maupun sistem PATH." -ForegroundColor Red
        Write-Host "Tindakan: Pastikan XAMPP terpasang atau PHP ditambahkan ke PATH." -ForegroundColor Yellow
        return
    }

    $ffmpegExe = Find-FFmpegExecutable
    if (-not $ffmpegExe) {
        Write-Host "[ERROR] FFmpeg portabel tidak ditemukan di writable\bin\ffmpeg.exe." -ForegroundColor Red
        Write-Host "Tindakan: Jalankan 'powershell scripts\setup_ffmpeg.ps1' untuk mengunduh FFmpeg portabel." -ForegroundColor Yellow
        return
    }

    $cfg = Get-SafeEnvConfig
    $nvrHost = if ($cfg['vivotek.nvrHost']) { $cfg['vivotek.nvrHost'] } else { '10.0.1.3' }
    $nvrPort = if ($cfg['vivotek.nvrRtspPort']) { $cfg['vivotek.nvrRtspPort'] } else { '8554' }

    Write-Host "   PHP     : $phpExe" -ForegroundColor Gray
    Write-Host "   FFmpeg  : $ffmpegExe" -ForegroundColor Gray
    Write-Host "   NVR Host: $nvrHost (Port $nvrPort)" -ForegroundColor Gray

    # B. Database MySQL / MariaDB Lokal
    $dbResult = Start-LocalDatabase -Port 3306

    # C. Web Server CodeIgniter 4 (Port 8080)
    $webResult = Start-LocalWebServer -PhpExe $phpExe -Port 8080
    if (-not $webResult.Success -and $webResult.Status -eq 'konflik_port') {
        Write-Host "[PERINGATAN] $($webResult.Detail)" -ForegroundColor Red
    }

    # D. Keterjangkauan NVR & Relay CCTV
    Write-Host "-> Menguji koneksi ke NVR Vivotek (${nvrHost}:${nvrPort})..." -ForegroundColor Cyan
    $nvrReachable = Test-TcpPort -HostName $nvrHost -Port ([int]$nvrPort) -TimeoutMs 2500
    $cctvHealth = $null

    if ($nvrReachable) {
        Write-Host "   Koneksi NVR berhasil (Jaringan kantor aktif)." -ForegroundColor Green
        $nvrStatus = 'dapat diakses'

        # Jalankan relay
        Start-CctvRelays -RelayScriptPath $relayScript

        # Tunggu 3 detik untuk inisialisasi media awal
        Start-Sleep -Seconds 3
        $cctvHealth = Get-CctvHealthSummary
    } else {
        $nvrStatus = 'belum dapat diakses'
        Write-Host ""
        Write-Host "============================================================" -ForegroundColor Yellow
        Write-Host "Website lokal berjalan. CCTV belum dapat diakses." -ForegroundColor Yellow
        Write-Host "Hubungkan laptop ke jaringan kantor lalu jalankan perintah yang sama." -ForegroundColor Yellow
        Write-Host "============================================================" -ForegroundColor Yellow
    }

    # E. AI Worker Multi-Kamera
    Write-Host "-> Menyalakan AI Worker (Deteksi Kendaraan Niaga & Over Dimension)..." -ForegroundColor Cyan
    $aiScript = Join-Path $rootDir 'scripts\ai_worker.ps1'
    if (Test-Path $aiScript) {
        & powershell -NoProfile -ExecutionPolicy Bypass -File "$aiScript" start
    } else {
        $sysPython = Get-Command python -ErrorAction SilentlyContinue
        if ($sysPython) {
            $pyPath = if ($sysPython.Source) { $sysPython.Source } else { $sysPython.Definition }
            Start-Process -FilePath $pyPath -ArgumentList "-u", "-m", "ai_worker.worker", "--camera", "soebardjo" -WorkingDirectory $rootDir -WindowStyle Hidden
        }
    }

    # F. Tampilkan Ringkasan Akhir
    Show-SystemSummary -WebResult $webResult -DbResult $dbResult -NvrStatus $nvrStatus -NvrHost $nvrHost -NvrPort $nvrPort -CctvResult $cctvHealth

} finally {
    Release-StartupLock
}
