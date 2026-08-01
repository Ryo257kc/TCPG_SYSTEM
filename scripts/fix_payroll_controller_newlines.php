<?php

$file = __DIR__ . '/../app/Http/Controllers/Admin/V2/PayrollV2Controller.php';
$contents = file_get_contents($file);
if ($contents === false) {
    fwrite(STDERR, "failed to read\n");
    exit(1);
}

$contents = preg_replace(
    '/^(    \/\/(?!.*\/\/).*?)\s+(public function .*)$/m',
    '$1' . PHP_EOL . '    $2',
    $contents
);

$contents = preg_replace(
    "/(\\\$companyKey = \\\$row\\['company_name'\\] !== '' \\? \\\$row\\['company_name'\\] : )'[^\\r\\n;]*;/",
    "$1'未設定';",
    $contents
);
$contents = preg_replace(
    "/(\\\$bankKey = \\\$row\\['bank_name'\\] !== '' \\? \\\$row\\['bank_name'\\] : )'[^\\r\\n;]*;/",
    "$1'未設定';",
    $contents
);
$contents = preg_replace(
    "/if \\(\\\$row\\['division'\\] !== '[^\\r\\n]*\\) \\{/",
    "if (!str_contains((string) \$row['division'], '業務委託')) {",
    $contents
);
$contents = preg_replace(
    "/if \\(\\\$staffId === '' \\|\\| \\\$division !== '[^\\r\\n]*\\) \\{/",
    "if (\$staffId === '' || !str_contains(\$division, '業務委託')) {",
    $contents
);
$outsourceNeedle = '"\\xE6\\xA5\\xAD\\xE5\\x8B\\x99\\xE5\\xA7\\x94\\xE8\\xA8\\x97"';
$contents = preg_replace(
    "/if \\(!str_contains\\(\\(string\\) \\\$row\\['division'\\], '[^\\r\\n]*\\) \\{/",
    "if (!str_contains((string) \$row['division'], {$outsourceNeedle})) {",
    $contents
);
$contents = preg_replace(
    "/if \\(trim\\(\\(string\\) \\(\\\$row\\['division'\\] \\?\\? ''\\)\\) === '[^\\r\\n]*\\) \\{/",
    "if (str_contains(trim((string) (\$row['division'] ?? '')), {$outsourceNeedle})) {",
    $contents
);
$contents = preg_replace(
    "/\\\$isOutsource = str_contains\\(\\\$division, '[^\\r\\n]*\\);/",
    "\$isOutsource = str_contains(\$division, {$outsourceNeedle});",
    $contents
);
$contents = preg_replace(
    "/if \\(\\\$staffId === '' \\|\\| !str_contains\\(\\\$division, '[^\\r\\n]*\\) \\{/",
    "if (\$staffId === '' || !str_contains(\$division, {$outsourceNeedle})) {",
    $contents
);

file_put_contents($file, $contents);
