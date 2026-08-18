<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>委託報酬</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        .print-page {
            width: 210mm;
            min-height: 297mm;
        }

        .reward-title {
            margin: 0 0 5mm;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .reward-meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4mm;
            font-size: 11px;
            font-weight: 700;
        }

        .reward-table {
            font-size: 14px;
        }

        .reward-table th,
        .reward-table td {
            padding: 2px 3px;
            line-height: 1.25;
        }

        .reward-table .item-col {
            width: 27mm;
            text-align: left;
            background: #f3f4f6;
            font-weight: 700;
            line-height: 18px;
        }

        .reward-table .person-col {
            width: 20mm;
            font-size: 12px;
        }

        .reward-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .reward-table .section-row th,
        .reward-table .section-row td {
            background: #e5e7eb;
            font-weight: 700;
        }

        .reward-table .total-row th,
        .reward-table .total-row td {
            background: #f3f4f6;
            font-weight: 700;
        }

        @media print {
            .print-page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">印刷</button>
    @php
    $money = static fn($value): string => $value === null ? '' : number_format((float) $value);
    $num = static fn($value, int $digits = 0): string => $value === null ? '' : number_format((float) $value, $digits);
    $selectedMonthText = '-';
    if (($selectedMonth ?? '') !== '') {
    $ts = strtotime((string) $selectedMonth . '-01');
    $selectedMonthText = $ts === false ? (string) $selectedMonth : date('Y年n月度', $ts);
    }
    $rowGroups = [
    '勤怠' => [
    ['label' => '出勤日数', 'key' => 'work_in_num', 'type' => 'num', 'digits' => 0],
    ['label' => '実働時間', 'key' => 'work_time', 'type' => 'num', 'digits' => 2],
    ['label' => '欠勤日数', 'key' => 'absence_num', 'type' => 'num', 'digits' => 0],
    ['label' => '残業時間', 'key' => 'overtime', 'type' => 'num', 'digits' => 2],
    ['label' => '休日勤務時間', 'key' => 'work_time_num', 'type' => 'num', 'digits' => 2],
    ['label' => '休日出勤日数', 'key' => 'work_holiday_num', 'type' => 'num', 'digits' => 0],
    ['label' => '有休日数', 'key' => 'holiday_true', 'type' => 'num', 'digits' => 2],
    ['label' => '有休残日数', 'key' => 'holiday_true_num', 'type' => 'num', 'digits' => 2],
    ['label' => '遅刻・早退時間', 'key' => 'late_time', 'type' => 'num', 'digits' => 2],
    ],
    '売上' => [
    ['label' => '人数', 'key' => 'peple_num', 'type' => 'num', 'digits' => 0],
    ['label' => '距離', 'key' => 'km', 'type' => 'num', 'digits' => 2],
    ['label' => '北在家', 'key' => 'kitazaike', 'type' => 'money'],
    ['label' => '東加古川', 'key' => 'higashi_kakogawa', 'type' => 'money'],
    ['label' => '播磨', 'key' => 'tsubasa_harima', 'type' => 'money'],
    ['label' => 'さくら鍼灸', 'key' => 'sakura_hari', 'type' => 'money'],
    ['label' => '織田鍼灸', 'key' => 'orita_hari', 'type' => 'money'],
    ['label' => '宮本鍼灸', 'key' => 'miyamoto_hari', 'type' => 'money'],
    ['label' => '横井鍼灸', 'key' => 'yokoi_hari', 'type' => 'money'],
    ['label' => '自費', 'key' => 'own_cost', 'type' => 'money'],
    ['label' => '未収額', 'key' => 'unpaid_amo', 'type' => 'money'],
    ['label' => '売上合計', 'key' => 'sales_total', 'type' => 'money', 'total' => true],
    ],
    '支給' => [
    ['label' => '基本給(月給)', 'key' => 'basic_salary', 'type' => 'money'],
    ['label' => '役員報酬', 'key' => 'allowance_amo_2', 'type' => 'money'],
    ['label' => '役職手当', 'key' => 'allowance_amo_16', 'type' => 'money'],
    ['label' => '職務手当', 'key' => 'allowance_amo_13', 'type' => 'money'],
    ['label' => '資格手当', 'key' => 'allowance_amo_11', 'type' => 'money'],
    ['label' => '請求手当', 'key' => 'allowance_amo_12', 'type' => 'money'],
    ['label' => '管理手当', 'key' => 'allowance_amo_3', 'type' => 'money'],
    ['label' => '歩合手当', 'key' => 'allowance_amo_4', 'type' => 'money'],
    ['label' => '調整手当', 'key' => 'allowance_amo_5', 'type' => 'money'],
    ['label' => '残業手当', 'key' => 'allowance_amo_8', 'type' => 'money'],
    ['label' => '休日出勤手当', 'key' => 'allowance_amo_7', 'type' => 'money'],
    ['label' => '交通費', 'key' => 'commuting_total', 'type' => 'money'],
    ['label' => '調整控除', 'key' => 'adjustment_cost', 'type' => 'money'],
    ['label' => '支給合計', 'key' => 'supply_sum', 'type' => 'money', 'total' => true],
    ['label' => '源泉徴収税額', 'key' => 'income_tax', 'type' => 'money'],
    ['label' => '立替経費', 'key' => 'cost_liquidation', 'type' => 'money'],
    ['label' => '差引支給額', 'key' => 'transfer_amount', 'type' => 'money', 'total' => true],
    ],
    ];
    $format = static function (array $row, array $def) use ($money, $num): string {
    $value = $row[$def['key']] ?? 0;
    if (($def['type'] ?? '') === 'money') {
    return $money($value);
    }
    return $num($value, (int) ($def['digits'] ?? 0));
    };
    $rows = array_values((array) ($rows ?? []));
    $pages = array_chunk($rows, 6);
    @endphp

    @if ($rows === [])
    <section class="print-page">
        <h1 class="reward-title">委託報酬</h1>
        <div class="reward-meta">
            <div>{{ $selectedMonthText }}</div>
            <div>{{ $selectedPaymentDate ?? '' }} 支払</div>
            <div>{{ ($selectedCompanyId ?? '') !== '' ? $selectedCompanyId : '全社' }}</div>
        </div>
        <div class="empty-message">対象データがありません。</div>
    </section>
    @else
    @foreach ($pages as $pageIndex => $pageRows)
    @php
    $isLastPage = $pageIndex === count($pages) - 1;
    @endphp
    <section class="print-page">
        <h1 class="reward-title">委託報酬</h1>
        <div class="reward-meta">
            <div>{{ $selectedMonthText }}</div>
            <div>{{ $selectedPaymentDate ?? '' }} 支払</div>
            <div>{{ ($selectedCompanyId ?? '') !== '' ? $selectedCompanyId : '全社' }} / {{ $pageIndex + 1 }} / {{ count($pages) }} ページ</div>
        </div>

        <table class="print-table reward-table">
            <thead>
                <tr>
                    <th class="item-col">項目</th>
                    @foreach ($pageRows as $row)
                    <th class="person-col">
                        {{ $row['staff_id'] }}<br>
                        {{ $row['staff_name'] }}
                    </th>
                    @endforeach
                    @if ($isLastPage)
                    <th class="person-col">合計</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($rowGroups as $groupName => $defs)
                <tr class="section-row">
                    <th class="item-col">{{ $groupName }}</th>
                    @foreach ($pageRows as $row)
                    <td></td>
                    @endforeach
                    @if ($isLastPage)
                    <td></td>
                    @endif
                </tr>
                @foreach ($defs as $def)
                <tr class="{{ !empty($def['total']) ? 'total-row' : '' }}">
                    <th class="item-col">{{ $def['label'] }}</th>
                    @foreach ($pageRows as $row)
                    <td class="num">{{ $format($row, $def) }}</td>
                    @endforeach
                    @if ($isLastPage)
                    <td class="num">{{ $format($totals ?? [], $def) }}</td>
                    @endif
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </section>
    @endforeach
    @endif
</body>

</html>
