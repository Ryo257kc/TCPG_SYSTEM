param(
  [Parameter(Mandatory = $true)]
  [string]$ExpectedProjectName
)

$ErrorActionPreference = "Stop"
$cwd = (Get-Location).Path
$leaf = Split-Path -Path $cwd -Leaf

if ($leaf -ne $ExpectedProjectName) {
  Write-Host "[HARD-STOP] Wrong project." -ForegroundColor Red
  Write-Host "  CWD      : $cwd"
  Write-Host "  Expected : $ExpectedProjectName"
  exit 42
}

Write-Host "[OK] Project lock passed: $ExpectedProjectName" -ForegroundColor Green
exit 0
