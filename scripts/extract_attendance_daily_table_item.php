<?php

$sourcePath = __DIR__ . '/../resources/views/admin_v2/work/attendance/daily_table.blade.php';
$itemPath = __DIR__ . '/../resources/views/admin_v2/work/attendance/daily_table_item.blade.php';

$source = file_get_contents($sourcePath);
if ($source === false) {
    fwrite(STDERR, "Failed to read source file.\n");
    exit(1);
}

$ifMarker = "        @if (!empty(\$dailyRows))";
$startMarker = "            <div class=\"daily-wrap\">";
$endMarker = "            <script>";
$includeLine = "            @include('admin_v2.work.attendance.daily_table_item')\r\n";

$ifPos = strpos($source, $ifMarker);
if ($ifPos === false) {
    fwrite(STDERR, "if marker not found.\n");
    exit(1);
}

$startPos = strpos($source, $startMarker, $ifPos);
if ($startPos === false) {
    fwrite(STDERR, "start marker not found.\n");
    exit(1);
}

$endPos = strpos($source, $endMarker, $startPos);
if ($endPos === false) {
    fwrite(STDERR, "end marker not found.\n");
    exit(1);
}

$tableBlock = substr($source, $startPos, $endPos - $startPos);
if ($tableBlock === false || $tableBlock === '') {
    fwrite(STDERR, "table block extraction failed.\n");
    exit(1);
}

$newSource = substr($source, 0, $startPos) . $includeLine . substr($source, $endPos);
if ($newSource === false || $newSource === '') {
    fwrite(STDERR, "source rewrite failed.\n");
    exit(1);
}

if (file_put_contents($itemPath, $tableBlock) === false) {
    fwrite(STDERR, "failed to write item file.\n");
    exit(1);
}

if (file_put_contents($sourcePath, $newSource) === false) {
    fwrite(STDERR, "failed to write source file.\n");
    exit(1);
}

fwrite(STDOUT, "attendance daily table item extracted.\n");
