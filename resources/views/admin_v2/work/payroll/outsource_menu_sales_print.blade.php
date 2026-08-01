<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>委託メニュー売上</title>
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

        .outsource-sales-title {
            margin: 0 0 5mm;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
        }

        .outsource-sales-meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5mm;
            font-size: 11px;
            font-weight: 700;
        }

        .staff-sales-block {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 7mm;
        }

        .staff-sales-name {
            margin: 0 0 2mm;
            font-size: 14px;
            font-weight: 700;
        }

        .outsource-sales-table {
            font-size: 13px;
            margin-bottom: 0;
        }

        .outsource-sales-table th,
        .outsource-sales-table td {
            padding: 2px 4px;
            line-height: 1.2;
        }

        .outsource-sales-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .outsource-sales-table .menu {
            text-align: left;
        }

        .outsource-sales-table .commission {
            background: #eef5ff;
            font-weight: 700;
        }

        .outsource-sales-table tfoot th,
        .outsource-sales-table tfoot td {
            background: #f3f4f6;
            font-weight: 700;
        }

        .grand-total {
            margin-top: 4mm;
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
    $count = static fn($value): string => (int) $value === 0 ? '' : number_format((int) $value);
    @endphp
    <section class="print-page">
        <h1 class="outsource-sales-title">{{ $salesYear }}年{{ $salesMonth }}月 委託メニュー売上</h1>
        <div class="outsource-sales-meta">
            <div>期間：{{ date('Y/m/d', strtotime($fromDate)) }} - {{ date('Y/m/d', strtotime($toDate)) }}</div>
            <div>支給日：{{ $selectedPaymentDate }}</div>
            <div>会社：{{ $selectedCompanyId !== '' ? $selectedCompanyId : '全社' }}</div>
        </div>

        @if (count($groupedRows) === 0)
        <div class="empty-message">表示するデータがありません。</div>
        @else
        @foreach ($groupedRows as $staffGroup)
        <section class="staff-sales-block">
            <h2 class="staff-sales-name">{{ $staffGroup['staff_name'] !== '' ? $staffGroup['staff_name'] : $staffGroup['staff_id'] }}</h2>
            <table class="print-table outsource-sales-table">
                <colgroup>
                    <col style="width: 9mm;">
                    <col style="width: 20mm;">
                    <col style="width: 35mm;">
                    <col style="width: 14mm;">
                    <col style="width: 21mm;">
                    <col style="width: 21mm;">
                    <col style="width: 14mm;">
                    <col style="width: 21mm;">
                    <col style="width: 21mm;">
                    <col style="width: 21mm;">
                </colgroup>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>日付</th>
                        <th>メニュー</th>
                        <th>保険人数</th>
                        <th>請求金額</th>
                        <th class="commission">保険歩合</th>
                        <th>件数</th>
                        <th>自費合計</th>
                        <th class="commission">自費歩合</th>
                        <th>合計</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staffGroup['rows'] as $row)
                    <tr>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td>{{ $row['sales_date'] }}</td>
                        <td class="menu">{{ $row['menu_name'] }}</td>
                        <td class="num">{{ $count($row['insurance_count']) }}</td>
                        <td class="num">{{ $money($row['claim_total']) }}</td>
                        <td class="num commission">{{ $money($row['insurance_commission']) }}</td>
                        <td class="num">{{ $count($row['patient_count']) }}</td>
                        <td class="num">{{ $money($row['private_total']) }}</td>
                        <td class="num commission">{{ $money($row['private_commission']) }}</td>
                        <td class="num">{{ $money($row['row_total']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th colspan="2">合計</th>
                        <td class="num">{{ $count($staffGroup['totals']['insurance_count'] ?? 0) }}</td>
                        <td class="num">{{ $money($staffGroup['totals']['claim_total'] ?? 0) }}</td>
                        <td class="num">{{ $money($staffGroup['totals']['insurance_commission'] ?? 0) }}</td>
                        <td class="num">{{ $count($staffGroup['totals']['patient_count'] ?? 0) }}</td>
                        <td class="num">{{ $money($staffGroup['totals']['private_total'] ?? 0) }}</td>
                        <td class="num">{{ $money($staffGroup['totals']['private_commission'] ?? 0) }}</td>
                        <td class="num">{{ $money($staffGroup['totals']['row_total'] ?? 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </section>
        @endforeach

        <table class="print-table outsource-sales-table grand-total">
            <colgroup>
                <col style="width: 64mm;">
                <col style="width: 14mm;">
                <col style="width: 21mm;">
                <col style="width: 21mm;">
                <col style="width: 14mm;">
                <col style="width: 21mm;">
                <col style="width: 21mm;">
                <col style="width: 21mm;">
            </colgroup>
            <tfoot>
                <tr>
                    <th>総合計</th>
                    <td class="num">{{ $count($totals['insurance_count'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['claim_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['insurance_commission'] ?? 0) }}</td>
                    <td class="num">{{ $count($totals['patient_count'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['private_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['private_commission'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['row_total'] ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>
        @endif
    </section>
</body>

</html>
