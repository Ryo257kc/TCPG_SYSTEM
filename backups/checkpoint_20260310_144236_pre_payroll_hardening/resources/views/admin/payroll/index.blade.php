<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 管理者給与計算</title>
    <style>
        body { margin:0; font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif; background:#ecf2fb; color:#1f2937; }
        .wrap { width:min(1760px,99vw); margin:14px auto; }
        .top { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-bottom:10px; }
        .title { font-size:24px; font-weight:800; color:#1f4f8f; }
        .btn { display:inline-block; text-decoration:none; border:1px solid #d3dff0; background:#fff; border-radius:10px; padding:8px 12px; color:#1f2937; }
        .panel { background:#fff; border:1px solid #d3dff0; border-radius:14px; padding:10px; }
        .filters { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:12px; }
        .filters input, .filters select, .filters button { height:32px; border:1px solid #cfd9e8; border-radius:8px; padding:0 10px; background:#fff; }
        .filters button { cursor:pointer; }
        .seed-ops { display:none; border:1px solid #d7e4f8; background:#f8fbff; border-radius:10px; padding:10px; margin:0 0 12px; }
        .seed-ops.is-open { display:block; }
        .seed-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:6px 10px; max-height:180px; overflow:auto; border:1px solid #dce7f7; background:#fff; border-radius:8px; padding:8px; margin-bottom:8px; }
        .seed-check { display:flex; align-items:center; gap:6px; font-size:12px; color:#344054; }
        .seed-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .flash { margin-bottom:10px; border:1px solid #cfe0fb; background:#edf4ff; color:#1f4f8f; border-radius:8px; padding:8px 10px; font-size:13px; }
        .meta { color:#667085; font-size:13px; margin-bottom:10px; }
        .empty { text-align:center; color:#667085; padding:22px; }

        .layout { display:grid; grid-template-columns: 220px 1fr; gap:10px; min-height: 72vh; align-items:stretch; }
        .staff-list { background:#f7faff; border:1px solid #d9e5f7; border-radius:10px; padding:6px; overflow:auto; align-self:start; }
        .staff-item { display:block; text-decoration:none; color:inherit; border:1px solid #dce6f5; background:#fff; border-radius:8px; padding:6px 8px; margin-bottom:6px; }
        .staff-item.active { border-color:#6aa0f0; box-shadow:0 0 0 2px rgba(106,160,240,.18); }
        .staff-id { display:flex; gap:4px; align-items:center; flex-wrap:wrap; font-size:11px; color:#475467; }
        .staff-name { margin-top:4px; font-size:14px; font-weight:700; color:#1f2937; }
        .staff-division { font-size:11px; color:#344054; background:#eef2f7; padding:1px 5px; border-radius:999px; }
        .lock-pill { font-size:10px; padding:1px 6px; border-radius:999px; border:1px solid transparent; }
        .lock-pill.locked { color:#0f5132; background:#d1fae5; border-color:#a7f3d0; }
        .lock-pill.unlocked { color:#9a3412; background:#ffedd5; border-color:#fed7aa; }
        .lock-pill.pending { color:#475467; background:#e5e7eb; border-color:#d1d5db; }
        .lock-pill.att-checked { color:#0f5132; background:#dcfce7; border-color:#86efac; }
        .lock-pill.att-unchecked { color:#b42318; background:#fee4e2; border-color:#fecaca; }

        .detail-pane { background:#f8fbff; border:1px solid #dbe7f7; border-radius:12px; padding:8px; }
        .summary-cards { display:grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap:8px; margin-bottom:8px; }
        .card { background:#fff; border:1px solid #d9e5f7; border-radius:10px; padding:8px; min-height:52px; }
        .card .k { font-size:11px; color:#667085; margin-bottom:4px; }
        .card .v { font-size:15px; font-weight:700; color:#1f2937; line-height:1.35; }
        .card.no-head .v { margin-top:0; font-size:13px; font-weight:600; }
        .btn-act { border:1px solid #cfd9e8; border-radius:8px; padding:6px 10px; background:#fff; color:#1f2937; cursor:pointer; font-size:12px; }
        .btn-act.primary { background:#1f4f8f; border-color:#1f4f8f; color:#fff; }

        .sections { margin-top:6px; }
        .section-grid { display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:8px; align-items:start; }
        .section-stack { display:grid; gap:8px; }
        .section { background:#fff; border:1px solid #d9e5f7; border-radius:10px; padding:8px; }
        .section.reference { background:#f6f8fc; }
        .section h3 { margin:0 0 6px; font-size:13px; font-weight:800; color:#1f2937; }
        .memo-box { margin-top:8px; border-top:1px solid #e4ebf7; padding-top:8px; }
        .memo-title { font-size:12px; font-weight:700; color:#475467; margin-bottom:4px; }
        .memo-body { min-height:110px; white-space:pre-wrap; word-break:break-word; font-size:12px; color:#111827; background:#fff; border:1px solid #dde6f5; border-radius:8px; padding:8px; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th, td { border-bottom:1px solid #edf2fb; padding:4px 6px; font-size:12px; vertical-align:top; }
        th { width:48%; text-align:left; color:#334155; font-weight:700; }
        td { text-align:right; color:#111827; }
        .num-input { display:none; width:100%; box-sizing:border-box; text-align:right; border:1px solid #cfd9e8; border-radius:6px; height:28px; padding:0 8px; }
        .cell-view { display:inline; }
        .edit-mode .cell-view { display:none; }
        .edit-mode .num-input { display:inline-block; }
        .recalc-koyou-btn { display:none; }
        .edit-mode .recalc-koyou-btn { display:inline-block; }
        .recalc-premium-btn { display:none; }
        .edit-mode .recalc-premium-btn { display:inline-block; }
        .edit-mode #btn-toggle-lock { display:none !important; }

        .earnings-total th, .earnings-total td,
        .deduction-total th, .deduction-total td,
        .sales-total th, .sales-total td {
            background:#edf4ff;
            color:#1f4f8f;
            font-weight:800;
        }

        .totals { margin-top:8px; display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:8px; }
        .total-card { background:#eaf2ff; border:1px solid #cfe0fb; border-radius:10px; padding:8px; }
        .total-card .k { font-size:12px; color:#375a8c; }
        .total-card .v { margin-top:4px; font-size:18px; font-weight:800; color:#1f4f8f; text-align:right; }

        @media (max-width: 1100px) {
            .layout { grid-template-columns: 200px 1fr; }
            .summary-cards { grid-template-columns: repeat(3, minmax(0,1fr)); }
            .section-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .totals { grid-template-columns: repeat(2, minmax(0,1fr)); }
        }
        @media (max-width: 760px) {
            .layout { grid-template-columns: 1fr; }
            .staff-list { max-height:none; }
            .summary-cards { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .section-grid { grid-template-columns: 1fr; }
            .totals { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    @php
        $attendanceMonth = date('Y-m', strtotime($selectedMonth . '-01 -1 month'));
    @endphp
    <div class="top">
        <div class="title">TCPG SYSTEM 管理者給与計算</div>
        <div style="display:flex; gap:8px; align-items:center;">
            <a class="btn" href="{{ route('admin.attendance.index', ['month' => $attendanceMonth, 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">勤怠管理</a>
            <a class="btn" href="{{ route('admin.dashboard', ['page' => 'bonus-detail']) }}">賞与計算</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
        </div>
    </div>

    <section class="panel">
        <form method="GET" class="filters">
            <label for="month">対象月</label>
            <input id="month" name="month" type="month" value="{{ $selectedMonth }}">

            <label for="company_id">会社</label>
            <select id="company_id" name="company_id">
                <option value="">全社</option>
                @foreach ($companyOptions as $company)
                    <option value="{{ $company['company_id'] }}" @selected($selectedCompanyId === $company['company_id'])>
                        {{ $company['company_name'] }}
                    </option>
                @endforeach
            </select>

            <label for="staff_id">スタッフ</label>
            <select id="staff_id" name="staff_id">
                <option value="">全員</option>
                @foreach ($staffOptions as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($selectedStaffId === $staff['staff_id'])>
                        {{ $staff['staff_id'] }} {{ $staff['staff_name'] }}
                    </option>
                @endforeach
            </select>

            <button type="submit">表示</button>
            <button
                type="submit"
                formmethod="GET"
                formaction="{{ route('admin.payroll.csv') }}"
            >
                CSV
            </button>
            <button type="button" id="payroll-seed-toggle">給与データ作成</button>
        </form>
        <div id="payroll-seed-ops" class="seed-ops">
            <div class="seed-grid">
                @forelse(($bulkStaffRows ?? []) as $staff)
                    <label class="seed-check">
                        <input
                            type="checkbox"
                            class="js-payroll-seed-target"
                            value="{{ $staff['staff_id'] }}"
                            @checked($selectedStaffId !== '' && $selectedStaffId === $staff['staff_id'])
                        >
                        <span>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</span>
                    </label>
                @empty
                    <div style="font-size:12px; color:#667085;">選択可能なスタッフがいません。</div>
                @endforelse
            </div>
            <div class="seed-actions">
                <button type="button" class="js-payroll-seed-all">全選択</button>
                <button type="button" class="js-payroll-seed-clear">全解除</button>
                <form id="payroll-seed-create-form" method="POST" action="{{ route('admin.payroll.seed-bulk-create') }}" style="margin:0;">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                    <button type="submit">給与データ一括作成</button>
                </form>
                <form id="payroll-seed-delete-form" method="POST" action="{{ route('admin.payroll.seed-bulk-delete') }}" style="margin:0;" onsubmit="return confirm('選択スタッフの対象月給与データを削除します。よろしいですか？');">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                    <button type="submit" class="btn-danger">給与データ一括削除</button>
                </form>
            </div>
        </div>
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        <div class="meta">
            対象者数 {{ count($summaryRows) }} / 勤怠確定済 {{ (int)($attendanceCheckedCount ?? 0) }} / 勤怠未確定 {{ (int)($attendanceUncheckedCount ?? 0) }}
        </div>

        @if (empty($summaryRows))
            <div class="empty">対象データがありません</div>
        @else
            @php
                $payload = [];
                foreach (($selectedDetails ?? []) as $detail) {
                    $name = (string)($detail['name'] ?? '');
                    $value = (string)($detail['value'] ?? '');
                    if (str_starts_with($name, 'payload.')) {
                        $payload[substr($name, 8)] = $value;
                    }
                }
                if ($payload === []) {
                    foreach (($selectedDetails ?? []) as $detail) {
                        $name = (string)($detail['name'] ?? '');
                        $value = (string)($detail['value'] ?? '');
                        if ($name !== 'staff_name') {
                            $payload[$name] = $value;
                        }
                    }
                }
                $selectedStaffMeta = $selectedStaffMeta ?? [];
                $selectedMasterInfo = $selectedMasterInfo ?? [];
                $kihonInfo = (array)($selectedMasterInfo['kihon'] ?? []);
                $syahoInfo = (array)($selectedMasterInfo['syaho'] ?? []);
                $residentInfo = (array)($selectedMasterInfo['resident'] ?? []);

                $getVal = function (array $keys, string $default = '-') use ($payload): string {
                    foreach ($keys as $k) {
                        if (array_key_exists($k, $payload) && trim((string)$payload[$k]) !== '') {
                            return (string)$payload[$k];
                        }
                    }
                    return $default;
                };
                $getRaw = function (array $keys, string $default = '') use ($payload): string {
                    foreach ($keys as $k) {
                        if (array_key_exists($k, $payload) && trim((string)$payload[$k]) !== '') {
                            return (string)$payload[$k];
                        }
                    }
                    return $default;
                };
                $hasVal = function (array $keys) use ($payload): bool {
                    foreach ($keys as $k) {
                        if (!array_key_exists($k, $payload)) {
                            continue;
                        }
                        $v = trim((string)($payload[$k] ?? ''));
                        if ($v !== '' && $v !== '-') {
                            return true;
                        }
                    }
                    return false;
                };
                                $formatComma = function (string $v): string {
                    $n = str_replace([',', '¥', ' '], '', trim($v));
                    if ($n === '' || $n === '-') {
                        return $v;
                    }
                    if (!is_numeric($n)) {
                        return $v;
                    }
                    $f = (float)$n;
                    if (abs($f - floor($f)) < 0.000001) {
                        return number_format((int)$f);
                    }
                    return number_format($f, 2);
                };
                $toFloat = function (string $v): float {
                    $n = str_replace([',', '¥', ' '], '', trim($v));
                    if ($n === '' || $n === '-' || !is_numeric($n)) {
                        return 0.0;
                    }
                    return (float)$n;
                };
                $fmtCompact = function (float $v): string {
                    $s = number_format($v, 2, '.', '');
                    return rtrim(rtrim($s, '0'), '.');
                };

                $isZero = function (string $v): bool {
                    $n = str_replace([',', '¥', ' '], '', trim($v));
                    return $n === '' || $n === '-' || (is_numeric($n) && (float)$n == 0.0);
                };

                $attendance = [
                    ['label' => '出勤日数', 'key' => 'work_in_num', 'raw' => $getRaw(['work_in_num']), 'value' => $getVal(['work_in_num'])],
                    ['label' => '欠勤日数', 'key' => 'absence_num', 'raw' => $getRaw(['absence_num']), 'value' => $getVal(['absence_num'])],
                    ['label' => '出勤時間', 'key' => 'work_time', 'raw' => $getRaw(['work_time']), 'value' => $getVal(['work_time'])],
                    ['label' => '残業時間', 'key' => 'overtime', 'raw' => $getRaw(['overtime']), 'value' => $getVal(['overtime'])],
                    ['label' => '深夜残業時間', 'key' => 'night_over_time', 'raw' => $getRaw(['night_over_time']), 'value' => $getVal(['night_over_time'])],
                    ['label' => '遅早控除', 'key' => 'late_time', 'raw' => $getRaw(['late_time']), 'value' => $getVal(['late_time'])],
                    ['label' => '休日出勤日数', 'key' => 'work_horiday_num', 'raw' => $getRaw(['work_horiday_num']), 'value' => $getVal(['work_horiday_num'])],
                    ['label' => '休日出勤時間', 'key' => 'work_time_num', 'raw' => $getRaw(['work_time_num']), 'value' => $getVal(['work_time_num'])],
                    ['label' => '有休使用日数', 'key' => 'horiday_true', 'raw' => $getRaw(['horiday_true']), 'value' => $getVal(['horiday_true'])],
                    ['label' => '有休残日数', 'key' => 'horiday_true_num', 'raw' => $getRaw(['horiday_true_num']), 'value' => $getVal(['horiday_true_num'])],
                    ['label' => '休業日数', 'key' => 'days_closed', 'raw' => $getRaw(['days_closed']), 'value' => $getVal(['days_closed'])],
                    ['label' => '休業時間', 'key' => 'time_closed', 'raw' => $getRaw(['time_closed']), 'value' => $getVal(['time_closed'])],
                ];

                                $attendanceExtra = [
                    ['label' => '扶養人数', 'value' => $getVal(['fuyo_sum'], (string)($selectedStaffMeta['fuyo_sum'] ?? '-'))],
                    ['label' => '税額表', 'value' => (string)($selectedStaffMeta['tax_amount'] ?? '-')],
                    ['label' => '日額交通費', 'value' => (string)($selectedStaffMeta['traffic_day'] ?? '-')],
                    ['label' => '所定労働時間', 'value' => (string)($selectedStaffMeta['working_time'] ?? '-')],
                    ['label' => '月所定労働時間', 'value' => (string)($selectedStaffMeta['year_working_time'] ?? '-')],
                ];

                $kihonMasterRows = [
                    ['label' => '基本給決定日', 'value' => (string)($kihonInfo['decision_date'] ?? '-')],
                    ['label' => '月給', 'value' => (string)($kihonInfo['monthly_salary'] ?? '-')],
                    ['label' => '役員報酬', 'value' => (string)($kihonInfo['executive_remu'] ?? '-')],
                    ['label' => '時給', 'value' => (string)($kihonInfo['hourly_salary'] ?? ($kihonInfo['hourly_pay'] ?? '-'))],
                    ['label' => '役職手当', 'value' => (string)($kihonInfo['position_allow'] ?? '-')],
                    ['label' => '職務手当', 'value' => (string)($kihonInfo['duties_allow'] ?? '-')],
                    ['label' => '資格手当', 'value' => (string)($kihonInfo['qualification_allow'] ?? '-')],
                    ['label' => '請求手当', 'value' => (string)($kihonInfo['claim_allow'] ?? '-')],
                    ['label' => '通勤費', 'value' => (string)($kihonInfo['traffic_pay'] ?? '-')],
                    ['label' => '調整手当', 'value' => (string)($kihonInfo['adjustment_add'] ?? '-')],
                    ['label' => '家賃補助', 'value' => (string)($kihonInfo['rent_subsidies'] ?? '-')],
                    ['label' => '家賃負担', 'value' => (string)($kihonInfo['rent_pay'] ?? '-')],
                    ['label' => '調整控除', 'value' => (string)($kihonInfo['adjustment_pay'] ?? '-')],
                    ['label' => '固定残業', 'value' => (string)($kihonInfo['fixed_overtime'] ?? '-')],
                ];
                $blankIfZero = function (string $v): string {
                    $n = str_replace([',', '¥', ' '], '', trim($v));
                    if ($n === '' || $n === '-') {
                        return $v;
                    }
                    if (is_numeric($n) && (float)$n == 0.0) {
                        return '';
                    }
                    return $v;
                };
                foreach ($kihonMasterRows as &$r) {
                    $r['value'] = $blankIfZero((string)($r['value'] ?? ''));
                }
                unset($r);

                $syahoMasterRows = [
                    ['label' => '社保改定年', 'value' => (string)($syahoInfo['raise_year'] ?? '-')],
                    ['label' => '健保額', 'value' => (string)($syahoInfo['kenpo_amo'] ?? '-')],
                    ['label' => '介護額', 'value' => (string)($syahoInfo['kaigo_amo'] ?? '-')],
                    ['label' => '厚年額', 'value' => (string)($syahoInfo['kounen_amo'] ?? '-')],
                    ['label' => '健保月額', 'value' => (string)($syahoInfo['kenpo_monthly_amo'] ?? '-')],
                    ['label' => '厚年月額', 'value' => (string)($syahoInfo['kounen_monthly_amo'] ?? '-')],
                    ['label' => '健保等級', 'value' => (string)($syahoInfo['kenpo_toukyu'] ?? '-')],
                    ['label' => '厚年等級', 'value' => (string)($syahoInfo['kounen_toukyu'] ?? '-')],
                ];

                $residentMasterRows = [
                    ['label' => '住民税対象月', 'value' => (string)($residentInfo['target_month'] ?? '-')],
                    ['label' => '住民税月額', 'value' => (string)($residentInfo['resident_tax_month'] ?? '-')],
                ];
                $taxBaseAfterSyahoDisplay = '-';
                if ($hasVal(['taxation_sum']) && $hasVal(['syaho_sum'])) {
                    $taxBaseAfterSyaho = $toFloat($getVal(['taxation_sum'], '0')) - $toFloat($getVal(['syaho_sum'], '0'));
                    $taxBaseAfterSyahoDisplay = $formatComma((string)$taxBaseAfterSyaho);
                }

                $basicInfo = [
                    ['label' => '社保対象額', 'value' => $formatComma($getVal(['syaho_target_sum']))],
                    ['label' => '労保対象額', 'value' => $formatComma($getVal(['rouho_target_sum']))],
                    ['label' => '社保控除後計', 'value' => $taxBaseAfterSyahoDisplay],
                ];

                $attendanceBasicMerged = [];
                $mergedSeen = [];
                foreach (array_merge($attendanceExtra, $basicInfo) as $row) {
                    $label = (string)($row['label'] ?? '');
                    if ($label === '' || isset($mergedSeen[$label])) {
                        continue;
                    }
                    $mergedSeen[$label] = true;
                    $attendanceBasicMerged[] = $row;
                }
                $staffMemo = trim((string)($selectedStaffMeta['memo'] ?? ''));

                $earnings = [];
                $allowanceRows = [];
                $usedAmountKeys = [];
                $defs = collect($allowanceDefinitions ?? [])
                    ->sortBy(function ($d) {
                        $displayOrder = (int)($d['display_order'] ?? 0);
                        if ($displayOrder <= 0) {
                            $displayOrder = 9999;
                        }
                        return [$displayOrder, ((int)($d['allowance_no'] ?? 9999))];
                    })
                    ->values()
                    ->all();

                foreach ($defs as $def) {
                    $slotNo = (int)($def['slot_no'] ?? 0);
                    $amountKey = trim((string)($def['amount_column_key'] ?? ''));
                    if ($amountKey === '' && $slotNo > 0) {
                        $amountKey = 'allowance_amo_' . $slotNo;
                    }
                    if ($amountKey === '') {
                        continue;
                    }
                    if (isset($usedAmountKeys[$amountKey])) {
                        continue;
                    }

                    $label = trim((string)($def['allowance_name'] ?? ''));
                    if ($label === '') {
                        $label = $slotNo > 0 ? ('手当' . $slotNo) : $amountKey;
                    }
                    $valueKeys = [$amountKey];
                    if ($amountKey === 'allowance_amo_1') {
                        // 手当設定のキーは維持しつつ、値が未格納なら basic_salary を表示フォールバック
                        $valueKeys[] = 'basic_salary';
                    }
                    $allowanceRows[] = [
                        'label' => $label,
                        'key' => $amountKey,
                        'raw' => $getRaw($valueKeys, ''),
                        'value' => $formatComma($getVal($valueKeys, '-')),
                    ];
                    $usedAmountKeys[$amountKey] = true;
                }

                if (($allowanceDefinitions ?? []) === []) {
                    for ($i = 1; $i <= 17; $i++) {
                        $key = 'allowance_amo_' . $i;
                        if (isset($usedAmountKeys[$key])) {
                            continue;
                        }
                        $allowanceRows[] = ['label' => ('手当' . $i), 'key' => $key, 'raw' => $getRaw([$key], ''), 'value' => $formatComma($getVal([$key], '-'))];
                    }
                    if (!isset($usedAmountKeys['traffic_addition'])) {
                        $allowanceRows[] = ['label' => '非課税通勤費加算', 'key' => 'traffic_addition', 'raw' => $getRaw(['traffic_addition'], ''), 'value' => $formatComma($getVal(['traffic_addition'], '-'))];
                    }
                    if (!isset($usedAmountKeys['late_deduction'])) {
                        $allowanceRows[] = ['label' => '遅早控除', 'key' => 'late_deduction', 'raw' => $getRaw(['late_deduction'], ''), 'value' => $formatComma($getVal(['late_deduction'], '-'))];
                    }
                    if (!isset($usedAmountKeys['absence_deduction'])) {
                        $allowanceRows[] = ['label' => '欠勤控除', 'key' => 'absence_deduction', 'raw' => $getRaw(['absence_deduction'], ''), 'value' => $formatComma($getVal(['absence_deduction'], '-'))];
                    }
                    if (!isset($usedAmountKeys['leave_allowance'])) {
                        $allowanceRows[] = ['label' => '休業手当', 'key' => 'leave_allowance', 'raw' => $getRaw(['leave_allowance'], ''), 'value' => $formatComma($getVal(['leave_allowance'], '-'))];
                    }
                }                foreach ($allowanceRows as $row) {
                    $earnings[] = $row;
                }
                $taxationSumFallback = (string) $toFloat($getVal(['supply_sum'], '0'));
                $taxationSumDisplay = $formatComma($getVal(['taxation_sum'], $taxationSumFallback));
                $notTaxationSumDisplay = $formatComma($getVal(['not_taxation_sum'], '0'));
                $earnings[] = ['label' => '課税支給合計', 'key' => 'taxation_sum', 'raw' => $getRaw(['taxation_sum']), 'value' => $taxationSumDisplay, 'is_total' => true];
                $earnings[] = ['label' => '非課税支給合計', 'key' => 'not_taxation_sum', 'raw' => $getRaw(['not_taxation_sum']), 'value' => $notTaxationSumDisplay, 'is_total' => true];

                $syahoFromPayroll = (
                    $toFloat($getVal(['kenpo'], '0'))
                    + $toFloat($getVal(['kaigo'], '0'))
                    + $toFloat($getVal(['kounen'], '0'))
                    + $toFloat($getVal(['koyou'], '0'))
                );
                $syahoSumResolved = $getVal(['syaho_sum'], '-');
                if ((trim((string)$syahoSumResolved) === '' || trim((string)$syahoSumResolved) === '-') && (
                    $hasVal(['kenpo']) || $hasVal(['kaigo']) || $hasVal(['kounen']) || $hasVal(['koyou'])
                )) {
                    $syahoSumResolved = (string)$syahoFromPayroll;
                }
                $syahoSumDisplay = $formatComma($syahoSumResolved);

                $deductions = [
                    ['label' => '健康保険', 'key' => 'kenpo', 'raw' => $getRaw(['kenpo']), 'value' => $formatComma($getVal(['kenpo']))],
                    ['label' => '介護保険', 'key' => 'kaigo', 'raw' => $getRaw(['kaigo']), 'value' => $formatComma($getVal(['kaigo']))],
                    ['label' => '厚生年金', 'key' => 'kounen', 'raw' => $getRaw(['kounen']), 'value' => $formatComma($getVal(['kounen']))],
                    ['label' => '雇用保険', 'key' => 'koyou', 'raw' => $getRaw(['koyou']), 'value' => $formatComma($getVal(['koyou']))],
                    ['label' => '社会保険計', 'key' => 'syaho_sum', 'raw' => $getRaw(['syaho_sum'], trim((string)$syahoSumResolved) === '-' ? '' : (string)$syahoSumResolved), 'value' => $syahoSumDisplay, 'is_total' => true],
                    ['label' => '所得税', 'key' => 'income_tax', 'raw' => $getRaw(['income_tax']), 'value' => $formatComma($getVal(['income_tax']))],
                    ['label' => '住民税', 'key' => 'resident_tax', 'raw' => $getRaw(['resident_tax']), 'value' => $formatComma($getVal(['resident_tax']))],
                ];

                $others = [
                    ['label' => '年調過不足', 'key' => 'adjustment_year_end', 'raw' => $getRaw(['adjustment_year_end']), 'value' => $getVal(['adjustment_year_end'])],
                    ['label' => '立替清算', 'key' => 'cost_liquidation', 'raw' => $getRaw(['cost_liquidation']), 'value' => $formatComma($getVal(['cost_liquidation']))],
                    ['label' => '振込残額', 'key' => '振込残額', 'raw' => $getRaw(['振込残額']), 'value' => $getVal(['振込残額'])],
                    ['label' => '調整控除', 'key' => 'adjustment_cost', 'raw' => $getRaw(['adjustment_cost']), 'value' => $formatComma($getVal(['adjustment_cost']))],
                    ['label' => '会社立替費用', 'key' => '会社立替費用', 'raw' => $getRaw(['会社立替費用']), 'value' => $getVal(['会社立替費用'])],
                ];
                $othersTotal = 0.0;
                foreach ($others as $o) {
                    $othersTotal += $toFloat((string)($o['value'] ?? ''));
                }

                $companyLabel = trim((string)($selectedSummary['company_name'] ?? '')) !== ''
                    ? (string)($selectedSummary['company_name'] ?? '')
                    : '-';
                $storeLabel = trim((string)($selectedSummary['store_name'] ?? '')) !== ''
                    ? (string)($selectedSummary['store_name'] ?? '')
                    : '-';
                $isLocked = ((string)($selectedSummary['edit_lock'] ?? '0')) === '1';

                $numOrNull = function (array $keys) use ($getVal, $toFloat): ?float {
                    $v = trim((string)$getVal($keys, ''));
                    if ($v === '' || $v === '-') {
                        return null;
                    }
                    return $toFloat($v);
                };
                $dispNum = function (?float $v) use ($formatComma): string {
                    if ($v === null) {
                        return '-';
                    }
                    return $formatComma((string)$v);
                };
                $salesKitazaike = $numOrNull(['kitazaike']);
                $salesHigashi = $numOrNull(['higashi_kakogawa']);
                $salesTsubasa = $numOrNull(['tsubasa_harima']);
                $salesOwn = $numOrNull(['own_cost']);
                $salesUnpaid = $numOrNull(['unpaid_amo']);
                $salesSubtotal = null;
                if ($salesKitazaike !== null || $salesHigashi !== null || $salesTsubasa !== null || $salesOwn !== null || $salesUnpaid !== null) {
                    $salesSubtotal = (float)($salesKitazaike ?? 0) + (float)($salesHigashi ?? 0) + (float)($salesTsubasa ?? 0) + (float)($salesOwn ?? 0) + (float)($salesUnpaid ?? 0);
                }

                $sales = [
                    ['label' => '人数', 'key' => 'peple_num', 'raw' => $getRaw(['peple_num', 'people_num']), 'value' => $dispNum($numOrNull(['peple_num', 'people_num']))],
                    ['label' => '距離', 'key' => 'km', 'raw' => $getRaw(['km']), 'value' => $dispNum($numOrNull(['km']))],
                    ['label' => '北在家', 'key' => 'kitazaike', 'raw' => $getRaw(['kitazaike']), 'value' => $dispNum($salesKitazaike)],
                    ['label' => '東加古川', 'key' => 'higashi_kakogawa', 'raw' => $getRaw(['higashi_kakogawa']), 'value' => $dispNum($salesHigashi)],
                    ['label' => 'つばさ播磨', 'key' => 'tsubasa_harima', 'raw' => $getRaw(['tsubasa_harima']), 'value' => $dispNum($salesTsubasa)],
                    ['label' => '自費', 'key' => 'own_cost', 'raw' => $getRaw(['own_cost']), 'value' => $dispNum($salesOwn)],
                    ['label' => '未収額', 'key' => 'unpaid_amo', 'raw' => $getRaw(['unpaid_amo']), 'value' => $dispNum($salesUnpaid)],
                    ['label' => '北在家〜未収額 合計', 'value' => $dispNum($salesSubtotal), 'is_total' => true],
                    ['label' => 'さくら鍼灸', 'key' => 'sakura_hari', 'raw' => $getRaw(['sakura_hari']), 'value' => $dispNum($numOrNull(['sakura_hari']))],
                    ['label' => '織田鍼灸', 'key' => 'orita_hari', 'raw' => $getRaw(['orita_hari']), 'value' => $dispNum($numOrNull(['orita_hari']))],
                    ['label' => '宮本鍼灸', 'key' => 'miyamoto_hari', 'raw' => $getRaw(['miyamoto_hari']), 'value' => $dispNum($numOrNull(['miyamoto_hari']))],
                    ['label' => '横井鍼灸', 'key' => 'yokoi_hari', 'raw' => $getRaw(['yokoi_hari']), 'value' => $dispNum($numOrNull(['yokoi_hari']))],
                ];

                $payTotalFallback = (string)(
                    $toFloat($getVal(['supply_sum'], '0')) > 0
                        ? $toFloat($getVal(['supply_sum'], '0'))
                        : ($toFloat($getVal(['taxation_sum'], '0')) + $toFloat($getVal(['not_taxation_sum'], '0')))
                );
                $payTotalDisplay = $formatComma((string)($selectedSummary['pay_total'] ?? ''));
                if (trim((string)($selectedSummary['pay_total'] ?? '')) === '' || trim((string)($selectedSummary['pay_total'] ?? '')) === '-') {
                    $payTotalDisplay = $formatComma($payTotalFallback);
                }

                $deductionTotalFallback = (string)(
                    $toFloat($getVal(['deduction_sum'], '0')) > 0
                        ? $toFloat($getVal(['deduction_sum'], '0'))
                        : ($toFloat((string)$syahoSumResolved) + $toFloat($getVal(['income_tax'], '0')) + $toFloat($getVal(['resident_tax'], '0')))
                );
                $deductionTotalDisplay = $formatComma((string)($selectedSummary['deduction_total'] ?? ''));
                if (trim((string)($selectedSummary['deduction_total'] ?? '')) === '' || trim((string)($selectedSummary['deduction_total'] ?? '')) === '-') {
                    $deductionTotalDisplay = $formatComma($deductionTotalFallback);
                }

                $netPayFallback = (string)(
                    $toFloat($getVal(['supply_deduction_sum'], '0')) > 0
                        ? $toFloat($getVal(['supply_deduction_sum'], '0'))
                        : ($toFloat($payTotalFallback) - $toFloat($deductionTotalFallback))
                );
                $netPayDisplay = $formatComma((string)($selectedSummary['net_pay'] ?? ''));
                if (trim((string)($selectedSummary['net_pay'] ?? '')) === '' || trim((string)($selectedSummary['net_pay'] ?? '')) === '-') {
                    $netPayDisplay = $formatComma($netPayFallback);
                }

                $basicInfo = [
                    ['label' => '', 'value' => (string)($selectedSummary['supply_month'] ?? '-')],
                    ['label' => '', 'value' => (string)($selectedSummary['division'] ?? '-')],
                    ['label' => '税額表', 'value' => (string)($selectedStaffMeta['tax_amount'] ?? '-')],
                    ['label' => '', 'value' => $formatComma($getVal(['syaho_target_sum']))],
                    ['label' => '社保控除後計', 'value' => $taxBaseAfterSyahoDisplay],
                    ['label' => '', 'value' => $formatComma($getVal(['rouho_target_sum']))],
                    ['label' => '', 'value' => $taxationSumDisplay],
                    ['label' => '', 'value' => $notTaxationSumDisplay],
                    ['label' => '', 'value' => $formatComma($getVal(['income_tax']))],
                    ['label' => '', 'value' => $formatComma($getVal(['resident_tax']))],
                    ['label' => '', 'value' => $syahoSumDisplay],
                ];

                // Admin payroll calculation screen: always show all items
                // so zero-valued fields are still visible/editable.
            @endphp

            <div class="layout">
                <aside class="staff-list">
                    @foreach ($summaryRows as $row)
                        @php
                            $query = request()->query();
                            $query['row'] = $row['key'];
                            $lockState = trim((string)($row['edit_lock'] ?? ''));
                            $lockNum = $lockState === '' ? -1 : (int)$lockState;
                            $stateClass = $lockNum === 1 ? 'state-locked' : ($lockNum === 0 ? 'state-unlocked' : 'state-pending');
                            $pillClass = $lockNum === 1 ? 'locked' : ($lockNum === 0 ? 'unlocked' : 'pending');
                            $pillText = $lockNum === 1 ? '確定済' : ($lockNum === 0 ? '未確定' : '未設定');
                            $checked = ((int)($row['attendance_checked'] ?? 0)) === 1;
                            $attendanceText = $checked ? '勤怠確定済' : '勤怠未確定';
                            $staffName = trim((string)($row['staff_name'] ?? '')) !== '' ? (string)$row['staff_name'] : '-';
                        @endphp
                        <a class="staff-item {{ $stateClass }} {{ $selectedRowKey === $row['key'] ? 'active' : '' }}" href="{{ route('admin.payroll.index', $query) }}">
                            <div class="staff-id">
                                <span>{{ $row['staff_id'] }}</span>
                                <span class="lock-pill {{ $pillClass }}">{{ $pillText }}</span>
                                <span class="lock-pill {{ $checked ? 'att-checked' : 'att-unchecked' }}">{{ $attendanceText }}</span>
                                @if (!empty($row['division']))
                                    <span class="staff-division">{{ $row['division'] }}</span>
                                @endif
                            </div>
                            <div class="staff-name">{{ $staffName }}</div>
                        </a>
                    @endforeach
                </aside>

                <section id="payroll-detail-pane" class="detail-pane">
                    <div class="summary-cards">
                        <div class="card"><div class="k">対象者</div><div class="v">{{ $selectedSummary['staff_name'] ?? '-' }}</div></div>
                        <div class="card"><div class="k">支給月</div><div class="v">{{ $selectedSummary['supply_month'] ?? '-' }}</div></div>
                        <div class="card"><div class="k">雇用形態</div><div class="v">{{ $selectedSummary['division'] ?? '-' }}</div></div>
                        <div class="card no-head">
                            <div class="v">
                                <div>{{ $companyLabel }}</div>
                                <div>{{ $storeLabel }}</div>
                            </div>
                        </div>
                        <div class="card">
                            @php
                                $attendanceChecked = ((string)($selectedSummary['attendance_checked'] ?? '0')) === '1';
                            @endphp

                            @if ($attendanceChecked && !$isLocked)
                                <form id="payroll-save-form" method="POST" action="{{ route('admin.payroll.save') }}" style="display:none;">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                    <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] ?? '' }}">
                                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                    <input type="hidden" name="row" value="{{ $selectedRowKey }}">
                                    <input type="hidden" id="recalc-koyou-on-save" name="recalc_koyou" value="0">
                                </form>
                            @endif

                            @if ($attendanceChecked)
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <form method="POST" action="{{ route('admin.payroll.unlock') }}">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                        <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] ?? '' }}">
                                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                        <input type="hidden" name="row" value="{{ $selectedRowKey }}">
                                        @if ($isLocked)
                                            <button type="submit" class="btn-act">編集</button>
                                        @else
                                            <button id="btn-start-edit" type="button" class="btn-act">編集</button>
                                        @endif
                                    </form>
                                    @if (!$isLocked)
                                        <button id="btn-save-edit" type="submit" form="payroll-save-form" class="btn-act primary" style="display:none;">保存</button>
                                        <button id="btn-clear-calc" type="button" class="btn-act" style="display:none;">勤怠/支給/控除/その他をクリア</button>
                                        <button id="btn-cancel-edit" type="button" class="btn-act" style="display:none;">キャンセル</button>
                                    @endif
                                    <form method="POST" action="{{ route('admin.payroll.toggle-lock') }}" onsubmit="return confirm('編集ロックを切り替えます。よろしいですか？');">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                        <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] ?? '' }}">
                                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                        <input type="hidden" name="row" value="{{ $selectedRowKey }}">
                                        <input type="hidden" name="current_lock" value="{{ $isLocked ? '1' : '0' }}">
                                        <button id="btn-toggle-lock" type="submit" class="btn-act {{ $isLocked ? '' : 'primary' }}">
                                            {{ $isLocked ? '未確定に戻す' : '確定' }}
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div style="font-size:12px; color:#b42318; font-weight:700;">勤怠未確定</div>
                            @endif
                        </div>
                    </div>

                        <div id="payroll-calc-sections" class="sections">
                        <div class="section-grid">
                            <div class="section-stack">
                                <section class="section">
                                    <h3>勤怠集計</h3>
                                    <table>
                                        <tbody>
                                        @forelse($attendance as $r)
                                            @php
                                                $k = (string)($r['key'] ?? '');
                                                $eps = 0.000001;
                                                $isPaidLeaveRemain = ($k === 'horiday_true_num');
                                                $savedVal = $toFloat((string)($r['value'] ?? '0'));
                                                $hasPreviewValue = !$isPaidLeaveRemain && $k !== '' && isset($attendancePreview[$k]);
                                                // Fallback to saved value so non-zero rows always show the preview line.
                                                $previewVal = $hasPreviewValue ? (float)$attendancePreview[$k] : $savedVal;
                                                $prevMonthVal = ($isPaidLeaveRemain && isset($previousPayrollPreview[$k])) ? (float)$previousPayrollPreview[$k] : null;
                                                $delta = null;
                                                if ($isPaidLeaveRemain && $prevMonthVal !== null) {
                                                    $delta = $savedVal - $prevMonthVal;
                                                } elseif ($hasPreviewValue) {
                                                    $delta = $previewVal - $savedVal;
                                                }
                                                $showPreview = !$isPaidLeaveRemain && abs((float)$previewVal) > $eps;
                                                $hasDelta = $delta !== null && abs((float)$delta) > $eps;
                                            @endphp
                                            <tr>
                                                <th>{{ $r['label'] }}</th>
                                                <td>
                                                    @if (!$isLocked && !empty($r['key']))
                                                        <span class="cell-view">{{ $r['value'] }}</span>
                                                        <input
                                                            class="num-input"
                                                            type="text"
                                                            name="fields[{{ $r['key'] }}]"
                                                            value="{{ $formatComma((string)($r['raw'] ?? '')) }}"
                                                            form="payroll-save-form"
                                                        >
                                                    @else
                                                        {{ $r['value'] }}
                                                    @endif
                                                    @if ($showPreview || $hasDelta)
                                                        <div class="small" style="margin-top:4px; font-size:11px; font-weight:500; color:#9ca3af;">
                                                            @if ($showPreview)
                                                                勤怠集計値 {{ $fmtCompact((float)$previewVal) }}
                                                            @endif
                                                            @if ($hasDelta)
                                                                <span style="color:#b42318; font-weight:500;">
                                                                    {{ $showPreview ? ' / ' : '' }}{{ $isPaidLeaveRemain ? '前月差分' : '差分' }} {{ ((float)$delta > 0 ? '+' : '') . $formatComma((string)$delta) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><th>-</th><td>-</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </section>

                                <section class="section reference">
                                    <h3>基本情報</h3>
                                    <table>
                                        <tbody>
                                        @forelse($attendanceBasicMerged as $r)
                                            <tr><th>{{ $r['label'] }}</th><td>{{ $r['value'] }}</td></tr>
                                        @empty
                                            <tr><th>-</th><td>-</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                    <div class="memo-box">
                                        <div class="memo-title">メモ</div>
                                        <div class="memo-body">{{ $staffMemo !== '' ? $staffMemo : '-' }}</div>
                                    </div>
                                </section>
                            </div>

                            <section class="section">
                                <h3>支給項目</h3>
                                <table>
                                    <tbody>
                                    @forelse($earnings as $r)
                                        @php
                                            $k = (string)($r['key'] ?? '');
                                            $isTotalRow = !empty($r['is_total']);
                                            $hasPrev = !$isTotalRow && $k !== '' && isset(($previousPayrollPreview ?? [])[$k]);
                                            $prevVal = $hasPrev ? (float)($previousPayrollPreview[$k] ?? 0) : null;
                                            $savedVal = $toFloat((string)($r['value'] ?? '0'));
                                            $delta = $hasPrev ? ($savedVal - (float)$prevVal) : null;
                                            $hasDelta = $hasPrev && abs((float)$delta) > 0.000001;
                                            $showDeltaBlock = $hasDelta;
                                        @endphp
                                        <tr class="{{ !empty($r['is_total']) ? 'earnings-total' : '' }}">
                                            <th>
                                                {{ $r['label'] }}
                                                @if (
                                                    !$isLocked
                                                    && in_array($k, ['allowance_amo_8', 'allowance_amo_9', 'allowance_amo_7', 'late_deduction', 'absence_deduction'], true)
                                                )
                                                    <form method="POST" action="{{ route('admin.payroll.recalc-premium-deduction') }}" style="display:inline-block; margin-left:6px; vertical-align:middle;">
                                                        @csrf
                                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                                        <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] ?? '' }}">
                                                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                                        <input type="hidden" name="row" value="{{ $selectedRowKey }}">
                                                        <button type="submit" class="btn-act recalc-premium-btn" style="height:22px; padding:0 7px; font-size:11px;">↩ 計算</button>
                                                    </form>
                                                @endif
                                            </th>
                                            <td>
                                                @if (!$isLocked && !empty($r['key']))
                                                    <span class="cell-view">{{ $r['value'] }}</span>
                                                    <input
                                                        class="num-input"
                                                        type="text"
                                                        name="fields[{{ $r['key'] }}]"
                                                        value="{{ $formatComma((string)($r['raw'] ?? '')) }}"
                                                        form="payroll-save-form"
                                                    >
                                                @else
                                                    {{ $r['value'] }}
                                                @endif
                                                @if ($showDeltaBlock)
                                                    <div class="small" style="margin-top:4px; font-size:11px; font-weight:500; color:#9ca3af;">
                                                            <span style="color:#b42318; font-weight:500;">差分 {{ ((float)$delta > 0 ? '+' : '') . $formatComma((string)$delta) }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><th>-</th><td>-</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </section>

                            <div class="section-stack">
                                <section class="section">
                                    <h3>控除項目</h3>
                                    <table>
                                    <tbody>
                                    @forelse($deductions as $r)
                                            @php
                                                $k = (string)($r['key'] ?? '');
                                                $isTotalRow = !empty($r['is_total']);
                                                $hasPrev = !$isTotalRow && $k !== '' && isset(($previousPayrollPreview ?? [])[$k]);
                                                $prevVal = $hasPrev ? (float)($previousPayrollPreview[$k] ?? 0) : null;
                                                $savedVal = $toFloat((string)($r['value'] ?? '0'));
                                                $delta = $hasPrev ? ($savedVal - (float)$prevVal) : null;
                                                $hasDelta = $hasPrev && abs((float)$delta) > 0.000001;
                                                $showDeltaBlock = $hasDelta;
                                            @endphp
                                            <tr class="{{ !empty($r['is_total']) ? 'deduction-total' : '' }}">
                                                <th>
                                                    {{ $r['label'] }}
                                                    @if (!$isLocked && $k === 'koyou')
                                                        <form method="POST" action="{{ route('admin.payroll.recalc-koyou') }}" style="display:inline-block; margin-left:6px; vertical-align:middle;">
                                                            @csrf
                                                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                                            <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                                            <input type="hidden" name="target_staff_id" value="{{ $selectedSummary['staff_id'] ?? '' }}">
                                                            <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                                                            <input type="hidden" name="row" value="{{ $selectedRowKey }}">
                                                            <button type="submit" class="btn-act recalc-koyou-btn" style="height:22px; padding:0 7px; font-size:11px;">↩ 計算</button>
                                                        </form>
                                                    @endif
                                                </th>
                                                <td>
                                                    @if (!$isLocked && !empty($r['key']))
                                                        <span class="cell-view">{{ $r['value'] }}</span>
                                                        <input
                                                            class="num-input"
                                                            type="text"
                                                            name="fields[{{ $r['key'] }}]"
                                                            value="{{ $formatComma((string)($r['raw'] ?? '')) }}"
                                                            form="payroll-save-form"
                                                        >
                                                    @else
                                                        {{ $r['value'] }}
                                                    @endif
                                                    @if ($showDeltaBlock)
                                                        <div class="small" style="margin-top:4px; font-size:11px; font-weight:500; color:#9ca3af;">
                                                            <span style="color:#b42318; font-weight:500;">差分 {{ ((float)$delta > 0 ? '+' : '') . $formatComma((string)$delta) }}</span>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                    @empty
                                        <tr><th>-</th><td>-</td></tr>
                                    @endforelse
                                        </tbody>
                                    </table>
                                </section>

                                <section class="section">
                                    <h3>その他項目</h3>
                                    <table>
                                        <tbody>
                                        @forelse($others as $r)
                                            <tr>
                                                <th>{{ $r['label'] }}</th>
                                                <td>
                                                    @if (!$isLocked && !empty($r['key']))
                                                        <span class="cell-view">{{ $r['value'] }}</span>
                                                        <input
                                                            class="num-input"
                                                            type="text"
                                                            name="fields[{{ $r['key'] }}]"
                                                            value="{{ $formatComma((string)($r['raw'] ?? '')) }}"
                                                            form="payroll-save-form"
                                                        >
                                                    @else
                                                        {{ $r['value'] }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><th>-</th><td>-</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </section>

                                <section class="section">
                                    <h3>売上</h3>
                                    <table>
                                        <tbody>
                                        @forelse($sales as $r)
                                            <tr class="{{ !empty($r['is_total']) ? 'sales-total' : '' }}">
                                                <th>{{ $r['label'] }}</th>
                                                <td>
                                                    @if (!$isLocked && !empty($r['key']))
                                                        <span class="cell-view">{{ $r['value'] }}</span>
                                                        <input
                                                            class="num-input"
                                                            type="text"
                                                            name="fields[{{ $r['key'] }}]"
                                                            value="{{ $formatComma((string)($r['raw'] ?? '')) }}"
                                                            form="payroll-save-form"
                                                        >
                                                    @else
                                                        {{ $r['value'] }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><th>-</th><td>-</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </section>
                            </div>

                            <div class="section-stack">
                                <section class="section reference">
                                    <h3>給与マスタ</h3>
                                    <table>
                                        <tbody>
                                        @forelse($kihonMasterRows as $r)
                                            <tr><th>{{ $r['label'] }}</th><td>{{ $r['value'] }}</td></tr>
                                        @empty
                                            <tr><th>-</th><td>-</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </section>

                                <section class="section reference">
                                    <h3>社会保険</h3>
                                    <table>
                                        <tbody>
                                        @forelse($syahoMasterRows as $r)
                                            <tr><th>{{ $r['label'] }}</th><td>{{ $r['value'] }}</td></tr>
                                        @empty
                                            <tr><th>-</th><td>-</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </section>

                                <section class="section reference">
                                    <h3>住民税</h3>
                                    <table>
                                        <tbody>
                                        @forelse($residentMasterRows as $r)
                                            <tr><th>{{ $r['label'] }}</th><td>{{ $r['value'] }}</td></tr>
                                        @empty
                                            <tr><th>-</th><td>-</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </section>
                            </div>
                        </div>
                        </div>

                        <div class="totals">
                            <div class="total-card"><div class="k">支給合計</div><div class="v">{{ $payTotalDisplay }}</div></div>
                            <div class="total-card"><div class="k">控除合計</div><div class="v">{{ $deductionTotalDisplay }}</div></div>
                            <div class="total-card"><div class="k">その他合計</div><div class="v">{{ $formatComma((string)$othersTotal) }}</div></div>
                            <div class="total-card"><div class="k">差引支給額</div><div class="v">{{ $netPayDisplay }}</div></div>
                        </div>
                    </div>
                </section>
            </div>
        @endif
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var startBtn = document.getElementById('btn-start-edit');
    var saveBtn = document.getElementById('btn-save-edit');
    var clearBtn = document.getElementById('btn-clear-calc');
    var cancelBtn = document.getElementById('btn-cancel-edit');
    var pane = document.getElementById('payroll-detail-pane');
    var staffList = document.querySelector('.staff-list');
    var calcSections = document.getElementById('payroll-calc-sections');
    var saveForm = document.getElementById('payroll-save-form');
    var recalcKoyouOnSave = document.getElementById('recalc-koyou-on-save');
    var setViewMode = function (resetRecalc) {
        if (resetRecalc === undefined) {
            resetRecalc = true;
        }
        if (pane) {
            pane.classList.remove('edit-mode');
        }
        if (saveBtn) {
            saveBtn.style.display = 'none';
        }
        if (clearBtn) {
            clearBtn.style.display = 'none';
        }
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
        if (resetRecalc && recalcKoyouOnSave) {
            recalcKoyouOnSave.value = '0';
        }
    };
    setViewMode();

        var syncStaffListHeight = function () {
            if (!staffList || !calcSections) {
                return;
            }
            if (window.innerWidth <= 760) {
                staffList.style.height = '';
                staffList.style.maxHeight = '';
                return;
            }
            var target = calcSections.offsetHeight;
            var totalCard = document.querySelector('.totals .total-card');
            var bonusHeight = totalCard ? (totalCard.offsetHeight * 2) : 0;
            var rowAdjust = 32; // 基本給1行分の微調整
            if (target > 0) {
                var finalHeight = target + bonusHeight + rowAdjust;
                staffList.style.height = finalHeight + 'px';
                staffList.style.maxHeight = finalHeight + 'px';
            }
        };
    syncStaffListHeight();
    window.addEventListener('resize', syncStaffListHeight);

    if (!startBtn || !pane) {
        return;
    }
    startBtn.addEventListener('click', function () {
        pane.classList.add('edit-mode');
        if (saveBtn) {
            saveBtn.style.display = 'inline-block';
        }
        if (clearBtn) {
            clearBtn.style.display = 'inline-block';
        }
        if (cancelBtn) {
            cancelBtn.style.display = 'inline-block';
        }
    });
    if (clearBtn && pane) {
        clearBtn.addEventListener('click', function () {
            if (recalcKoyouOnSave) {
                recalcKoyouOnSave.value = '1';
            }
            var protectedKeys = {
                // attendance: keep as-is
                work_in_num: true,
                absence_num: true,
                work_time: true,
                overtime: true,
                night_over_time: true,
                late_time: true,
                work_horiday_num: true,
                work_time_num: true,
                horiday_true: true,
                horiday_true_num: true,
                days_closed: true,
                time_closed: true,

                // master-derived: keep as-is
                basic_salary: true,
                allowance_amo_1: true,
                allowance_amo_2: true,
                allowance_amo_5: true,
                allowance_amo_10: true,
                allowance_amo_11: true,
                allowance_amo_12: true,
                allowance_amo_13: true,
                allowance_amo_14: true,
                allowance_amo_15: true,
                allowance_amo_16: true,
                rent_cost: true,
                adjustment_cost: true,
                kenpo: true,
                kaigo: true,
                kounen: true,
                resident_tax: true
            };
            var inputs = pane.querySelectorAll('.num-input[name^="fields["]');
            inputs.forEach(function (input) {
                var name = input.getAttribute('name') || '';
                var key = name.replace(/^fields\[/, '').replace(/\]$/, '');
                if (!protectedKeys[key]) {
                    input.value = '0';
                }
            });
        });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            setViewMode();
        });
    }
    if (saveForm) {
        saveForm.addEventListener('submit', function () {
            // Keep recalc_koyou flag value during submit.
            setViewMode(false);
        });
    }

    var seedOps = document.getElementById('payroll-seed-ops');
    var seedToggle = document.getElementById('payroll-seed-toggle');
    var seedChecks = document.querySelectorAll('.js-payroll-seed-target');
    var seedAll = document.querySelector('.js-payroll-seed-all');
    var seedClear = document.querySelector('.js-payroll-seed-clear');
    var seedForms = ['payroll-seed-create-form', 'payroll-seed-delete-form'];

    var syncSeedTargets = function () {
        var selected = Array.from(seedChecks).filter(function (el) { return el.checked; }).map(function (el) { return el.value; });
        seedForms.forEach(function (id) {
            var form = document.getElementById(id);
            if (!form) return;
            form.querySelectorAll('input[name="target_staff_ids[]"]').forEach(function (node) { node.remove(); });
            selected.forEach(function (staffId) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'target_staff_ids[]';
                input.value = staffId;
                form.appendChild(input);
            });
        });
    };

    if (seedAll) {
        seedAll.addEventListener('click', function () {
            seedChecks.forEach(function (el) { el.checked = true; });
            syncSeedTargets();
        });
    }
    if (seedClear) {
        seedClear.addEventListener('click', function () {
            seedChecks.forEach(function (el) { el.checked = false; });
            syncSeedTargets();
        });
    }
    seedChecks.forEach(function (el) {
        el.addEventListener('change', syncSeedTargets);
    });
    syncSeedTargets();

    if (seedToggle && seedOps) {
        var setSeedOpen = function (open) {
            seedOps.classList.toggle('is-open', open);
            seedToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            seedToggle.textContent = open ? '給与データ作成を閉じる' : '給与データ作成';
        };
        setSeedOpen(false);
        seedToggle.addEventListener('click', function () {
            setSeedOpen(!seedOps.classList.contains('is-open'));
        });
    }
});
</script>
</body>
</html>










