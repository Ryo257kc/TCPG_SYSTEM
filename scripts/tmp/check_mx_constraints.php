<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

function report(string $connName): void {
    echo "=== {$connName} ===\n";
    $sql = "
SELECT
  t.name AS table_name,
  MAX(CASE WHEN i.is_primary_key=1 THEN 1 ELSE 0 END) AS has_pk,
  SUM(CASE WHEN i.is_unique=1 THEN 1 ELSE 0 END) AS unique_indexes,
  SUM(CASE WHEN i.index_id IS NOT NULL THEN 1 ELSE 0 END) AS total_indexes
FROM sys.tables t
LEFT JOIN sys.indexes i ON i.object_id=t.object_id AND i.index_id>0
WHERE t.schema_id=SCHEMA_ID('dbo')
  AND t.name LIKE 'mx[_]%'
GROUP BY t.name
ORDER BY t.name";

    $rows = DB::connection($connName)->select($sql);
    $noPk = 0;
    foreach ($rows as $r) {
        if ((int)$r->has_pk === 0) { $noPk++; }
    }
    echo 'mx table count: '.count($rows)."\n";
    echo 'mx tables without PK: '.$noPk."\n";

    if ($noPk > 0) {
        echo "-- no PK tables --\n";
        foreach ($rows as $r) {
            if ((int)$r->has_pk === 0) {
                echo $r->table_name."\n";
            }
        }
    }

    $fkSql = "
SELECT COUNT(*) AS fk_count
FROM sys.foreign_keys fk
JOIN sys.tables t ON t.object_id=fk.parent_object_id
WHERE t.schema_id=SCHEMA_ID('dbo')
  AND t.name LIKE 'mx[_]%'";
    $fk = DB::connection($connName)->select($fkSql);
    echo 'foreign keys on mx tables: '.((int)$fk[0]->fk_count)."\n\n";
}

report('sqlsrv');
report('sqlsrv_payroll');
