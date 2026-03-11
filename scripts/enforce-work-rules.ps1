param()

$ErrorActionPreference = 'Stop'

function Fail([string]$Message) {
    Write-Host "[RULE] $Message" -ForegroundColor Red
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

function Test-PossibleMojibake([string]$Content) {
    if ($Content.Contains([string][char]0xFFFD)) { return $true }

    $patterns = @(
        '(?:\u7E67|\u7E5D|\u8340|\u8763|\u870D|\u879F|\u8C55|\u9006|\u8B5B|\u9A65|\u90A8|\u8793){2,}',
        '(?:\u92C9|\u50DF|\u5116|\u5114|\u50D7|\u50E2|\u50FC){2,}'
    )

    foreach ($p in $patterns) {
        if ($Content -match $p) { return $true }
    }

    return $false
}

$repoRoot = (git rev-parse --show-toplevel 2>$null)
if (-not $repoRoot) {
    Fail "git repository not found."
}

$repoName = Split-Path -Leaf $repoRoot
if ($repoName -ne 'tcpg_system_admin_laravel') {
    Fail "wrong repository: $repoName"
}

$staged = @(git diff --cached --name-only --diff-filter=ACMR)
if ($staged.Count -eq 0) {
    exit 0
}

Write-Host "[RULE] staged files:" -ForegroundColor Cyan
$staged | ForEach-Object { Write-Host " - $_" }

# 1) scope restriction (admin side only)
$allowedPatterns = @(
    '^resources/views/admin/',
    '^app/Http/Controllers/Admin/',
    '^routes/web\.php$',
    '^docs/',
    '^scripts/',
    '^\.githooks/',
    '^\.freeze-files\.txt$',
    '^\.page-scope\.txt$',
    '^README\.md$'
)

foreach ($f in $staged) {
    $ok = $false
    foreach ($p in $allowedPatterns) {
        if ($f -match $p) { $ok = $true; break }
    }
    if (-not $ok) {
        Fail "out-of-scope file detected: $f"
    }
}

# 7) explicit non-admin folder deny (views/controllers)
foreach ($f in $staged) {
    if ($f -match '^resources/views/' -and $f -notmatch '^resources/views/admin/') {
        Fail "non-admin view is not allowed: $f"
    }
    if ($f -match '^app/Http/Controllers/' -and $f -notmatch '^app/Http/Controllers/Admin/') {
        Fail "non-admin controller is not allowed: $f"
    }
}

# 2) freeze file protection
if (Test-Path '.freeze-files.txt') {
    $frozen = Get-Content '.freeze-files.txt' |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ -ne '' -and -not $_.StartsWith('#') }

    foreach ($ff in $frozen) {
        if ($staged -contains $ff) {
            Fail "frozen file modified: $ff"
        }
    }
}

# 3) change isolation (max 3 work files)
$ignoreForCount = @('^docs/', '^scripts/', '^\.githooks/', '^\.freeze-files\.txt$', '^\.page-scope\.txt$', '^README\.md$')
$workFiles = @()
foreach ($f in $staged) {
    $skip = $false
    foreach ($p in $ignoreForCount) {
        if ($f -match $p) { $skip = $true; break }
    }
    if (-not $skip) { $workFiles += $f }
}

if ($workFiles.Count -gt 3) {
    Fail "too many work files: $($workFiles.Count) (max 3)"
}

# 6) page-level isolation (do not mix multiple pages in one commit)
$adminViewFiles = @($staged | Where-Object { $_ -match '^resources/views/admin/.+\.blade\.php$' })
if ($adminViewFiles.Count -gt 0) {
    $pageKeys = @()
    foreach ($vf in $adminViewFiles) {
        $rel = $vf -replace '^resources/views/admin/', ''
        $parts = $rel -split '/'
        if ($parts.Count -ge 2) {
            $pageKeys += $parts[0]
        } else {
            $pageKeys += ($parts[0] -replace '\.blade\.php$', '')
        }
    }
    $distinctPageKeys = @($pageKeys | Sort-Object -Unique)
    if ($distinctPageKeys.Count -gt 1) {
        Fail "mixed admin pages in one commit: $($distinctPageKeys -join ', ')"
    }
}

# 6b) enforce separated structure for newly added admin views
# allowed new files:
# - resources/views/admin/<page>/index.blade.php
# - resources/views/admin/<page>/partials/<name>.blade.php
$addedFiles = @(
    git diff --cached --name-status --diff-filter=A |
        ForEach-Object {
            $parts = $_ -split "`t"
            if ($parts.Count -ge 2) { $parts[1] } else { $null }
        } |
        Where-Object { $_ -ne $null -and $_ -ne '' }
)

foreach ($f in $addedFiles) {
    if ($f -notmatch '^resources/views/admin/.+\.blade\.php$') {
        continue
    }

    $isIndexPage = $f -match '^resources/views/admin/.+/index\.blade\.php$'
    $isPartial = $f -match '^resources/views/admin/.+/partials/.+\.blade\.php$'
    if (-not ($isIndexPage -or $isPartial)) {
        Fail "new admin view must be separated page structure: $f"
    }
}

# 4) syntax/view check mandatory
$needViewCheck = $false
foreach ($f in $staged) {
    if ($f -match '\.php$' -or $f -match '\.blade\.php$' -or $f -eq 'routes/web.php') {
        $needViewCheck = $true
        break
    }
}

# 4a) encoding guard (UTF-8 strict + no BOM for php/blade)
$textLike = @($staged | Where-Object { $_ -match '\.(php|blade\.php|js|ts|css|scss|json|md|ya?ml|xml|txt|ps1)$' })
foreach ($f in $textLike) {
    if (-not (Test-Path $f)) { continue }
    $bytes = [System.IO.File]::ReadAllBytes($f)
    if (-not (Test-Utf8Strict $bytes)) {
        Fail "invalid UTF-8 detected: $f"
    }
    $hasBom = $bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF
    if ($hasBom -and ($f -match '\.php$' -or $f -match '\.blade\.php$')) {
        Fail "BOM is not allowed for php/blade: $f"
    }
}

# 4ab) mojibake guard
foreach ($f in $textLike) {
    if (-not (Test-Path $f)) { continue }
    $content = [System.IO.File]::ReadAllText($f, [System.Text.Encoding]::UTF8)
    if (Test-PossibleMojibake $content) {
        Fail "possible mojibake detected: $f"
    }
}

# 8) legacy table reference deny (English-version only)
$legacyPattern = '\bt_time_card\b'
foreach ($f in $textLike) {
    if (-not (Test-Path $f)) { continue }
    $content = [System.IO.File]::ReadAllText($f, [System.Text.Encoding]::UTF8)
    if ($content -match $legacyPattern) {
        Fail "legacy table reference is forbidden (use m_time_cards only): $f"
    }
}

# 4b) php lint for staged php files
$phpFiles = @($staged | Where-Object { $_ -match '\.php$' -and $_ -notmatch '^vendor/' })
foreach ($pf in $phpFiles) {
    if (-not (Test-Path $pf)) { continue }
    & php -l $pf | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Fail "php -l failed: $pf"
    }
}

if ($needViewCheck) {
    & php artisan view:clear | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Fail "php artisan view:clear failed"
    }

    & php artisan view:cache | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Fail "php artisan view:cache failed"
    }
}

Write-Host "[RULE] all checks passed." -ForegroundColor Green
exit 0




