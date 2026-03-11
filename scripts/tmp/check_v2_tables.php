<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

function listCols($conn, $table) {
    $rows = DB::connection($conn)->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? ORDER BY ORDINAL_POSITION", [$table]);
    return array_map(fn($r)=>$r->COLUMN_NAME, $rows);
}

$checks = [
  ['sqlsrv_payroll', ['m_payroll_entries','mx_kyuyo_shou','m_kyuyo_shou']],
  ['sqlsrv', ['m_staffs','mx_staffs','m_stores','mx_stores']],
];

foreach ($checks as [$conn, $tables]) {
  echo "== $conn ==\n";
  foreach ($tables as $t) {
    $exists = DB::connection($conn)->selectOne("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=?", [$t])->c ?? 0;
    echo "$t\t" . ((int)$exists > 0 ? 'exists' : 'missing') . "\n";
    if ((int)$exists > 0) {
      $cols = listCols($conn, $t);
      echo "  cols: " . implode(', ', array_slice($cols, 0, 25));
      if (count($cols) > 25) echo ', ...';
      echo "\n";
    }
  }
}
