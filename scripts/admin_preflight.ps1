param(
    [switch]$StartServer,
    [int]$Port = 8082,
    [switch]$SkipEncodingCheck,
    [switch]$SkipRouteCheck
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

function Test-Utf8Strict([byte[]]$Bytes) {
    try {
        $enc = New-Object System.Text.UTF8Encoding($false, $true)
        [void]$enc.GetString($Bytes)
        return $true
    } catch {
        return $false
    }
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

Write-Step "Freeze guard (working tree)"
$freezeFile = Join-Path $projectRoot '.freeze-files.txt'
if (Test-Path $freezeFile) {
    $frozen = @(
        Get-Content $freezeFile |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -ne '' -and -not $_.StartsWith('#') }
    )

    if ($frozen.Count -gt 0) {
        $changed = @(
            git -C $projectRoot diff --name-only
            git -C $projectRoot diff --cached --name-only
        ) | Where-Object { $_ -and $_.Trim() -ne '' } | Sort-Object -Unique

        $violations = @()
        foreach ($ff in $frozen) {
            if ($changed -contains $ff) {
                $violations += $ff
            }
        }

        if ($violations.Count -gt 0) {
            $violations | ForEach-Object {
                Write-Host "[FREEZE] modified frozen file: $_" -ForegroundColor Red
            }
            Fail "freeze guard failed. unfreeze explicitly before editing."
        }
    }
}

Write-Step "Page scope guard (working tree)"
$scopeFile = Join-Path $projectRoot '.page-scope.txt'
if (Test-Path $scopeFile) {
    $scopeRules = @(
        Get-Content $scopeFile |
            ForEach-Object { $_.Trim() } |
            Where-Object { $_ -ne '' -and -not $_.StartsWith('#') }
    )

    if ($scopeRules.Count -gt 0) {
        $changed = @(
            git -C $projectRoot diff --name-only
            git -C $projectRoot diff --cached --name-only
        ) | Where-Object { $_ -and $_.Trim() -ne '' } | Sort-Object -Unique

        $scopeViolations = @()
        foreach ($file in $changed) {
            $allowed = $false
            foreach ($rule in $scopeRules) {
                # simple prefix match for repository-relative paths
                if ($file -eq $rule -or $file.StartsWith($rule)) {
                    $allowed = $true
                    break
                }
            }
            if (-not $allowed) {
                $scopeViolations += $file
            }
        }

        if ($scopeViolations.Count -gt 0) {
            $scopeViolations | Select-Object -First 30 | ForEach-Object {
                Write-Host "[SCOPE] out of scope changed file: $_" -ForegroundColor Red
            }
            Fail "page scope guard failed. edit only files in .page-scope.txt"
        }
    }
}

$guardScript = Join-Path $projectRoot 'scripts\guard-project.ps1'
if (Test-Path $guardScript) {
    Write-Step "Guard project lock"
    & powershell -NoProfile -ExecutionPolicy Bypass -File $guardScript -ExpectedProjectName 'tcpg_system_admin_laravel'
    if ($LASTEXITCODE -ne 0) {
        Fail "guard-project check failed"
    }
}

if (-not $SkipEncodingCheck) {
    Write-Step "Runtime encoding check (UTF-8 + no BOM for php/blade)"
    $runtimeRoots = @(
        (Join-Path $projectRoot 'app'),
        (Join-Path $projectRoot 'resources'),
        (Join-Path $projectRoot 'routes'),
        (Join-Path $projectRoot 'config')
    )

    $runtimeErrors = @()
    $runtimeWarns = @()
    $mojibakePattern = [string][char]0xFFFD

    foreach ($root in $runtimeRoots) {
        if (-not (Test-Path $root)) { continue }
        $files = Get-ChildItem -Path $root -Recurse -File -Include *.php,*.blade.php
        foreach ($file in $files) {
            $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
            if (-not (Test-Utf8Strict $bytes)) {
                $runtimeErrors += "invalid UTF-8: $($file.FullName)"
                continue
            }

            $hasBom = $bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF
            if ($hasBom) {
                $runtimeErrors += "BOM not allowed: $($file.FullName)"
            }

            $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
            if ($content.Contains($mojibakePattern)) {
                $runtimeWarns += "possible mojibake: $($file.FullName)"
            }
        }
    }

    if ($runtimeWarns.Count -gt 0) {
        $runtimeWarns | Select-Object -First 10 | ForEach-Object {
            Write-Host "[WARN] $_" -ForegroundColor Yellow
        }
    }

    if ($runtimeErrors.Count -gt 0) {
        $runtimeErrors | Select-Object -First 20 | ForEach-Object {
            Write-Host "[ENCODING] $_" -ForegroundColor Red
        }
        Fail "runtime encoding check failed"
    }
}

Write-Step "Clear Laravel caches"
& php artisan optimize:clear | Out-Null

Write-Step "Rebuild Blade cache"
& php artisan view:cache | Out-Null

Write-Step "Lint compiled Blade files"
$compiledDir = Join-Path $projectRoot 'storage\framework\views'
$compiledFiles = Get-ChildItem $compiledDir -Filter *.php -File
$lintErrors = @()

foreach ($file in $compiledFiles) {
    $out = & php -n -l $file.FullName 2>&1
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
    if ($content -match '繝ｻ/title>|繝ｻ/div>|繝ｻ/a>|繝ｻ/button>|繝ｻ/label>|繝ｻ/h3>') {
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
    Fail "legacy table reference detected (dbo.staff). use mx_staffs only."
}

Write-Step "Block old runtime table references (t_/staff)."
$legacyRuntimeHits = @()
foreach ($target in $scanTargets) {
    if (-not (Test-Path $target)) { continue }
    $files = Get-ChildItem -Path $target -Recurse -File -Include *.php,*.blade.php
    foreach ($f in $files) {
        $hit1 = Select-String -Path $f.FullName -Pattern 'table\(\s*[''"](?:dbo\.)?(?:t_|staff\b)' -SimpleMatch:$false
        if ($hit1) { $legacyRuntimeHits += $hit1 }

        $hit2 = Select-String -Path $f.FullName -Pattern "\[dbo\]\.\[(?:t_|staff\b)" -SimpleMatch:$false
        if ($hit2) { $legacyRuntimeHits += $hit2 }
    }
}
if ($legacyRuntimeHits.Count -gt 0) {
    $filtered = @($legacyRuntimeHits | Where-Object { $_.Line -notmatch "mx_" })
    if ($filtered.Count -gt 0) {
        Write-Host ""
        Write-Host "---- LEGACY RUNTIME TABLE REFERENCES ----" -ForegroundColor Yellow
        $filtered | Select-Object -First 20 | ForEach-Object {
            Write-Host ("{0}:{1}: {2}" -f $_.Path, $_.LineNumber, $_.Line.Trim()) -ForegroundColor Yellow
        }
        Fail "old runtime table reference detected. use m_/mx_ tables only."
    }
}

if (-not $SkipRouteCheck) {
    Write-Step "Route sanity checks"
    & php artisan route:list --path=admin/payroll | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Fail "route check failed: admin/payroll"
    }

    & php artisan route:list --path=admin/attendance | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Fail "route check failed: admin/attendance"
    }
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
