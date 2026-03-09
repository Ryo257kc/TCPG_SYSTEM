<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows=Illuminate\Support\Facades\DB::connection('sqlsrv')->select("SELECT TOP 40 CONVERT(date, calendar_day) as d, work_holiday FROM dbo.t_calendar ORDER BY calendar_day DESC");
foreach($rows as $r){echo $r->d.'|'.(string)$r->work_holiday."\n";}
