$procs = Get-Process ffmpeg -ErrorAction SilentlyContinue
$totalMemMB = ($procs | Measure-Object -Property WorkingSet64 -Sum).Sum / 1MB
$totalCpuSec = ($procs | Measure-Object -Property CPU -Sum).Sum
$cpuSample = Get-Counter '\Processor(_Total)\% Processor Time' -SampleInterval 1 -MaxSamples 2
$avgCpu = ($cpuSample.CounterSamples | Measure-Object -Property CookedValue -Average).Average

[PSCustomObject]@{
    FfmpegCount = $procs.Count
    FfmpegRAM_MB = [math]::Round($totalMemMB, 1)
    FfmpegTotalCpuSec = [math]::Round($totalCpuSec, 1)
    SystemCpuPercent = [math]::Round($avgCpu, 1)
} | Format-List
