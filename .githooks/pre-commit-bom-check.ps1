$ErrorActionPreference = 'Stop'

$staged = git diff --cached --name-only --diff-filter=ACMR
if (-not $staged) { exit 0 }

$failed = @()
foreach ($path in $staged) {
    if ([string]::IsNullOrWhiteSpace($path)) { continue }

    # Read staged blob (not working tree) to avoid false positives.
    $blob = git show ":$path" 2>$null
    if ($LASTEXITCODE -ne 0) { continue }

    if ($blob.Length -ge 3) {
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($blob)
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            $failed += $path
        }
    }
}

if ($failed.Count -gt 0) {
    Write-Host "[BLOCKED] UTF-8 BOM detected in staged files:" -ForegroundColor Red
    $failed | ForEach-Object { Write-Host " - $_" -ForegroundColor Yellow }
    Write-Host "Please save as UTF-8 (without BOM), then re-stage and commit." -ForegroundColor Red
    exit 1
}

exit 0