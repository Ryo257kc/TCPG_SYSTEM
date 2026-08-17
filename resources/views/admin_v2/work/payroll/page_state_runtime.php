<?php
$selectedRow = collect($rows)->firstWhere('staff_id', $selectedStaffId) ?: (count($rows) > 0 ? $rows[0] : null);
$summary = (array) ($selectedRow['summary'] ?? []);
$attendanceSource = (array) ($selectedRow['attendance_source'] ?? []);
$referenceCalc = (array) ($selectedRow['reference_calc'] ?? []);
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
    'traffic_day' => $staffMaster['traffic_day'] ?? null,
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
$nFrom = function (array $source, string $k, int $d = 0): string {
    $r = $source[$k] ?? 0;
    if (is_string($r)) {
        $r = trim($r);
        if ($r === '') {
            $r = 0;
        }
    }
    $num = is_numeric($r) ? (float)$r : 0.0;
    return number_format($num, $d);
};
$tFrom = function (array $source, string $k, string $def = ''): string {
    $v = $source[$k] ?? $def;
    if ($v === null) {
        return $def;
    }
    return trim((string)$v);
};
$n = function (string $k, int $d = 0) use (&$summary, $nFrom): string {
    return $nFrom($summary, $k, $d);
};
$t = function (string $k, string $def = '') use (&$summary, $tFrom): string {
    return $tFrom($summary, $k, $def);
};
$nm = function (string $k, int $d = 0) use ($kihon, $nFrom): string {
    return $nFrom($kihon, $k, $d);
};
$tm = function (string $k, string $def = '') use ($kihon, $tFrom): string {
    return $tFrom($kihon, $k, $def);
};
$b = function (string $k) use (&$summary): bool {
    return ((int)($summary[$k] ?? 0)) === 1;
};
$deltaFrom = function (string $k, int $d = 0) use (&$summary, $summaryPrev): string {
    $cur = $summary[$k] ?? null;
    $prev = $summaryPrev[$k] ?? null;
    if (!is_numeric($cur) || !is_numeric($prev)) {
        return '';
    }
    $curRounded = round((float)$cur, $d);
    $prevRounded = round((float)$prev, $d);
    $diff = $curRounded - $prevRounded;
    if (abs($diff) < 0.0000001) {
        return '';
    }
    $sign = $diff > 0 ? '+' : '';
    return '(' . $sign . number_format($diff, $d) . ')';
};
$attendanceValueFrom = function (string $k, int $d = 0) use ($attendanceSource, $nFrom): string {
    $aliases = [
        'work_time_num' => 'holiday_work_time',
        'late_time' => 'late_early_time',
    ];
    $sourceKey = $aliases[$k] ?? $k;
    if (!array_key_exists($sourceKey, $attendanceSource)) {
        return '';
    }
    return $nFrom($attendanceSource, $sourceKey, $d);
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
$labels = [
    'work_in_num' => '総出勤日数',
    'work_in_num_net' => '平日出勤日数',
    'work_time_net' => '所定時間',
    'absence_num' => '欠勤日数',
    'work_time' => '総出勤時間',
    'work_time_num' => '休日出勤時間',
    'work_holiday_num' => '休日出勤日数',
    'overtime' => '普通残業時間',
    'night_over_time' => '深夜残業',
    'late_time' => '遅刻早退時間',
    'holiday_true' => '有休日数',
    'holiday_true_num' => '有休残日数',
    'days_closed' => '休業日数',
    'time_closed' => '休業時間',
    'peple_num' => '人数',
    'km' => '距離',
    'kitazaike' => '北在家',
    'higashi_kakogawa' => '東加古川',
    'tsubasa_harima' => 'つばさ播磨',
    'own_cost' => '自費',
    'unpaid_amo' => '未収金',
    'sales_core_total' => '合計',
    'sakura_hari' => 'さくら鼻灸',
    'orita_hari' => '織田鼻灸',
    'miyamoto_hari' => '宮本鼻灸',
    'yokoi_hari' => '横井鼻灸',
    'basic_salary' => '基本給',
    'month_salary' => '月給',
    'executive_reward' => '役員報酬',
    'fixed_overtime_allowance' => '固定残業手当',
    'remaining_allowance' => '残業手当',
    'deepnight_overtime_shift' => '深夜残業手当',
    'holiday_work_allowance' => '休日出勤手当',
    'executive_allowance' => '役職手当',
    'position_allowance' => '職務手当',
    'manager_allowance' => '管理手当',
    'qualify_allowance' => '資格手当',
    'request_allowance' => '請求手当',
    'adjust_allowance' => '調整手当',
    'family_allowance' => '家族手当',
    'taxable_commuting' => '課税通勤費',
    'non_taxable_commuting' => '非課税通勤費',
    'holiday_allowance' => '休業手当',
    'absence_deduction' => '欠勤控除',
    'unpaid_deduction' => '欠勤控除',
    'taxation_sum' => '課税合計',
    'not_taxation_sum' => '非課税合計',
    'supply_sum' => '支給合計',
    'kenpo' => '健康保険料',
    'kaigo' => '介護保険料',
    'child_support_funds' => '子ども支援金',
    'kounen' => '厚生年金保険料',
    'koyou' => '雇用保険料',
    'income_tax' => '所得税',
    'resident_tax' => '住民税',
    'rent_cost' => '家賃代',
    'adjustment_cost' => '調整控除',
    'syaho_sum' => '社保合計',
    'deduction_sum' => '控除合計',
    'syaho_deduction_sum' => '社保控除後計',
    'koyou_office' => '雇用保険(事業所)',
    'jidou_office' => '児童手当(事業所)',
    'rousai_office' => '労災保険(事業所)',
    'adjustment_year_end' => '年末調整',
    'koujyo_1' => '定額減税額',
    'cost_liquidation' => '立替精算',
    'company_advance_cost' => '会社立替費用',
    'fuyo_sum' => '扶養人数',
    'work_kiso_num' => '出勤基礎日数',
    'tax_table' => '税額表',
    'set_work_time' => '所定労働時間(日)',
    'month_set_work_time' => '所定労働時間(月)',
    'day_set_work_time' => '所定労働時間(週)',
    'yukyu_month' => '有休加算月',
    'social_join' => '社保加入',
    'employment_join' => '雇保加入',
    'memo' => 'メモ',
    'kyuyo_memo' => '明細備考',
    'transfer_amount_calc' => '振込額',
    'hourly_wage' => '時給',
    'hourly_wage_adjusted' => '時給(調整後)',
    'monthly_salary' => '月給',
    'hourly_pay' => '時給',
    'hourly_salary' => '時給(調整後)',
    'executive_remu' => '役員報酬',
    'position_allow' => '役職手当',
    'duties_allow' => '職務手当',
    'qualification_allow' => '資格手当',
    'claim_allow' => '請求手当',
    'traffic_pay' => '交通費',
    'traffic_day' => '日額交通費',
    'adjustment_add' => '調整手当',
    'rent_subsidies' => '家族手当',
    'rent_pay' => '家賃補助',
    'adjustment_pay' => '欠勤控除',
    'fixed_overtime' => '固定残業手当',
    'kotei_sum' => '固定合計',
    'yakuin_sum' => '役員合計',
    'not_syaho' => '社保対象外',
    'not_rouho' => '労保対象外',
    'not_supply' => '支給対象外',
    'transfer_balance' => '振込残額',
    'rouho_target_sum' => '労保対象合計',
    'syaho_target_sum' => '社保対象合計',
    'kenpo_monthly_amo' => '健保標準報酬月額',
    'kounen_monthly_amo' => '厚年標準報酬月額',
    'kenpo_toukyu' => '健保等級',
    'kounen_toukyu' => '厚年等級',
    'warimasi_base' => '割増基礎',
    'koujyo_base' => '控除基礎',
];
if (!empty($labelOverrides) && is_array($labelOverrides)) {
    $labels = array_merge($labels, $labelOverrides);
}
$l = function (string $k) use ($labels): string {
    return $labels[$k] ?? $k;
};
$totalRowKeys = ['sales_core_total', 'taxation_sum', 'not_taxation_sum', 'supply_sum', 'syaho_sum', 'deduction_sum', 'syaho_deduction_sum', 'kotei_sum', 'yakuin_sum', 'rouho_target_sum', 'syaho_target_sum'];
// work_in_num/work_time（総出勤日数・総出勤時間）は編集はそのまま残しつつ、
// 見出し的に太字強調したいだけなので$totalRowKeysとは別扱いにする（2026-08-17）。
$boldOnlyRowKeys = ['work_in_num', 'work_time'];
$sectionMonth = function (array $source): string {
    foreach (['decision_date', 'raise_year', 'target_month', 'supply_month', 'apply_month', 'display_from'] as $k) {
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
$supplySum = is_numeric($summary['supply_sum'] ?? null) ? (float)$summary['supply_sum'] : 0.0;
$deductionSum = is_numeric($summary['deduction_sum'] ?? null) ? (float)$summary['deduction_sum'] : 0.0;
$otherSum =
    (is_numeric($summary['cost_liquidation'] ?? null) ? (float)$summary['cost_liquidation'] : 0.0)
    - (is_numeric($summary['adjustment_year_end'] ?? null) ? (float)$summary['adjustment_year_end'] : 0.0)
    + (is_numeric($summary['company_advance_cost'] ?? null) ? (float)$summary['company_advance_cost'] : 0.0);
$netPay = is_numeric($summary['transfer_amount'] ?? null) ? (float)$summary['transfer_amount'] : 0.0;
$summary['transfer_amount_calc'] = $netPay;
$salesCoreTotal = 0.0;
foreach (['kitazaike','higashi_kakogawa','tsubasa_harima','sakura_hari','orita_hari','miyamoto_hari','yokoi_hari','own_cost','unpaid_amo'] as $salesKey) {
    $salesCoreTotal += is_numeric($summary[$salesKey] ?? null) ? (float) $summary[$salesKey] : 0.0;
}
$summary['sales_core_total'] = $salesCoreTotal;
$attendance = ['勤怠' => [['work_in_num', 2], ['work_in_num_net', 2], ['work_holiday_num', 2], ['absence_num', 2], ['work_time', 2], ['work_time_net', 2], ['work_time_num', 2], ['overtime', 2], ['late_time', 2], ['holiday_true', 2], ['holiday_true_num', 2], ['time_closed', 2], ['days_closed', 2], ['work_kiso_num', 2]]];
$sales = ['売上' => [['peple_num', 0], ['km', 0], ['kitazaike', 0], ['higashi_kakogawa', 0], ['tsubasa_harima', 0], ['sakura_hari', 0], ['orita_hari', 0], ['miyamoto_hari', 0], ['yokoi_hari', 0], ['own_cost', 0], ['unpaid_amo', 0], ['sales_core_total', 0]]];
$supply = ['支給' => []];
$mid = [
    '控除' => [['kenpo', 0], ['kaigo', 0], ['child_support_funds', 0], ['kounen', 0], ['koyou', 0], ['syaho_sum', 0], ['income_tax', 0], ['resident_tax', 0], ['rent_cost', 0], ['adjustment_cost', 0], ['koujyo_1', 0], ['deduction_sum', 0], ['koyou_office', 0], ['jidou_office', 0], ['rousai_office', 0]],
    'その他' => [['adjustment_year_end', 0], ['cost_liquidation', 0], ['company_advance_cost', 0]],
    '振込' => [['transfer_amount_calc', 0], ['transfer_balance', 0]],
];
$baseInfoTitle = '基本情報';
$referenceTitle = '参考集計';
$masterTitle = '給与マスタ';
$shahoTitle = '社会保険';
$residentTitle = '住民税';
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
$rightA = [
    $baseInfoTitle => [['fuyo_sum', 0], ['tax_table', -1], ['set_work_time', 2], ['day_set_work_time', 2], ['month_set_work_time', 2], ['yukyu_month', 0], ['social_join', 0], ['employment_join', 0], ['memo', -1]],
    $referenceTitle => [['kotei_sum', 0], ['yakuin_sum', 0], ['warimasi_base', 0], ['koujyo_base', 0], ['not_syaho', 0], ['not_rouho', 0], ['not_supply', 0], ['rouho_target_sum', 0], ['syaho_target_sum', 0], ['syaho_deduction_sum', 0]],
];
$rightB = [
    $masterTitle => [['monthly_salary', 0], ['hourly_pay', 0], ['hourly_salary', 0], ['executive_remu', 0], ['position_allow', 0], ['duties_allow', 0], ['qualification_allow', 0], ['claim_allow', 0], ['traffic_pay', 0], ['traffic_day', 0], ['adjustment_add', 0], ['rent_subsidies', 0], ['rent_pay', 0], ['adjustment_pay', 0], ['fixed_overtime', 0]],
    $shahoTitle => [['kenpo', 0], ['kaigo', 0], ['kounen', 0], ['kenpo_monthly_amo', 0], ['kounen_monthly_amo', 0], ['kenpo_toukyu', 0], ['kounen_toukyu', 0]],
    $residentTitle => [['resident_tax', 0]],
];

$referenceView = [
    'warimasi_base' => $referenceCalc['warimasi_base'] ?? null,
    'koujyo_base' => $referenceCalc['koujyo_base'] ?? null,
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
?>
