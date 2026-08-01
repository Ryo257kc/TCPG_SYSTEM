<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会社負担一覧</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        .print-page {
            width: 297mm;
            min-height: 210mm;
        }

        .company-burden-title {
            margin: 0 0 5mm;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
        }

        .company-burden-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 4mm;
            font-size: 12px;
            font-weight: 700;
        }

        .company-burden-group {
            margin-top: 5mm;
        }

        .company-burden-group:first-of-type {
            margin-top: 0;
        }

        .company-burden-heading {
            margin: 0 0 2mm;
            font-size: 13px;
            font-weight: 700;
        }

        .company-burden-table {
            font-size: 10px;
        }

        .company-burden-table th,
        .company-burden-table td {
            padding: 2px 3px;
            line-height: 1.35;
        }

        .company-burden-table .name {
            text-align: left;
        }

        .company-burden-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .company-burden-table .group-self {
            background: #eaf4ff;
        }

        .company-burden-table .group-office {
            background: #fff2cc;
        }

        .company-burden-table .group-total {
            background: #eef7e8;
        }

        .company-burden-table tfoot th,
        .company-burden-table tfoot td {
            font-weight: 700;
            background: #f3f4f6;
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
        $money = static fn($value): string => (float) $value == 0.0 ? '' : number_format((float) $value);
    @endphp
    <section class="print-page landscape">
        <h1 class="company-burden-title">会社負担一覧</h1>
        <div class="company-burden-meta">
            <div>対象月：{{ $selectedMonth }} / 支給日：{{ $selectedPaymentDate }}</div>
            <div>{{ $companyLabel !== '' ? $companyLabel : '全社' }}　{{ now()->format('Y/n/j H:i:s') }} 現在</div>
        </div>

        @if (empty($groupedStores))
            <div class="empty-message">表示するデータがありません。</div>
        @else
            @foreach ($groupedStores as $group)
                <section class="company-burden-group">
                    <h2 class="company-burden-heading">
                        {{ $group['company_name'] !== '' ? $group['company_name'] : '会社未設定' }}
                        /
                        {{ $group['store_name'] !== '' ? $group['store_name'] : '部署未設定' }}
                    </h2>
                    <table class="print-table company-burden-table">
                        <colgroup>
                            <col style="width: 10mm;">
                            <col style="width: 24mm;">
                            <col style="width: 18mm;">
                            <col style="width: 18mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                            <col style="width: 14mm;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th rowspan="2">番号</th>
                                <th rowspan="2">氏名</th>
                                <th rowspan="2">健保標準</th>
                                <th rowspan="2">厚年標準</th>
                                <th colspan="3">健康保険</th>
                                <th colspan="3">介護保険</th>
                                <th colspan="3">厚生年金</th>
                                <th colspan="2" class="group-office">会社のみ</th>
                                <th rowspan="2" class="group-total">総合計</th>
                            </tr>
                            <tr>
                                <th class="group-self">自己</th>
                                <th class="group-office">会社</th>
                                <th class="group-total">計</th>
                                <th class="group-self">自己</th>
                                <th class="group-office">会社</th>
                                <th class="group-total">計</th>
                                <th class="group-self">自己</th>
                                <th class="group-office">会社</th>
                                <th class="group-total">計</th>
                                <th class="group-office">児童</th>
                                <th class="group-office">子支援</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['rows'] as $row)
                                <tr>
                                    <td class="num">{{ $row['staff_id'] }}</td>
                                    <td class="name">{{ $row['staff_name'] !== '' ? $row['staff_name'] : $row['staff_id'] }}</td>
                                    <td class="num">{{ $money($row['kenpo_standard']) }}</td>
                                    <td class="num">{{ $money($row['kounen_standard']) }}</td>
                                    <td class="num group-self">{{ $money($row['kenpo_self']) }}</td>
                                    <td class="num group-office">{{ $money($row['kenpo_office']) }}</td>
                                    <td class="num group-total">{{ $money($row['kenpo_total']) }}</td>
                                    <td class="num group-self">{{ $money($row['kaigo_self']) }}</td>
                                    <td class="num group-office">{{ $money($row['kaigo_office']) }}</td>
                                    <td class="num group-total">{{ $money($row['kaigo_total']) }}</td>
                                    <td class="num group-self">{{ $money($row['kounen_self']) }}</td>
                                    <td class="num group-office">{{ $money($row['kounen_office']) }}</td>
                                    <td class="num group-total">{{ $money($row['kounen_total']) }}</td>
                                    <td class="num group-office">{{ $money($row['jidou_office']) }}</td>
                                    <td class="num group-office">{{ $money($row['child_support_funds']) }}</td>
                                    <td class="num group-total">{{ $money($row['grand_total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">部署合計</th>
                                <td class="num">{{ $money($group['totals']['kenpo_self'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['kenpo_office'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['kenpo_total'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['kaigo_self'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['kaigo_office'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['kaigo_total'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['kounen_self'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['kounen_office'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['kounen_total'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['jidou_office'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['child_support_funds'] ?? 0) }}</td>
                                <td class="num">{{ $money($group['totals']['grand_total'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <th colspan="4">負担別合計</th>
                                <td colspan="3" class="num group-self">自己 {{ $money($group['totals']['self_total'] ?? 0) }}</td>
                                <td colspan="3" class="num group-office">会社 {{ $money($group['totals']['office_total'] ?? 0) }}</td>
                                <td colspan="3" class="num group-office">会社のみ {{ $money(($group['totals']['jidou_office'] ?? 0) + ($group['totals']['child_support_funds'] ?? 0)) }}</td>
                                <td colspan="3" class="num group-total">総合計 {{ $money($group['totals']['grand_total'] ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </section>
            @endforeach

            <section class="company-burden-group">
                <h2 class="company-burden-heading">総合計</h2>
                <table class="print-table company-burden-table">
                    <tbody>
                        <tr>
                            <th>自己負担合計</th>
                            <td class="num">{{ $money($grandTotals['self_total'] ?? 0) }}</td>
                            <th>会社負担合計</th>
                            <td class="num">{{ $money($grandTotals['office_total'] ?? 0) }}</td>
                            <th>会社のみ合計</th>
                            <td class="num">{{ $money(($grandTotals['jidou_office'] ?? 0) + ($grandTotals['child_support_funds'] ?? 0)) }}</td>
                            <th>総合計</th>
                            <td class="num">{{ $money($grandTotals['grand_total'] ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        @endif
    </section>
</body>

</html>
