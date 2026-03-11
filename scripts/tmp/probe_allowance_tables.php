<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $conn = DB::connection('sqlsrv_payroll');
    $pong = $conn->select('SELECT 1 AS x');
    echo "connected=" . ($pong[0]->x ?? '0') . PHP_EOL;

    $tables = ['m_allowance','mx_allowance','t_allowance'];
    foreach ($tables as $t) {
        $exists = (int)($conn->select("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='{$t}'")[0]->c ?? 0);
        echo "TABLE {$t}: " . ($exists ? 'exists' : 'missing') . PHP_EOL;
        if (!$exists) { continue; }

        $cnt = (int)($conn->select("SELECT COUNT(*) c FROM dbo.[{$t}]")[0]->c ?? 0);
        echo "  rows: {$cnt}" . PHP_EOL;

        $cols = $conn->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='{$t}' ORDER BY ORDINAL_POSITION");
        echo "  cols: " . implode(',', array_map(fn($r) => $r->COLUMN_NAME, $cols)) . PHP_EOL;

        $sample = $conn->select("SELECT TOP 5 * FROM dbo.[{$t}]");
        foreach ($sample as $i => $row) {
            echo "  sample" . ($i + 1) . ': ' . json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
    }
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . PHP_EOL;
}
