param(
    [Parameter(Mandatory=$true, Position=0)]
    [string[]]$Rules
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$scopePath = Join-Path $repoRoot '.page-scope.txt'

$header = '# repo-relative prefixes/files allowed for current task'
$out = New-Object System.Collections.Generic.List[string]
$out.Add($header)
foreach ($r in $Rules) {
    $v = ($r -replace '\\','/').Trim()
    if ($v -ne '') { $out.Add($v) }
}

$out | Set-Content -Encoding UTF8 $scopePath
Write-Host "[OK] scope set: $scopePath" -ForegroundColor Green
Get-Content $scopePath
