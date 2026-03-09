param(
    [string]$Root = '.',
    [switch]$WhatIf
)

$ErrorActionPreference = 'Stop'
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$patterns = @('*.php','*.blade.php','*.js','*.ts','*.css','*.scss','*.json','*.md','*.yml','*.yaml','*.xml','*.txt','*.ps1')

$files = foreach($p in $patterns){ Get-ChildItem -Path $Root -Recurse -File -Filter $p }
$files = $files | Where-Object { $_.FullName -notmatch '\\vendor\\|\\node_modules\\|\\storage\\framework\\' } | Sort-Object FullName -Unique

$updated = 0
foreach($f in $files){
    $bytes = [System.IO.File]::ReadAllBytes($f.FullName)
    $enc = New-Object System.Text.UTF8Encoding($false, $true)
    try {
        $text = $enc.GetString($bytes)
    } catch {
        continue
    }

    $normalized = $text -replace "`r`n", "`n"
    $orig = [System.IO.File]::ReadAllText($f.FullName)
    if($orig -ne $normalized -or ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF)){
        if(-not $WhatIf){
            [System.IO.File]::WriteAllText($f.FullName, $normalized, $utf8NoBom)
        }
        $updated++
    }
}

Write-Host "normalized files: $updated"
