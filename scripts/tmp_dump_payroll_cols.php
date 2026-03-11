<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$r = Illuminate\Support\Facades\DB::connection('sqlsrv_payroll')
    ->table('dbo.mx_kyuyo_shou')
    ->where('bonus', 0)
    ->orderBy('kyuyo_sho_no', 'desc')
    ->first();
if (!$r) {
    echo "no-row\n";
    exit(0);
}
$a = (array) $r;
foreach (array_keys($a) as $k) {
    echo $k, "\n";
}
