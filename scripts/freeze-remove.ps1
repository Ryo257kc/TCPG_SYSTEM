param(
    [Parameter(Mandatory=$true, Position=0)]
    [string[]]$Paths
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$freezePath = Join-Path $repoRoot '.freeze-files.txt'
if (-not (Test-Path $freezePath)) {
    Write-Host "[OK] no freeze file" -ForegroundColor Yellow
    exit 0
}

$targets = @($Paths | ForEach-Object { ($_ -replace '\\','/').Trim() } | Where-Object { $_ -ne '' })
$existing = @(Get-Content $freezePath)
$kept = New-Object System.Collections.Generic.List[string]

foreach ($line in $existing) {
    $trim = $line.Trim()
    if ($trim -eq '' -or $trim.StartsWith('#')) {
        $kept.Add($line)
        continue
    }
    if ($targets -contains $trim) {
        Write-Host "[REMOVE] $trim" -ForegroundColor Green
        continue
    }
    $kept.Add($line)
}

$kept | Set-Content -Encoding UTF8 $freezePath
Write-Host "[OK] updated: $freezePath" -ForegroundColor Cyan
