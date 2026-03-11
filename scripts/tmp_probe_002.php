<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function dumpRow(string $conn, string $table, string $id): void {
    echo "=== {$conn} {$table} / {$id} ===\n";
    $rows = Illuminate\Support\Facades\DB::connection($conn)->table($table)->get();
    $hit = null;
    foreach ($rows as $r) {
        $a = (array)$r;
        foreach (['staff_id','kyuyo_staff_id','staffid','id_staff'] as $k) {
            if (array_key_exists($k, $a) && trim((string)$a[$k]) === $id) {
                $hit = $a;
                break 2;
            }
        }
    }
    if ($hit === null) {
        echo "(no matched row)\n\n";
        return;
    }
    foreach ($hit as $k => $v) {
        $vv = is_scalar($v) || $v === null ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE);
        echo $k, " = ", $vv, "\n";
    }
    echo "\n";
}

$id='002';
dumpRow('sqlsrv_payroll','dbo.mx_kihon',$id);
dumpRow('sqlsrv_payroll','dbo.mx_staff_shou',$id);
dumpRow('sqlsrv_payroll','dbo.mx_resident',$id);
