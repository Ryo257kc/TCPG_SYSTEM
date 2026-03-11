<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::connection('sqlsrv_payroll')
    ->table('dbo.m_payroll_entries')
    ->where('is_bonus', 0)
    ->whereNotNull('raw_payload')
    ->orderByDesc('payroll_entry_id')
    ->limit(200)
    ->get(['raw_payload']);

$counts = [];
foreach ($rows as $r) {
    $p = json_decode((string)$r->raw_payload, true);
    if (!is_array($p)) continue;
    foreach ($p as $k => $v) {
        if (!is_string($k)) continue;
        $counts[$k] = ($counts[$k] ?? 0) + 1;
    }
}
arsort($counts);
foreach ($counts as $k => $c) {
    echo $k, "\t", $c, PHP_EOL;
}
