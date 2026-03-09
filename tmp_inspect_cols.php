<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = ['m_time_cards','t_time_card','t_calendar','m_staffs'];
foreach ($tables as $t) {
    echo "==== {$t} ====\n";
    try {
        $rows = Illuminate\Support\Facades\DB::connection('sqlsrv')->select(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
            [$t]
        );
        foreach ($rows as $r) {
            echo $r->COLUMN_NAME . "\n";
        }
    } catch (Throwable $e) {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}
