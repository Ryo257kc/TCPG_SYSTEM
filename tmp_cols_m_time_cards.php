<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$rows = Illuminate\Support\Facades\DB::connection('sqlsrv')->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='m_time_cards' ORDER BY ORDINAL_POSITION");
foreach ($rows as $r) { echo $r->COLUMN_NAME, PHP_EOL; }
