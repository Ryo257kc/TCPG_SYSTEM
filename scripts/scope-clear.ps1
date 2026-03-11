Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
$scopePath = Join-Path $repoRoot '.page-scope.txt'
@('# repo-relative prefixes/files allowed for current task') | Set-Content -Encoding UTF8 $scopePath
Write-Host "[OK] scope cleared: $scopePath" -ForegroundColor Green
