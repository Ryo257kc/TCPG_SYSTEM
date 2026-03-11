<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$tables=[['sqlsrv','mx_staffs'],['sqlsrv','mx_stores'],['sqlsrv','mx_time_cards'],['sqlsrv_payroll','mx_allowance'],['sqlsrv_payroll','m_payroll_entries']];
foreach($tables as [$conn,$tbl]){
  echo "== {$conn}.{$tbl} ==\n";
  try{
    $rows=DB::connection($conn)->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME=? ORDER BY ORDINAL_POSITION",[$tbl]);
    foreach($rows as $r){ echo $r->COLUMN_NAME,"\n"; }
  }catch(Throwable $e){ echo "ERR: ",$e->getMessage(),"\n"; }
}
