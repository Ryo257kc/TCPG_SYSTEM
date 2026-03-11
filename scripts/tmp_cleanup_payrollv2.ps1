$path='app/Http/Controllers/Admin/PayrollV2Controller.php'
$c=Get-Content -Raw $path
$pattern1='(?s)\n\s*if \(\$staffId === ''''\) \{.*?\n\s*\}\r?\n\r?\n\s*private function redirectFromActionResult'
$pattern2='(?s)\n\s*\$text = trim\(\(string\) \$value\);.*?\n\s*\}\r?\n\r?\n\s*private function runCalculationAction'
$c=[regex]::Replace($c,$pattern1,"`r`n    private function redirectFromActionResult")
$c=[regex]::Replace($c,$pattern2,"`r`n    private function runCalculationAction")
$utf8NoBom=New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText((Resolve-Path $path),$c,$utf8NoBom)
