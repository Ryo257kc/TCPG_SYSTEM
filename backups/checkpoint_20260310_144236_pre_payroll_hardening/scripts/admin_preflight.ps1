param(
    [switch]$StartServer,
    [int]$Port = 8082
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Step {
    param([string]$Message)
    Write-Host "[STEP] $Message" -ForegroundColor Cyan
}

function Fail {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
    exit 1
}

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

if (-not (Test-Path (Join-Path $projectRoot 'artisan'))) {
    Fail "artisan not found: $projectRoot"
}

if ($projectRoot -notmatch 'tcpg_system_admin_laravel') {
    Fail "wrong project root: $projectRoot"
}

Write-Step "Project: $projectRoot"

Write-Step "Clear Laravel caches"
& php artisan optimize:clear | Out-Null

Write-Step "Rebuild Blade cache"
& php artisan view:cache | Out-Null

Write-Step "Lint compiled Blade files"
$compiledDir = Join-Path $projectRoot 'storage\framework\views'
$compiledFiles = Get-ChildItem $compiledDir -Filter *.php -File
$lintErrors = @()

foreach ($file in $compiledFiles) {
    $out = cmd /c "php -n -l ""$($file.FullName)"" 2>&1"
    if ($LASTEXITCODE -ne 0) {
        $lintErrors += [PSCustomObject]@{
            File = $file.FullName
            Output = ($out -join "`n")
        }
    }
}

if ($lintErrors.Count -gt 0) {
    Write-Host ""
    Write-Host "---- COMPILED VIEW ERRORS ----" -ForegroundColor Yellow
    $lintErrors | Select-Object -First 10 | ForEach-Object {
        Write-Host $_.File -ForegroundColor Yellow
        Write-Host $_.Output -ForegroundColor Yellow
        Write-Host ""
    }
    Fail "compiled views contain syntax errors"
}

Write-Step "Quick check: broken close tags in payroll blade"
$payrollBlade = Join-Path $projectRoot 'resources\views\admin\payroll\index.blade.php'
if (Test-Path $payrollBlade) {
    $content = Get-Content $payrollBlade -Raw
    if ($content -match '・/title>|・/div>|・/a>|・/button>|・/label>|・/h3>') {
        Fail "broken close tags detected in resources/views/admin/payroll/index.blade.php"
    }
}

Write-Step "Block legacy table references (dbo.staff) in admin runtime code"
$legacyHits = @()
$scanTargets = @(
    (Join-Path $projectRoot 'app'),
    (Join-Path $projectRoot 'resources'),
    (Join-Path $projectRoot 'routes')
)
foreach ($target in $scanTargets) {
    if (-not (Test-Path $target)) { continue }
    $files = Get-ChildItem -Path $target -Recurse -File -Include *.php,*.blade.php
    foreach ($f in $files) {
        $hit = Select-String -Path $f.FullName -Pattern "dbo\.staff\b|table\('dbo\.staff'\)|table\('staff'\)" -SimpleMatch:$false
        if ($hit) {
            $legacyHits += $hit
        }
    }
}
if ($legacyHits.Count -gt 0) {
    Write-Host ""
    Write-Host "---- LEGACY TABLE REFERENCES ----" -ForegroundColor Yellow
    $legacyHits | Select-Object -First 20 | ForEach-Object {
        Write-Host ("{0}:{1}: {2}" -f $_.Path, $_.LineNumber, $_.Line.Trim()) -ForegroundColor Yellow
    }
    Fail "legacy table reference detected (dbo.staff). use m_staffs only."
}

Write-Host "[OK] preflight passed." -ForegroundColor Green

if ($StartServer) {
    Write-Step "Stop old php server processes on port $Port"
    $listeningIds = netstat -ano | Select-String ":$Port" | Select-String "LISTENING" | ForEach-Object {
        ($_ -split '\s+')[-1]
    } | Sort-Object -Unique

    foreach ($id in $listeningIds) {
        try {
            $proc = Get-CimInstance Win32_Process -Filter "ProcessId=$id"
            if ($proc -and $proc.Name -eq 'php.exe') {
                taskkill /PID $id /F | Out-Null
            }
        } catch {
            # ignore
        }
    }

    Write-Step "Start php artisan serve"
    Start-Process -FilePath 'php' -ArgumentList "artisan serve --host=127.0.0.1 --port=$Port" -WorkingDirectory $projectRoot | Out-Null
    Start-Sleep -Seconds 1
    Write-Host "[OK] server started: http://127.0.0.1:$Port" -ForegroundColor Green
}

exit 0
