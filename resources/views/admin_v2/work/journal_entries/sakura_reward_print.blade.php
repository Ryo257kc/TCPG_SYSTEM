<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>さくら報酬計算</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        .reward-page {
            page-break-after: always;
        }

        .reward-page:last-child {
            page-break-after: auto;
        }

        .reward-title {
            /* margin: 0 0 8mm; */
            text-align: center;
            font-size: 26px;
            font-weight: 700;
        }

        .reward-subtitle {
            margin: 0 0 4mm;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
        }

        .reward-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
            font-size: 12px;
            font-weight: 700;
        }

        .reward-summary {
            width: 135mm;
            /* margin: 0 auto 8mm; */
            border: none;
            border-collapse: collapse;
            font-size: 20px;
            line-height: 1.8;
            margin-top: 50px;
        }

        .reward-summary th,
        .reward-summary td {
            /* border: 1px solid #111; */
            padding: 5px 7px;
            border: none;
        }

        .reward-summary th {
            text-align: right;
            /* background: #f3f4f6; */
        }

        .reward-summary .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .reward-summary-meta {
            /* display: flex;
            justify-content: space-between; */
            /* margin-bottom: 2mm; */
            font-size: 14px;
            /* font-weight: 700; */
        }

        .reward-table {
            font-size: 13px;
        }

        .reward-table th,
        .reward-table td {
            padding: 2px 3px;
            line-height: 1.35;
        }

        .reward-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .reward-table .text {
            text-align: left;
        }

        .reward-table tfoot th,
        .reward-table tfoot td {
            font-weight: 700;
            background: #f3f4f6;
        }

        .reward-note {
            margin-top: 4mm;
            font-size: 11px;
        }

        .group th,
        .group td {
            font-size: 14px;
            font-weight: bolder;
            line-height: 2;
        }

        .group th {
            text-align: right;
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
    $money = static fn($value): string => number_format((float) $value);
    $moneyBlank = static fn($value): string => (float) $value == 0.0 ? '' : number_format((float) $value);
    @endphp

    <section class="print-page reward-page">
        <h1 class="reward-title">さくら鍼灸整骨院</h1>
        <!-- <div class="reward-meta"> -->
        <h2 class="reward-subtitle">{{ $targetMonth }}月施術月</h2>
        <!-- <div>{{ $dateFrom }} - {{ $dateTo }}</div>
        </div> -->

        <table class="reward-summary">
            <tbody>
                <tr>
                    <th>さくら売上</th>
                    <td class="num">{{ $money($summary['sakura_sales'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>さくら経費</th>
                    <td class="num">{{ $money($summary['store_expense_total'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>会社経費（÷２）</th>
                    <td class="num">{{ $money($summary['company_expense_total'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th></th>
                    <td class="num">　</td>
                </tr>
                <tr>
                    <th>さくら売上ー経費　計</th>
                    <td class="num">{{ $money($summary['sakura_sales']-$summary['store_expense_total'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>ー会社経費　計</th>
                    <td class="num">{{ $money($summary['company_expense_total'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>さくら残金ー会社経費</th>
                    <td class="num">{{ $money($summary['profit'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th></th>
                    <td class="num">　</td>
                </tr>
                <tr class="reward-summary-meta">
                    <th>井上 66%</th>
                    <td class="num">{{ $money($summary['inoue_profit'] ?? 0) }}</td>
                </tr>
                <tr class="reward-summary-meta">
                    <th>会社 34%</th>
                    <td class="num">{{ $money($summary['company_profit'] ?? 0) }}</td>
                </tr>
                <tr class="reward-summary-meta">
                    <th>保証料</th>
                    <td class="num">{{ $money($summary['guarantee_fee'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th></th>
                    <td class="num">　</td>
                </tr>
                <tr>
                    <th>井上</th>
                    <td class="num">{{ $money($summary['inoue_payment'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>会社</th>
                    <td class="num">{{ $money($summary['company_profit'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>合計</th>
                    <td class="num">{{ $money($summary['total_payment'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="print-page reward-page">
        <h1 class="reward-title">さくら鍼灸整骨院　経費</h1>
        <div class="reward-meta">
            <div>対象月：{{ $targetMonth }}</div>
            <div>さくら売上：{{ $money($summary['sakura_sales'] ?? 0) }}</div>
        </div>

        <!-- <h2 class="reward-subtitle">店舗経費</h2> -->
        <table class="print-table reward-table">
            <thead>
                <tr>
                    <th style="width: 25mm;">発生日</th>
                    <th style="width: 70mm;">品目</th>
                    <th>支払先</th>
                    <th style="width: 15mm;">件数</th>
                    <th style="width: 25mm;">金額</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($storeGroups as $group)
                @foreach ($group['summary_rows'] as $row)
                <tr>
                    <td>{{ $row['occurred_at'] }}</td>
                    <td class="text">{{ $row['item_category'] }}</td>
                    <td class="text">{{ $row['item'] }}</td>
                    <td class="num">{{ number_format((int) ($row['count'] ?? 0)) }}</td>
                    <td class="num">{{ $moneyBlank($row['expense_amount']) }}</td>
                </tr>
                @endforeach
                <tr class="group">
                    <th colspan="3">小計</th>
                    <td class="num">{{ number_format((int) ($group['count'] ?? 0)) }}</td>
                    <td class="num">{{ $money($group['total'] ?? 0) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">表示するデータがありません。</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4">さくら経費 計</th>
                    <td class="num">{{ $money($summary['store_expense_total'] ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </section>

    <section class="print-page reward-page">
        <h1 class="reward-title">会社全体　経費</h1>
        <div class="reward-meta">
            <div>対象月：{{ $targetMonth }}</div>
            <div>会社経費：{{ $money($summary['company_expense_total'] ?? 0) }}</div>
        </div>

        <!-- <h2 class="reward-subtitle">分割負担</h2> -->
        <table class="print-table reward-table">
            <thead>
                <tr>
                    <th style="width: 30mm;">発生日</th>
                    <th style="width: 32mm;">品目</th>
                    <th>支払先</th>
                    <th style="width: 15mm;">件数</th>
                    <th style="width: 18mm;">金額</th>
                    <th style="width: 14mm;">割合</th>
                    <th style="width: 25mm;">分割金額</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companyGroups as $company)
                <tr>
                    <th colspan="7" class="text">{{ $company['company_name'] }}</th>
                </tr>
                @foreach ($company['groups'] as $group)
                @foreach ($group['summary_rows'] as $row)
                <tr>
                    <td>{{ $row['occurred_at'] }}</td>
                    <td class="text">{{ $row['item_category'] }}</td>
                    <td class="text">{{ $row['item'] }}</td>
                    <td class="num">{{ number_format((int) ($row['count'] ?? 0)) }}</td>
                    <td class="num">{{ $moneyBlank($row['expense_amount']) }}</td>
                    <td class="num">{{ $row['allocation_ratio'] }}</td>
                    <td class="num">{{ $moneyBlank($row['allocated_amount']) }}</td>
                </tr>
                @endforeach
                <!-- <tr class="group">
                    <th colspan="6">{{ $group['name'] }} 小計</th>
                    <td class="num">{{ $money($group['total'] ?? 0) }}</td>
                </tr> -->
                @endforeach
                <tr class="group">
                    <th colspan="3">会社合計</th>
                    <td class="num">{{ number_format((int) ($company['count'] ?? 0)) }}</td>
                    <td></td>
                    <td></td>
                    <td class="num">{{ $money($company['total'] ?? 0) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">表示するデータがありません。</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="group">
                    <th colspan="6">会社経費 計</th>
                    <td class="num">{{ $money($summary['company_expense_total'] ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </section>
</body>

</html>
