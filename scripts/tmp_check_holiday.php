<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = Illuminate\Support\Facades\DB::connection('sqlsrv');

$tc = $db->table('dbo.m_time_cards')
    ->selectRaw("FORMAT(CONVERT(date,work_date),'yyyy-MM') as ym, COUNT(*) as total, SUM(CASE WHEN LTRIM(RTRIM(ISNULL(work_type,'')))<>'' THEN 1 ELSE 0 END) as filled")
    ->whereRaw('CONVERT(date,work_date) between ? and ?', ['2026-02-01','2026-03-31'])
    ->groupByRaw("FORMAT(CONVERT(date,work_date),'yyyy-MM')")
    ->orderBy('ym')->get();

echo "-- m_time_cards(work_type) --".PHP_EOL;
foreach($tc as $r){ echo "$r->ym total=$r->total filled=$r->filled".PHP_EOL; }

$cal = $db->table('dbo.m_calendars')
    ->selectRaw("FORMAT(CONVERT(date,calendar_day),'yyyy-MM') as ym, COUNT(*) as total, SUM(CASE WHEN LTRIM(RTRIM(ISNULL(work_holiday,'')))<>'' THEN 1 ELSE 0 END) as filled")
    ->whereRaw('CONVERT(date,calendar_day) between ? and ?', ['2026-02-01','2026-03-31'])
    ->groupByRaw("FORMAT(CONVERT(date,calendar_day),'yyyy-MM')")
    ->orderBy('ym')->get();

echo "-- m_calendars(work_holiday) --".PHP_EOL;
foreach($cal as $r){ echo "$r->ym total=$r->total filled=$r->filled".PHP_EOL; }
