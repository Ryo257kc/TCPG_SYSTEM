param(
    [string]$Root = "."
)

$ErrorActionPreference = "Stop"

function Fail([string]$Message) {
    Write-Host "[ENCODING] $Message" -ForegroundColor Red
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

$rootPath = Resolve-Path $Root
$extensions = @(
    ".php", ".blade.php", ".js", ".ts", ".css", ".scss",
    ".json", ".md", ".yml", ".yaml", ".xml", ".txt", ".ps1"
)
$skipDirs = @(
    "\.git\", "\vendor\", "\storage\", "\node_modules\", "\bootstrap\cache\", "\backups\", "\scripts\tmp\"
)

$targets = Get-ChildItem -Path $rootPath -Recurse -File | Where-Object {
    $full = $_.FullName.Replace('/', '\')
    foreach ($skip in $skipDirs) {
        if ($full -like "*$skip*") { return $false }
    }

    $name = $_.Name.ToLowerInvariant()
    $ok = $false
    foreach ($ext in $extensions) {
        if ($name.EndsWith($ext)) { $ok = $true; break }
    }
    return $ok
}

$targets = $targets | Where-Object {
    $rel = $_.FullName.Substring($rootPath.Path.Length).TrimStart('\').Replace('/', '\')
    if ($rel -like 'scripts\tmp*') { return $false }
    if ($rel -like 'tmp_*') { return $false }
    return $true
}

if ($targets.Count -eq 0) {
    Write-Host "[ENCODING] no target files"
    exit 0
}

$errors = New-Object System.Collections.Generic.List[string]
foreach ($file in $targets) {
    $rel = $file.FullName.Substring($rootPath.Path.Length).TrimStart('\\')
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)

    if (-not (Test-Utf8Strict $bytes)) {
        $errors.Add("invalid UTF-8: $rel")
        continue
    }

    $hasBom = $bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF
    $isPhpLike = $rel -match '\.php$' -or $rel -match '\.blade\.php$'
    if ($hasBom -and $isPhpLike) {
        $errors.Add("BOM not allowed for php/blade: $rel")
    }

    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    if (Test-PossibleMojibake $content) {
        $errors.Add("possible mojibake: $rel")
    }
}

if ($errors.Count -gt 0) {
    foreach ($err in $errors) {
        Write-Host "[ENCODING] $err" -ForegroundColor Red
    }
    Fail ("encoding check failed: " + $errors.Count + " issue(s)")
}

Write-Host ("[ENCODING] OK: {0} files checked" -f $targets.Count) -ForegroundColor Green
exit 0


