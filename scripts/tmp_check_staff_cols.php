<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('sqlsrv');
$cols = $conn->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='m_staffs' ORDER BY ORDINAL_POSITION");
echo "-- columns --\n";
foreach ($cols as $c) { echo $c->COLUMN_NAME, "\n"; }

$cands = ['tai_date','retire_date','nyu_date','hire_date'];
$exists = [];
$colNames = array_map(fn($r)=>strtolower($r->COLUMN_NAME), $cols);
foreach ($cands as $c) if (in_array(strtolower($c), $colNames, true)) $exists[] = $c;

echo "-- date candidates exists --\n";
echo implode(', ', $exists), "\n";

foreach ($exists as $c) {
  $sql = "SELECT COUNT(*) AS total, SUM(CASE WHEN [$c] IS NULL THEN 1 ELSE 0 END) AS nulls FROM dbo.m_staffs";
  $r = $conn->select($sql);
  echo "-- $c --\n";
  var_export($r);
  echo "\n";
}
