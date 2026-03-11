<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$rows = DB::connection('sqlsrv_payroll')->select("SELECT TOP 20 office_name, allowance_no, allowance_name, slot_no, amount_column_key FROM dbo.mx_allowance ORDER BY office_name, allowance_no");
echo "mx_allowance sample\n";
foreach ($rows as $r) {
    echo ($r->office_name ?? 'NULL')."\t".($r->allowance_no ?? 'NULL')."\t".($r->allowance_name ?? 'NULL')."\t".($r->slot_no ?? 'NULL')."\t".($r->amount_column_key ?? 'NULL')."\n";
}

echo "\ncompany/staff/store sample\n";
$rows2 = DB::connection('sqlsrv')->select("SELECT TOP 20 LTRIM(RTRIM(ms.staff_id)) staff_id, ms.staff_name, ms.section, st.store_code, st.store_name, st.company_name, st.company_id, mc.company_id mc_company_id, mc.company_code FROM dbo.mx_staffs ms LEFT JOIN dbo.mx_stores st ON ms.section = st.store_code LEFT JOIN dbo.m_companies mc ON st.company_name = mc.company_name ORDER BY ms.staff_id");
foreach ($rows2 as $r) {
    echo ($r->staff_id??'')."\t".($r->staff_name??'')."\tsec=".($r->section??'')."\tst.company_id=".($r->company_id??'')."\tst.company_name=".($r->company_name??'')."\tmc.id=".($r->mc_company_id??'')."\tmc.code=".($r->company_code??'')."\n";
}
