<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows=Illuminate\Support\Facades\DB::connection('sqlsrv')->table('dbo.t_kihon_shift')->limit(5)->get();
foreach($rows as $r){echo json_encode((array)$r, JSON_UNESCAPED_UNICODE)."\n";}
