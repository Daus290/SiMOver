# ==============================================================================
# Script Manajemen SiMover AI Worker (PowerShell)
# Dinas Perhubungan Kota Banjarbaru
# ==============================================================================
param (
    [Parameter(Mandatory = $false, Position = 0)]
    [ValidateSet("start", "stop", "status", "restart")]
    [string]$Action = "status"
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent $scriptDir
$workerDir = Join-Path $projectRoot "ai_worker"
$pythonExe = Join-Path $workerDir ".venv\Scripts\python.exe"
$pidFile = Join-Path $workerDir "worker.pid"
$logFile = Join-Path $workerDir "worker.log"
$errFile = Join-Path $workerDir "worker_err.log"

function Get-WorkerPid {
    if (Test-Path $pidFile) {
        $p = Get-Content $pidFile -ErrorAction SilentlyContinue | Out-String
        $p = $p.Trim()
        if ($p -match '^\d+$') {
            return [int]$p
        }
    }
    return $null
}

# Proses dianggap worker hanya bila executable = python venv proyek DAN command line memuat ai_worker.worker.
# Ini mencegah menghentikan proses lain ketika PID file usang dipakai ulang oleh Windows.
function Is-ProcessAlive([int]$procId) {
    $p = Get-CimInstance Win32_Process -Filter "ProcessId=$procId" -ErrorAction SilentlyContinue
    if (-not $p) { return $false }
    $exeOk = $p.ExecutablePath -and ([IO.Path]::GetFullPath($p.ExecutablePath) -ieq [IO.Path]::GetFullPath($pythonExe))
    return ($exeOk -and $p.CommandLine -match 'ai_worker\.worker')
}

# Baca satu kunci dari .env proyek tanpa mencetak nilainya
function Get-DotEnvValue([string]$key) {
    $envFile = Join-Path $projectRoot ".env"
    if (-not (Test-Path $envFile)) { return "" }
    $line = Get-Content $envFile | Where-Object { $_ -match "^\s*$([regex]::Escape($key))\s*=" } | Select-Object -First 1
    if (-not $line) { return "" }
    return (($line -split '=', 2)[1]).Trim().Trim('"').Trim("'")
}

switch ($Action.ToLower()) {
    "start" {
        Write-Host "`n=== [SIMOVER] MEMULAI AI WORKER ===" -ForegroundColor Cyan
        $existingPid = Get-WorkerPid
        if ($existingPid -and (Is-ProcessAlive $existingPid)) {
            Write-Host "Worker sudah berjalan dengan PID: $existingPid" -ForegroundColor Yellow
            return
        }

        if (-not (Test-Path $pythonExe)) {
            Write-Host "Error: Python virtual environment tidak ditemukan di $pythonExe" -ForegroundColor Red
            return
        }

        $token = Get-DotEnvValue "AI_INGEST_TOKEN"
        if (-not $token) {
            Write-Host "Error: AI_INGEST_TOKEN belum diisi di .env. Worker tidak dijalankan." -ForegroundColor Red
            return
        }
        $env:AI_INGEST_TOKEN = $token

        Write-Host "Catatan: worker ini buatan Antigravity (Prompt 52), BUKAN core Rizkiya. Datanya ditandai uji lama." -ForegroundColor Yellow
        Write-Host "Menjalankan worker di background..." -ForegroundColor Green
        $process = Start-Process -FilePath $pythonExe -ArgumentList @("-u", "-m", "ai_worker.worker") -WorkingDirectory $projectRoot -PassThru -WindowStyle Hidden

        Start-Sleep -Seconds 3

        $activePid = Get-WorkerPid
        if ($activePid -and (Is-ProcessAlive $activePid)) {
            Write-Host "AI Worker berhasil dijalankan! (PID: $activePid)" -ForegroundColor Green
            Write-Host "Log file: $logFile" -ForegroundColor Gray
        } else {
            Write-Host "Worker sedang menginisialisasi atau gagal. Periksa $logFile" -ForegroundColor Yellow
        }
    }

    "stop" {
        Write-Host "`n=== [SIMOVER] MENGHENTIKAN AI WORKER ===" -ForegroundColor Cyan
        $existingPid = Get-WorkerPid
        if ($existingPid) {
            if (Is-ProcessAlive $existingPid) {
                Write-Host "Menghentikan proses PID: $existingPid..." -ForegroundColor Yellow
                try {
                    Stop-Process -Id $existingPid -Force -ErrorAction SilentlyContinue
                    Write-Host "Proses AI Worker dihentikan." -ForegroundColor Green
                } catch {
                    Write-Host "Gagal menghentikan proses PID: $existingPid" -ForegroundColor Red
                }
            } else {
                Write-Host "PID $existingPid bukan worker aktif (PID file usang). Tidak ada proses yang dihentikan." -ForegroundColor Yellow
            }
            if (Test-Path $pidFile) {
                Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
            }
        } else {
            # Cari proses worker jika PID file hilang: hanya python venv proyek dengan modul ai_worker.worker
            $procs = Get-CimInstance Win32_Process | Where-Object { Is-ProcessAlive $_.ProcessId }
            if ($procs) {
                foreach ($p in $procs) {
                    Write-Host "Menghentikan proses yatim PID: $($p.ProcessId)..." -ForegroundColor Yellow
                    Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
                }
                Write-Host "Semua proses AI Worker dihentikan." -ForegroundColor Green
            } else {
                Write-Host "Tidak ada AI Worker yang sedang berjalan." -ForegroundColor Gray
            }
        }
    }

    "status" {
        Write-Host "`n=== [SIMOVER] STATUS AI WORKER ===" -ForegroundColor Cyan
        $existingPid = Get-WorkerPid
        $isRunning = $false

        if ($existingPid -and (Is-ProcessAlive $existingPid)) {
            $isRunning = $true
            Write-Host "Status Proses : AKTIF (PID: $existingPid)" -ForegroundColor Green
        } else {
            Write-Host "Status Proses : TIDAK AKTIF" -ForegroundColor Red
        }

        # Status dari heartbeat yang diterima server (data_origin antigravity_worker_p52)
        try {
            $apiRes = Invoke-RestMethod -Uri "http://localhost:8080/api/ai/status" -TimeoutSec 3 -ErrorAction SilentlyContinue
            $st = if ($apiRes -and $apiRes.sources) { $apiRes.sources.'uji-lama' } else { $null }
            if ($st) {
                Write-Host "Sumber        : $($st.origin_label)" -ForegroundColor Gray
                Write-Host "Heartbeat Ter : $($st.last_heartbeat) ($($st.heartbeat_age_sec) detik lalu)" -ForegroundColor Gray
                Write-Host "Status Server : $($st.label)" -ForegroundColor $(if ($st.is_live) { "Green" } else { "Red" })
            }
        } catch {
            Write-Host "Catatan       : Tidak dapat menghubungi API /api/ai/status (Web server offline?)" -ForegroundColor Gray
        }
    }

    "restart" {
        & $PSCommandPath "stop"
        Start-Sleep -Seconds 1
        & $PSCommandPath "start"
    }
}
