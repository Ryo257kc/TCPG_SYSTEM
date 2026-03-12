@php
$selectedRow = collect($rows)->firstWhere('staff_id', $selectedStaffId) ?: (count($rows) > 0 ? $rows[0] : null);
$summary = (array) ($selectedRow['summary'] ?? []);
$summaryPrev = (array) ($selectedRow['summary_prev'] ?? []);
$kihon = (array) ($selectedRow['kihon'] ?? []);
$staffMaster = (array) ($selectedRow['staff_master'] ?? []);
$shaho = (array) ($selectedRow['shaho'] ?? []);
$resident = (array) ($selectedRow['resident'] ?? []);

$kihonMasterView = [
    'decision_date' => $kihon['decision_date'] ?? null,
    'monthly_salary' => $kihon['monthly_salary'] ?? null,
    'hourly_pay' => $kihon['hourly_pay'] ?? null,
    'hourly_salary' => $kihon['hourly_salary'] ?? null,
    'executive_remu' => $kihon['executive_remu'] ?? null,
    'position_allow' => $kihon['position_allow'] ?? null,
    'duties_allow' => $kihon['duties_allow'] ?? null,
    'qualification_allow' => $kihon['qualification_allow'] ?? null,
    'claim_allow' => $kihon['claim_allow'] ?? null,
    'traffic_pay' => $kihon['traffic_pay'] ?? null,
    'adjustment_add' => $kihon['adjustment_add'] ?? null,
    'rent_subsidies' => $kihon['rent_subsidies'] ?? null,
    'rent_pay' => $kihon['rent_pay'] ?? null,
    'adjustment_pay' => $kihon['adjustment_pay'] ?? null,
    'fixed_overtime' => $kihon['fixed_overtime'] ?? null,
];

$shahoView = [
    'raise_year' => $shaho['raise_year'] ?? null,
    'kenpo' => $shaho['kenpo_amo'] ?? null,
    'kaigo' => $shaho['kaigo_amo'] ?? null,
    'kounen' => $shaho['kounen_amo'] ?? null,
    'kenpo_monthly_amo' => $shaho['kenpo_monthly_amo'] ?? null,
    'kounen_monthly_amo' => $shaho['kounen_monthly_amo'] ?? null,
    'kenpo_toukyu' => $shaho['kenpo_toukyu'] ?? null,
    'kounen_toukyu' => $shaho['kounen_toukyu'] ?? null,
];
$residentView = $resident;
$nFrom=function(array $source,string $k,int $d=0):string{$r=$source[$k]??0;if(is_string($r)){$r=trim($r);if($r===''){$r=0;}}$num=is_numeric($r)?(float)$r:0.0;return number_format($num,$d);};
$tFrom=function(array $source,string $k,string $def=''):string{$v=$source[$k]??$def; if($v===null){return $def;} return trim((string)$v);};
$n=function(string $k,int $d=0) use(&$summary,$nFrom):string{return $nFrom($summary,$k,$d);};
$t=function(string $k,string $def='') use(&$summary,$tFrom):string{return $tFrom($summary,$k,$def);};
$nm=function(string $k,int $d=0) use($kihon,$nFrom):string{return $nFrom($kihon,$k,$d);};
$tm=function(string $k,string $def='') use($kihon,$tFrom):string{return $tFrom($kihon,$k,$def);};
$b=function(string $k) use(&$summary):bool{return ((int)($summary[$k]??0))===1;};
$deltaFrom=function(string $k,int $d=0) use(&$summary,$summaryPrev):string{
    $cur = $summary[$k] ?? null;
    $prev = $summaryPrev[$k] ?? null;
    if(!is_numeric($cur) || !is_numeric($prev)){
        return '';
    }
    $diff = (float)$cur - (float)$prev;
    if(abs($diff) < 0.0000001){
        return '';
    }
    $sign = $diff > 0 ? '+' : '';
    return '(' . $sign . number_format($diff, $d) . ')';
};
$paymentDate = '-';
if (!empty($summary['supply_month'])) {
    $paymentRaw = trim((string) $summary['supply_month']);
    $timestamp = strtotime($paymentRaw);
    if ($timestamp !== false) {
        $paymentDate = date('Y/m/d', $timestamp);
    } else {
        $paymentDate = preg_split('/[ T]/', $paymentRaw)[0] ?? $paymentRaw;
    }
}

$labels=[
'work_in_num'=>'&#20986;&#21220;&#26085;&#25968;','absence_num'=>'&#27424;&#21220;&#26085;&#25968;','work_time'=>'&#23455;&#20685;&#26178;&#38291;','work_time_num'=>'&#20241;&#26085;&#21220;&#21209;&#26178;&#38291;','work_holiday_num'=>'&#20241;&#26085;&#21220;&#21209;&#26085;&#25968;','overtime'=>'&#26222;&#36890;&#27531;&#26989;&#26178;&#38291;','night_over_time'=>'&#28145;&#22812;&#27531;&#26989;','late_time'=>'&#36933;&#21051;&#26089;&#36864;&#26178;&#38291;','holiday_true'=>'&#26377;&#20241;&#26085;&#25968;','holiday_true_num'=>'&#26377;&#20241;&#27531;&#26085;&#25968;','days_closed'=>'&#20241;&#26989;&#26085;&#25968;','time_closed'=>'&#20241;&#26989;&#26178;&#38291;',
'peple_num'=>'&#20154;&#25968;','km'=>'&#36317;&#38626;','kitazaike'=>'&#21271;&#22312;&#23478;','higashi_kakogawa'=>'&#26481;&#21152;&#21476;&#24029;','tsubasa_harima'=>'&#12388;&#12400;&#12373;&#25773;&#30952;','own_cost'=>'&#33258;&#36027;','unpaid_amo'=>'&#26410;&#21454;&#37329;','sales_core_total'=>'&#21512;&#35336;','sakura_hari'=>'&#12373;&#12367;&#12425;&#37756;&#28792;','orita_hari'=>'&#32340;&#30000;&#37756;&#28792;','miyamoto_hari'=>'&#23470;&#26412;&#37756;&#28792;','yokoi_hari'=>'&#27178;&#20117;&#37756;&#28792;',
'basic_salary'=>'&#22522;&#26412;&#32102;','officer_com'=>'&#24441;&#21729;&#22577;&#37228;','month_salary'=>'&#26376;&#32102;','executive_reward'=>'&#24441;&#21729;&#22577;&#37228;','fixed_overtime_allowance'=>'&#22266;&#23450;&#27531;&#26989;&#25163;&#24403;','remaining_allowance'=>'&#27531;&#26989;&#25163;&#24403;','deepnight_overtime_shift'=>'&#28145;&#22812;&#27531;&#26989;&#25163;&#24403;','holiday_work_allowance'=>'&#20241;&#26085;&#20986;&#21220;&#25163;&#24403;','executive_allowance'=>'&#24441;&#32887;&#25163;&#24403;','position_allowance'=>'&#32887;&#21209;&#25163;&#24403;','manager_allowance'=>'&#31649;&#29702;&#25163;&#24403;','qualify_allowance'=>'&#36039;&#26684;&#25163;&#24403;','request_allowance'=>'&#35531;&#27714;&#25163;&#24403;','adjust_allowance'=>'&#35519;&#25972;&#25163;&#24403;','family_allowance'=>'&#23478;&#26063;&#25163;&#24403;','taxable_commuting'=>'&#35506;&#31246;&#36890;&#21220;&#36027;','non_taxable_commuting'=>'&#38750;&#35506;&#31246;&#36890;&#21220;&#36027;','holiday_allowance'=>'&#20241;&#26989;&#25163;&#24403;','absence_deduction'=>'&#27424;&#21220;&#25511;&#38500;','unpaid_deduction'=>'&#27424;&#21220;&#25511;&#38500;','taxation_sum'=>'&#35506;&#31246;&#21512;&#35336;','not_taxation_sum'=>'&#38750;&#35506;&#31246;&#21512;&#35336;','supply_sum'=>'&#25903;&#32102;&#21512;&#35336;',
'kenpo'=>'&#20581;&#24247;&#20445;&#38522;&#26009;','kaigo'=>'&#20171;&#35703;&#20445;&#38522;&#26009;','kounen'=>'&#21402;&#29983;&#24180;&#37329;&#20445;&#38522;&#26009;','koyou'=>'&#38599;&#29992;&#20445;&#38522;&#26009;','income_tax'=>'&#25152;&#24471;&#31246;','resident_tax'=>'&#20303;&#27665;&#31246;','rent_cost'=>'&#23478;&#36035;&#20195;','adjustment_cost'=>'&#35519;&#25972;&#25511;&#38500;','syaho_sum'=>'&#31038;&#20445;&#21512;&#35336;','deduction_sum'=>'&#25511;&#38500;&#21512;&#35336;','syaho_deduction_sum'=>'&#31038;&#20445;&#25511;&#38500;&#24460;&#35336;','koyou_office'=>'&#38599;&#29992;&#20445;&#38522;(&#20107;&#26989;&#25152;)','jidou_office'=>'&#20816;&#31461;&#25163;&#24403;(&#20107;&#26989;&#25152;)','rousai_office'=>'&#21172;&#28797;&#20445;&#38522;(&#20107;&#26989;&#25152;)',
'adjustment_year_end'=>'&#24180;&#26411;&#35519;&#25972;','koujyo_1'=>'&#23450;&#38989;&#28187;&#31246;&#38989;','cost_liquidation'=>'&#31435;&#26367;&#28165;&#31639;','company_advance_cost'=>'&#20250;&#31038;&#31435;&#26367;&#36027;&#29992;','fuyo_sum'=>'&#25206;&#39178;&#20154;&#25968;','work_kiso_num'=>'&#20986;&#21220;&#22522;&#30990;&#26085;&#25968;','tax_table'=>'&#31246;&#38989;&#34920;','set_work_time'=>'&#25152;&#23450;&#21172;&#20685;&#26178;&#38291;(&#26085;)','month_set_work_time'=>'&#25152;&#23450;&#21172;&#20685;&#26178;&#38291;(&#26376;)','day_set_work_time'=>'&#25152;&#23450;&#21172;&#20685;&#26178;&#38291;(&#36913;)','yukyu_month'=>'&#26377;&#20241;&#21152;&#31639;&#26376;','social_join'=>'&#31038;&#20445;&#21152;&#20837;','employment_join'=>'&#38599;&#20445;&#21152;&#20837;','memo'=>'&#12513;&#12514;','kyuyo_memo'=>'&#26126;&#32048;&#20633;&#32771;','transfer_amount_calc'=>'&#25391;&#36796;&#38989;',
'hourly_wage'=>'&#26178;&#32102;','hourly_wage_adjusted'=>'&#26178;&#32102;(&#35519;&#25972;&#24460;)','monthly_salary'=>'&#26376;&#32102;','hourly_pay'=>'&#26178;&#32102;','hourly_salary'=>'&#26178;&#32102;(&#35519;&#25972;&#24460;)','executive_remu'=>'&#24441;&#21729;&#22577;&#37228;','position_allow'=>'&#24441;&#32887;&#25163;&#24403;','duties_allow'=>'&#32887;&#21209;&#25163;&#24403;','qualification_allow'=>'&#36039;&#26684;&#25163;&#24403;','claim_allow'=>'&#35531;&#27714;&#25163;&#24403;','traffic_pay'=>'&#35506;&#31246;&#36890;&#21220;&#36027;','adjustment_add'=>'&#35519;&#25972;&#25163;&#24403;','rent_subsidies'=>'&#23478;&#26063;&#25163;&#24403;','rent_pay'=>'&#38750;&#35506;&#31246;&#36890;&#21220;&#36027;','adjustment_pay'=>'&#27424;&#21220;&#25511;&#38500;','fixed_overtime'=>'&#22266;&#23450;&#27531;&#26989;&#25163;&#24403;','kotei_sum'=>'&#22266;&#23450;&#21512;&#35336;','yakuin_sum'=>'&#24441;&#21729;&#21512;&#35336;','not_syaho'=>'&#31038;&#20445;&#23550;&#35937;&#22806;','not_rouho'=>'&#21172;&#20445;&#23550;&#35937;&#22806;','not_supply'=>'&#25903;&#32102;&#23550;&#35937;&#22806;','transfer_balance'=>'&#25391;&#36796;&#27531;&#38989;','rouho_target_sum'=>'&#21172;&#20445;&#23550;&#35937;&#21512;&#35336;','syaho_target_sum'=>'&#31038;&#20445;&#23550;&#35937;&#21512;&#35336;','kenpo_monthly_amo'=>'&#20581;&#20445;&#27161;&#28310;&#22577;&#37228;&#26376;&#38989;','kounen_monthly_amo'=>'&#21402;&#24180;&#27161;&#28310;&#22577;&#37228;&#26376;&#38989;','kenpo_toukyu'=>'&#20581;&#20445;&#31561;&#32026;','kounen_toukyu'=>'&#21402;&#24180;&#31561;&#32026;',
];
if (!empty($labelOverrides) && is_array($labelOverrides)) {
    $labels = array_merge($labels, $labelOverrides);
}
$l=function(string $k) use($labels):string{ return $labels[$k] ?? $k; };
$totalRowKeys = ['sales_core_total','taxation_sum','not_taxation_sum','supply_sum','syaho_sum','deduction_sum','syaho_deduction_sum','kotei_sum','yakuin_sum','rouho_target_sum','syaho_target_sum'];
$sectionMonth = function(array $source): string {
    foreach (['decision_date','raise_year','target_month','supply_month','apply_month','display_from'] as $k) {
        if (!array_key_exists($k, $source)) {
            continue;
        }
        $v = trim((string) ($source[$k] ?? ''));
        if ($v === '') {
            continue;
        }
        $ts = strtotime($v);
        if ($ts !== false) {
            return date('Y/m', $ts);
        }
        return $v;
    }
    return '-';
};
$supplySum=is_numeric($summary['supply_sum']??null)?(float)$summary['supply_sum']:0.0; $deductionSum=is_numeric($summary['deduction_sum']??null)?(float)$summary['deduction_sum']:0.0; $otherSum=(is_numeric($summary['adjustment_year_end']??null)?(float)$summary['adjustment_year_end']:0.0)+(is_numeric($summary['cost_liquidation']??null)?(float)$summary['cost_liquidation']:0.0)+(is_numeric($summary['company_advance_cost']??null)?(float)$summary['company_advance_cost']:0.0); $netPay=$supplySum-$deductionSum; $transferBalance=is_numeric($summary['transfer_balance']??null)?(float)$summary['transfer_balance']:0.0; $summary['transfer_amount_calc']=$netPay-$transferBalance;
$salesCoreTotal = 0.0;
foreach (['kitazaike','higashi_kakogawa','tsubasa_harima','own_cost','unpaid_amo'] as $salesKey) {
    $salesCoreTotal += is_numeric($summary[$salesKey] ?? null) ? (float) $summary[$salesKey] : 0.0;
}
$summary['sales_core_total'] = $salesCoreTotal;
$attendance=['&#21220;&#24608;' => [['work_in_num',0],['absence_num',0],['work_time',2],['work_time_num',2],['work_holiday_num',0],['overtime',2],['late_time',2],['holiday_true',2],['holiday_true_num',0],['time_closed',2],['days_closed',0],['work_kiso_num',0]]];
$sales=['&#22770;&#19978;' => [['peple_num',0],['km',0],['kitazaike',0],['higashi_kakogawa',0],['tsubasa_harima',0],['own_cost',0],['unpaid_amo',0],['sales_core_total',0],['sakura_hari',0],['orita_hari',0],['miyamoto_hari',0],['yokoi_hari',0]]];
$supply=['&#25903;&#32102;' => []];
$mid=['&#25511;&#38500;' => [['kenpo',0],['kaigo',0],['kounen',0],['koyou',0],['syaho_sum',0],['income_tax',0],['resident_tax',0],['rent_cost',0],['adjustment_cost',0],['koujyo_1',0],['deduction_sum',0],['koyou_office',0],['jidou_office',0],['rousai_office',0]],'&#12381;&#12398;&#20182;' => [['adjustment_year_end',0],['cost_liquidation',0],['company_advance_cost',0]],'&#25391;&#36796;' => [['transfer_amount_calc',0],['transfer_balance',0]]];
$baseInfoTitle = '&#22522;&#26412;&#24773;&#22577;';
$referenceTitle = '&#21442;&#32771;&#38598;&#35336;';
$masterTitle = '&#32102;&#19982;&#12510;&#12473;&#12479;';
$shahoTitle = '&#31038;&#20250;&#20445;&#38522;';
$residentTitle = '&#20303;&#27665;&#31246;';
$baseInfoView = [
    'display_from' => $summary['supply_month'] ?? null,
    'fuyo_sum' => $summary['fuyo_sum'] ?? null,
    'work_kiso_num' => $summary['work_kiso_num'] ?? null,
    'tax_table' => $staffMaster['tax_amount'] ?? ($summary['tax_table'] ?? null),
    'set_work_time' => $staffMaster['working_time'] ?? ($summary['set_work_time'] ?? null),
    'month_set_work_time' => $staffMaster['year_working_time'] ?? ($summary['month_set_work_time'] ?? null),
    'day_set_work_time' => $staffMaster['weekly_working_time'] ?? ($summary['day_set_work_time'] ?? null),
    'yukyu_month' => $staffMaster['yukyu_month'] ?? ($summary['yukyu_month'] ?? null),
    'social_join' => $staffMaster['syaho'] ?? ($summary['social_join'] ?? null),
    'employment_join' => $staffMaster['koyou'] ?? ($summary['employment_join'] ?? null),
    'memo' => $staffMaster['memo'] ?? ($summary['memo'] ?? null),
];
$rightA=[
    $baseInfoTitle => [['fuyo_sum',0],['tax_table',-1],['set_work_time',2],['day_set_work_time',2],['month_set_work_time',2],['yukyu_month',0],['social_join',0],['employment_join',0],['memo',-1]],
    $referenceTitle => [['kotei_sum',0],['yakuin_sum',0],['not_syaho',0],['not_rouho',0],['not_supply',0],['rouho_target_sum',0],['syaho_target_sum',0],['syaho_deduction_sum',0]]
];
$rightB=[
    $masterTitle => [['monthly_salary',0],['hourly_pay',0],['hourly_salary',0],['executive_remu',0],['position_allow',0],['duties_allow',0],['qualification_allow',0],['claim_allow',0],['traffic_pay',0],['adjustment_add',0],['rent_subsidies',0],['rent_pay',0],['adjustment_pay',0],['fixed_overtime',0]],
    $shahoTitle => [['kenpo',0],['kaigo',0],['kounen',0],['kenpo_monthly_amo',0],['kounen_monthly_amo',0],['kenpo_toukyu',0],['kounen_toukyu',0]],
    $residentTitle => [['resident_tax',0]]
];

$allowanceEntries = is_array($allowanceEntries ?? null) ? $allowanceEntries : [];
$supplyTitle = array_key_first($supply);
$supplyCoreKeys = [];
foreach (($supply[$supplyTitle] ?? []) as $it) {
    $supplyCoreKeys[(string)($it[0] ?? '')] = true;
}

foreach ($allowanceEntries as $entry) {
    $k = trim((string) ($entry['key'] ?? ''));
    if ($k === '' || !array_key_exists($k, $summary) || isset($supplyCoreKeys[$k])) {
        continue;
    }
    $supply[$supplyTitle][] = [$k, 0];
    $supplyCoreKeys[$k] = true;
}

foreach (['taxation_sum', 'not_taxation_sum', 'supply_sum'] as $k) {
    if (array_key_exists($k, $summary) && !isset($supplyCoreKeys[$k])) {
        $supply[$supplyTitle][] = [$k, 0];
        $supplyCoreKeys[$k] = true;
    }
}

$filterBySummary = static function (array $sections) use ($summary): array {
    $out = [];
    foreach ($sections as $title => $items) {
        $filtered = [];
        foreach ($items as $it) {
            $k = (string) ($it[0] ?? '');
            if ($k === '') {
                continue;
            }
            if (array_key_exists($k, $summary)) {
                $filtered[] = $it;
            }
        }
        $out[$title] = $filtered;
    }
    return $out;
};

$attendance = $filterBySummary($attendance);
$sales = $filterBySummary($sales);
$supply = $filterBySummary($supply);
$mid = $filterBySummary($mid);
@endphp
