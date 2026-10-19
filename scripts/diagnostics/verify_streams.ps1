$cameras = @(
    "BJB-ROULIN-F1", "BJB-ROULIN-F2", "BJB-ROULIN-PTZ",
    "BJB-BRIMOB-F1", "BJB-BRIMOB-F2", "BJB-BRIMOB-F3", "BJB-BRIMOB-PTZ",
    "BJB-SAMSAT-F2", "BJB-SAMSAT-F3", "BJB-SAMSAT-F4", "BJB-SAMSAT-PTZ",
    "BJB-ADIPURA-F1", "BJB-ADIPURA-F2", "BJB-ADIPURA-F3", "BJB-ADIPURA-F4", "BJB-ADIPURA-PTZ"
)

$results = @()

foreach ($cam in $cameras) {
    $url = "http://localhost:8080/cctv-stream/$cam/index.m3u8"
    try {
        $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 3
        $statusCode = $resp.StatusCode
        $content = $resp.Content
        $lines = ($content -split "`r?`n") | Where-Object { $_ -match '\.ts$' }
        $segCount = $lines.Count
        $firstSeg = if ($segCount -gt 0) { $lines[0] } else { "-" }
        $lastSeg = if ($segCount -gt 0) { $lines[-1] } else { "-" }

        $results += [PSCustomObject]@{
            Camera = $cam
            HTTP = $statusCode
            SegmentsInPlaylist = $segCount
            FirstSeg = $firstSeg
            LastSeg = $lastSeg
            Status = if ($statusCode -eq 200 -and $segCount -ge 3) { "PASS" } else { "CHECK" }
        }
    } catch {
        $results += [PSCustomObject]@{
            Camera = $cam
            HTTP = "ERROR"
            SegmentsInPlaylist = 0
            FirstSeg = "-"
            LastSeg = "-"
            Status = "FAILED"
        }
    }
}

$results | Format-Table -AutoSize
