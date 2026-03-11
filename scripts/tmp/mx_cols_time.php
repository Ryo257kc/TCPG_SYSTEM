<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$rows = DB::connection('sqlsrv')->select("SELECT c.name AS col FROM sys.columns c WHERE c.object_id = OBJECT_ID('dbo.mx_time_cards') ORDER BY c.column_id");
foreach($rows as $r){ echo $r->col, "\n"; }
