<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$targets = [
    ['conn' => 'sqlsrv_payroll', 'table' => 'dbo.mx_kihon'],
    ['conn' => 'sqlsrv', 'table' => 'dbo.mx_staffs'],
    ['conn' => 'sqlsrv_payroll', 'table' => 'dbo.mx_staff_shou'],
    ['conn' => 'sqlsrv_payroll', 'table' => 'dbo.mx_resident'],
];

foreach ($targets as $t) {
    echo "=== {$t['conn']} {$t['table']} ===\n";
    $row = Illuminate\Support\Facades\DB::connection($t['conn'])
        ->table($t['table'])
        ->first();
    if (!$row) {
        echo "(no row)\n\n";
        continue;
    }
    foreach (array_keys((array)$row) as $k) {
        echo $k, "\n";
    }
    echo "\n";
}
