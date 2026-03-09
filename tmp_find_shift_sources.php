<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cols = ['shift_start','シフト始業','work_type','区分','work_type_time','区分時間'];
foreach ($cols as $c) {
  echo "==== column {$c} ====\n";
  $rows = Illuminate\Support\Facades\DB::connection('sqlsrv')->select(
    "SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = ? ORDER BY TABLE_SCHEMA, TABLE_NAME",
    [$c]
  );
  foreach ($rows as $r) {
    echo $r->TABLE_SCHEMA . '.' . $r->TABLE_NAME . ' : ' . $r->COLUMN_NAME . "\n";
  }
}
