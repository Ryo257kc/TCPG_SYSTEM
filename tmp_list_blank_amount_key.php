<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('sqlsrv_payroll');
$targets = $conn->table('dbo.t_allowance')
    ->select('allowance_no', 'office_name', 'allowance_name', 'slot_no', 'amount_column_key')
    ->whereRaw("LTRIM(RTRIM(ISNULL(amount_column_key, ''))) = ''")
    ->orderBy('allowance_no')
    ->get();

echo 'blank_key_count=' . $targets->count() . PHP_EOL;
foreach ($targets as $t) {
    echo implode("\t", [
        (int)$t->allowance_no,
        trim((string)($t->office_name ?? '')),
        trim((string)($t->allowance_name ?? '')),
        (int)($t->slot_no ?? 0),
        trim((string)($t->amount_column_key ?? '')),
    ]) . PHP_EOL;
}
