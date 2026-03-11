<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::connection('sqlsrv')->table('dbo.m_time_cards')
    ->whereRaw('LTRIM(RTRIM(staff_id))=?', ['024'])
    ->whereRaw('CONVERT(date, work_date) between ? and ?', ['2026-01-01','2026-01-31'])
    ->get(['work_date','work_type','work_type_time','work_time','overtime_hours','night_overtime_hours','change_start','change_leave','change_break_out','change_end']);

echo 'rows=' . $rows->count() . PHP_EOL;
echo 'sum_work_time=' . (float)$rows->sum('work_time') . PHP_EOL;
echo 'sum_ot=' . (float)$rows->sum('overtime_hours') . PHP_EOL;
echo 'sum_night=' . (float)$rows->sum('night_overtime_hours') . PHP_EOL;
$holidayRows = $rows->filter(function($r){ $w=trim((string)($r->work_type??'')); return in_array($w,['休出','法出'],true); });
echo 'holiday_days=' . $holidayRows->count() . PHP_EOL;
echo 'holiday_time=' . (float)$holidayRows->sum('work_type_time') . PHP_EOL;

$changeHours = 0.0;
$workIn = 0;
foreach($rows as $r){
  $parts=[(string)($r->change_start??''),(string)($r->change_leave??''),(string)($r->change_break_out??''),(string)($r->change_end??'')];
  $mins=[];
  foreach($parts as $p){
    if(preg_match('/(\d{1,2}):(\d{2})/', $p,$m)){ $mins[]=(int)$m[1]*60+(int)$m[2]; } else { $mins[] = null; }
  }
  $h=0.0; $ok=false;
  if($mins[0]!==null && $mins[1]!==null && $mins[1]>=$mins[0]){ $h += ($mins[1]-$mins[0])/60; $ok=true; }
  if($mins[2]!==null && $mins[3]!==null && $mins[3]>=$mins[2]){ $h += ($mins[3]-$mins[2])/60; $ok=true; }
  if($ok && $h>0){ $workIn++; $changeHours += $h; }
}
echo 'change_work_days=' . $workIn . PHP_EOL;
echo 'change_work_hours=' . round($changeHours,2) . PHP_EOL;
