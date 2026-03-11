param(
    [Parameter(Mandatory=$true, Position=0)]
    [string[]]$Paths
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$freezePath = Join-Path $repoRoot '.freeze-files.txt'
if (-not (Test-Path $freezePath)) {
    '# one path per line (repo-relative)' | Set-Content -Encoding UTF8 $freezePath
}

$existing = Get-Content $freezePath | ForEach-Object { $_.Trim() }
$out = New-Object System.Collections.Generic.List[string]
foreach ($line in $existing) {
    if ($line -ne '') { $out.Add($line) }
}

foreach ($p in $Paths) {
    $norm = ($p -replace '\\','/').Trim()
    if ($norm -eq '') { continue }
    if (-not ($out -contains $norm)) {
        $out.Add($norm)
        Write-Host "[ADD] $norm" -ForegroundColor Green
    } else {
        Write-Host "[SKIP] $norm" -ForegroundColor Yellow
    }
}

$out | Set-Content -Encoding UTF8 $freezePath
Write-Host "[OK] updated: $freezePath" -ForegroundColor Cyan
