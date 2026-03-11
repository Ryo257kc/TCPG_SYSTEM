<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $r = Illuminate\Support\Facades\DB::connection('sqlsrv')
      ->selectOne("SELECT TOP 1 * FROM Payroll_DEV.dbo.mx_kihon");
    var_dump($r ? 'ok' : 'no');
} catch (Throwable $e) {
    echo $e->getMessage(), "\n";
}
