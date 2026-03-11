param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$ExpectedProjectName
)

$ErrorActionPreference = "Stop"

$cwd = [System.IO.Path]::GetFullPath((Get-Location).Path).TrimEnd('\\')
$projectRoot = $null

try {
    $gitRoot = (git rev-parse --show-toplevel 2>$null)
    if ($LASTEXITCODE -eq 0 -and -not [string]::IsNullOrWhiteSpace($gitRoot)) {
        $projectRoot = [System.IO.Path]::GetFullPath($gitRoot.Trim()).TrimEnd('\\')
    }
} catch {
    # Fallback to cwd-based checks below.
}

if (-not $projectRoot) {
    $probe = $cwd
    while ($true) {
        if (Test-Path (Join-Path $probe "AGENTS.md")) {
            $projectRoot = $probe
            break
        }
        $parent = Split-Path -Path $probe -Parent
        if ([string]::IsNullOrEmpty($parent) -or $parent -eq $probe) {
            break
        }
        $probe = $parent
    }
}

if (-not $projectRoot) {
    $projectRoot = $cwd
}

$actualProjectName = Split-Path -Path $projectRoot -Leaf

if ($actualProjectName -cne $ExpectedProjectName) {
    Write-Host "[HARD-STOP] Wrong project." -ForegroundColor Red
    Write-Host "  CWD      : $cwd"
    Write-Host "  Root     : $projectRoot"
    Write-Host "  Actual   : $actualProjectName"
    Write-Host "  Expected : $ExpectedProjectName"
    exit 42
}

Write-Host "[OK] Project lock passed: $ExpectedProjectName" -ForegroundColor Green
Write-Host "  Root     : $projectRoot"
exit 0
