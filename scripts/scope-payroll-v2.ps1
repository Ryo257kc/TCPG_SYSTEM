param(
    [switch]$IncludeGuardFiles
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$scopePath = Join-Path $repoRoot '.page-scope.txt'

$rules = @(
    'app/Http/Controllers/Admin/V2/PayrollV2Controller.php',
    'app/Services/Admin/V2/Payroll/',
    'resources/views/admin_v2/payroll/',
    'routes/admin/payroll_v2.php'
)

if ($IncludeGuardFiles) {
    $rules += @('.page-scope.txt', '.freeze-files.txt', 'scripts/')
}

$out = New-Object System.Collections.Generic.List[string]
$out.Add('# repo-relative prefixes/files allowed for current task')
foreach ($r in $rules) {
    $v = ($r -replace '\\','/').Trim()
    if ($v -ne '') { $out.Add($v) }
}

$out | Set-Content -Encoding UTF8 $scopePath
Write-Host "[OK] payroll scope set: $scopePath" -ForegroundColor Green
Get-Content $scopePath
