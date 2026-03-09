param()

$ErrorActionPreference = 'Stop'

# enable repository-local hooks path
& git config core.hooksPath .githooks
if ($LASTEXITCODE -ne 0) {
    Write-Error 'failed to set core.hooksPath'
    exit 1
}

Write-Host 'hooksPath set to .githooks' -ForegroundColor Green
