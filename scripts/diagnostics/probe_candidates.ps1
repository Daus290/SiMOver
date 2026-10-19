$ErrorActionPreference = 'SilentlyContinue'
$rootDir = "D:\Project Coding Daus\Web\Sistem Monitoring Over Dimension Dishub"
$ffprobe = Join-Path $rootDir "writable\bin\ffprobe.exe"
if (-not (Test-Path $ffprobe)) {
    $ffprobe = "tools\ffmpeg\bin\ffprobe.exe"
}

# Baca env untuk kredensial
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
$nvrHost = if ($envConfig['vivotek.nvrHost']) { $envConfig['vivotek.nvrHost'] } else { '10.0.1.3' }
$nvrPort = if ($envConfig['vivotek.nvrRtspPort']) { $envConfig['vivotek.nvrRtspPort'] } else { '8554' }
$nvrUser = $envConfig['vivotek.nvrUsername']
$nvrPass = $envConfig['vivotek.nvrPassword']

$auth = [Uri]::EscapeDataString($nvrUser) + ':' + [Uri]::EscapeDataString($nvrPass) + '@'

$candidates = @(
    @{ Code = "BJB-ROULIN-F1";  Chan = 1;  Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-ROULIN-F2";  Chan = 2;  Stream = 1; Note = "Tetap S1 H.265 (transcode)" },
    @{ Code = "BJB-ROULIN-PTZ"; Chan = 4;  Stream = 1; Note = "Tetap S1 H.264" },
    @{ Code = "BJB-BRIMOB-F1";  Chan = 5;  Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-BRIMOB-F2";  Chan = 6;  Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-BRIMOB-F3";  Chan = 7;  Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-BRIMOB-PTZ"; Chan = 8;  Stream = 3; Note = "Calon S3 H.264" },
    @{ Code = "BJB-SAMSAT-F2";  Chan = 10; Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-SAMSAT-F3";  Chan = 11; Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-SAMSAT-F4";  Chan = 12; Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-SAMSAT-PTZ"; Chan = 13; Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-ADIPURA-F1"; Chan = 15; Stream = 3; Note = "Calon S3 H.264" },
    @{ Code = "BJB-ADIPURA-F2"; Chan = 16; Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-ADIPURA-F3"; Chan = 17; Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-ADIPURA-F4"; Chan = 18; Stream = 2; Note = "Calon S2 H.264" },
    @{ Code = "BJB-ADIPURA-PTZ";Chan = 19; Stream = 3; Note = "Tetap S3 H.264 (sudah disetujui)" }
)

$results = @()

foreach ($item in $candidates) {
    $c = $item.Code
    $ch = $item.Chan
    $st = $item.Stream
    $rtspUrl = "rtsp://${auth}${nvrHost}:${nvrPort}/Media/Live/Normal?camera=C_${ch}&streamindex=${st}"
    
    $probeArgs = @(
        "-rtsp_transport", "tcp",
        "-timeout", "5000000",
        "-i", $rtspUrl,
        "-v", "error",
        "-select_streams", "v:0",
        "-show_entries", "stream=codec_name,width,height,r_frame_rate,avg_frame_rate",
        "-of", "csv=p=0"
    )
    
    $out = & $ffprobe $probeArgs 2>&1
    $line = ($out | Select-String -Pattern "^\w+,\d+,\d+").Line
    if ($line) {
        $p = $line.Split(',')
        $results += [PSCustomObject]@{
            Code = $c
            Chan = "C_$ch"
            Stream = $st
            Codec = $p[0]
            Res = "$($p[1])x$($p[2])"
            FPS_r = $p[3]
            FPS_avg = $p[4]
            Status = "OK"
            Note = $item.Note
        }
    } else {
        $errStr = ($out | Out-String).Trim()
        $errShort = if ($errStr -like "*462*") { "462 Unreachable" } elseif ($errStr -like "*404*") { "404 Not Found" } else { "Timeout/Error" }
        $results += [PSCustomObject]@{
            Code = $c
            Chan = "C_$ch"
            Stream = $st
            Codec = "-"
            Res = "-"
            FPS_r = "-"
            FPS_avg = "-"
            Status = $errShort
            Note = $item.Note
        }
    }
}

$results | Format-Table -AutoSize
$results | Export-Csv -Path (Join-Path $rootDir "scratch\probe_candidates_result.csv") -NoTypeInformation
