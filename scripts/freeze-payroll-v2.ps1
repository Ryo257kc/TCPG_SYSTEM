param(
    [switch]$Overwrite
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$freezePath = Join-Path $repoRoot '.freeze-files.txt'

$targets = @(
    'app/Http/Controllers/Admin/V2/PayrollV2Controller.php',
    'app/Services/Admin/V2/Payroll/',
    'resources/views/admin_v2/payroll/',
    'routes/admin/payroll_v2.php'
)

if ($Overwrite -or -not (Test-Path $freezePath)) {
    $out = New-Object System.Collections.Generic.List[string]
    $out.Add('# one path per line (repo-relative)')
} else {
    $existing = Get-Content $freezePath | ForEach-Object { $_.Trim() }
    $out = New-Object System.Collections.Generic.List[string]
    foreach ($line in $existing) {
        if ($line -ne '') { $out.Add($line) }
    }
}

foreach ($t in $targets) {
    $norm = ($t -replace '\\','/').Trim()
    if ($norm -eq '') { continue }
    if (-not ($out -contains $norm)) {
        $out.Add($norm)
        Write-Host "[ADD] $norm" -ForegroundColor Green
    } else {
        Write-Host "[SKIP] $norm" -ForegroundColor Yellow
    }
}

$out | Set-Content -Encoding UTF8 $freezePath
Write-Host "[OK] payroll freeze updated: $freezePath" -ForegroundColor Cyan
Get-Content $freezePath
