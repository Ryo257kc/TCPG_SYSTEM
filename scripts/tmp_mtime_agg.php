<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$agg = DB::connection('sqlsrv')->select("
SELECT
  SUM(CASE WHEN LTRIM(RTRIM(ISNULL(work_type,''))) IN (N'休出',N'法出') THEN 1 ELSE 0 END) AS holiday_days,
  SUM(CASE WHEN LTRIM(RTRIM(ISNULL(work_type,''))) IN (N'休出',N'法出') THEN ISNULL(CAST(work_type_time as float),0) ELSE 0 END) AS holiday_hours,
  SUM(ISNULL(CAST(overtime_hours as float),0)) AS overtime_hours,
  SUM(ISNULL(CAST(night_overtime_hours as float),0)) AS night_overtime_hours,
  SUM(ISNULL(CAST(work_time as float),0)) AS work_time_sum,
  SUM(CASE WHEN ISNULL(CAST(work_time as float),0) > 0 AND LTRIM(RTRIM(ISNULL(work_type,''))) NOT IN (N'休出',N'法出') THEN 1 ELSE 0 END) AS work_days_from_work_time
FROM dbo.m_time_cards
WHERE LTRIM(RTRIM(staff_id)) = ?
  AND CONVERT(date, work_date) BETWEEN ? AND ?
", ['024','2026-01-01','2026-01-31']);

echo "-- sql agg --\n";
print_r($agg);
