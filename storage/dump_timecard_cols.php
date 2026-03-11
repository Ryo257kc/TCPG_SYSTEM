<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$rows = DB::connection('sqlsrv')->select("SELECT TOP 1 * FROM dbo.mx_time_cards");
if (!$rows) { echo "no rows\n"; exit; }
$row = (array)$rows[0];
ksort($row);
foreach($row as $k=>$v){ echo $k, "\n"; }
