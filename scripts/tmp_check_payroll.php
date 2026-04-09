<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $row = Illuminate\Support\Facades\DB::connection('sqlsrv_payroll')
        ->table('dbo.mx_kyuyo_shou')
        ->where('edit_lock', 1)
        ->orderBy('supply_month', 'desc')
        ->first();

    $keys = $row ? array_keys((array) $row) : [];
    echo 'keys=' . implode(',', $keys) . PHP_EOL;

    if ($row) {
        $arr = (array) $row;
        foreach ([
            'raw_payload',
            'sikyu_total',
            'sikyu_total_amo',
            'koujyo_total',
            'koujyo_total_amo',
            'sasihiki_total',
            'sasihiki_sikyu',
            'other_total',
            'other_total_amo',
        ] as $key) {
            $value = array_key_exists($key, $arr) ? (string) $arr[$key] : '[missing]';
            echo $key . '=' . substr($value, 0, 200) . PHP_EOL;
        }
    }
} catch (Throwable $e) {
    echo get_class($e) . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
}
