<?php

$path = __DIR__ . '/../resources/views/staff_portal/dashboard/index.blade.php';
$cssPath = __DIR__ . '/../public/css/staff_portal/dashboard-index.css';

$contents = file_get_contents($path);
if ($contents === false) {
    fwrite(STDERR, "failed to read blade\n");
    exit(1);
}

$contents = str_replace('<bottom class="office-menu-card">', '<div class="office-menu-card office-menu-card-static">', $contents);
$contents = str_replace('</bottom>', '</div>', $contents);
$contents = preg_replace(
    '/<span class="office-menu-card-links">\s*<span class="office-menu-chip">([^<]+)<\/span>\s*<span class="office-menu-chip">([^<]+)<\/span>\s*<\/span>/u',
    '<span class="office-menu-card-links"><a class="office-menu-chip office-menu-chip-link" href="{{ route(\'office.sales\') }}">$1</a><span class="office-menu-chip">$2</span></span>',
    $contents,
    1
);

file_put_contents($path, $contents);

$css = file_get_contents($cssPath);
if ($css === false) {
    fwrite(STDERR, "failed to read css\n");
    exit(1);
}

if (strpos($css, '.office-menu-card-static:hover') === false) {
    $css .= "\n.office-menu-card-static:hover {\n"
        . "    transform: none;\n"
        . "}\n\n"
        . ".office-menu-chip-link {\n"
        . "    text-decoration: none;\n"
        . "}\n\n"
        . ".office-menu-chip-link:hover {\n"
        . "    border-color: rgba(241, 164, 102, 0.6);\n"
        . "    background: rgba(255, 239, 222, 0.96);\n"
        . "}\n";
}

file_put_contents($cssPath, $css);
