<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::connection('sqlsrv')->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='m_time_cards' ORDER BY ORDINAL_POSITION");
echo "-- columns --\n";
foreach($cols as $c){ echo $c->COLUMN_NAME, "\n"; }

$rows = DB::connection('sqlsrv')->table('dbo.m_time_cards')
    ->whereRaw('LTRIM(RTRIM(staff_id))=?', ['024'])
    ->whereRaw('CONVERT(date, work_date) between ? and ?', ['2026-01-01','2026-01-31'])
    ->orderBy('work_date')
    ->limit(10)
    ->get();

echo "\n-- sample rows --\n";
foreach($rows as $r){
  $a=(array)$r;
  echo ($a['work_date']??'')," | ",($a['work_type']??'')," | ",($a['work_type_time']??'')," | ",($a['change_start']??'')," | ",($a['change_leave']??'')," | ",($a['change_break_out']??'')," | ",($a['change_end']??'')," | ",($a['overtime_hours']??'')," | ",($a['night_overtime_hours']??''),"\n";
}
