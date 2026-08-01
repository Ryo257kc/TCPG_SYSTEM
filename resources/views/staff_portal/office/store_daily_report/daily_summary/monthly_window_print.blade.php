<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>月間日報 印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        .window-report-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            align-items: end;
            margin-bottom: 8mm;
        }

        .window-report-title {
            text-align: center;
            font-size: 22px;
            letter-spacing: 0;
        }

        .window-report-meta {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
        }

        .window-report-store {
            text-align: right;
            font-size: 18px;
            font-weight: 700;
        }

        .window-report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }

        .window-report-table th,
        .window-report-table td {
            border: 1px solid #999;
            padding: 3px 4px;
        }

        .window-report-table th {
            font-weight: 700;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .window-total-row td {
            border-top: 2px solid #777;
            font-weight: 700;
        }

        .window-summary-box {
            width: 42%;
            margin: 8mm 0 0 auto;
            border-collapse: collapse;
            font-size: 14px;
        }

        .window-summary-box th,
        .window-summary-box td {
            border: 1px solid #999;
            padding: 5px 8px;
        }

        .window-summary-box th {
            font-weight: 400;
            text-align: center;
        }
    </style>
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    <main class="print-page">
        <header class="window-report-header">
            <div class="window-report-title">日報　窓口　負担金</div>
            <div class="window-report-meta">{{ $targetMonthWareki }}</div>
            <div class="window-report-store">{{ $selectedStore !== '' ? $selectedStore : '全店舗' }}</div>
        </header>

        <table class="window-report-table">
            <colgroup>
                <col style="width: 38px;">
                <col style="width: 52px;">
                <col style="width: 68px;">
                <col style="width: 82px;">
                <col style="width: 82px;">
                <col style="width: 82px;">
                <col style="width: 82px;">
                <col style="width: 58px;">
                <col style="width: 92px;">
                <col style="width: 72px;">
            </colgroup>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>新患</th>
                    <th>新患扱い</th>
                    <th>自費</th>
                    <th>保険負担</th>
                    <th>その他</th>
                    <th>合計</th>
                    <th>人数</th>
                    <th>レセ負担金</th>
                    <th>差額</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($windowRows ?? []) as $row)
                <tr>
                    <td class="text-right">{{ $row['day'] }}</td>
                    <td class="text-right">{{ number_format($row['new_patient_count']) }}</td>
                    <td class="text-right">{{ number_format($row['new_like_count']) }}</td>
                    <td class="text-right">{{ number_format($row['self_pay_amount']) }}</td>
                    <td class="text-right">{{ number_format($row['insurance_amount']) }}</td>
                    <td class="text-right">{{ $row['expense_amount'] != 0 ? number_format($row['expense_amount']) : '' }}</td>
                    <td class="text-right">{{ number_format($row['total_amount']) }}</td>
                    <td class="text-right">{{ number_format($row['people_count']) }}</td>
                    <td class="text-right">{{ number_format($row['receipt_burden_amount']) }}</td>
                    <td class="text-right">{{ $row['difference_amount'] != 0 ? number_format($row['difference_amount']) : '0' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">対象データがありません。</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="window-total-row">
                    <td>{{ number_format($windowTotals['days'] ?? 0) }}日</td>
                    <td class="text-right">{{ number_format($windowTotals['new_patient_count'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($windowTotals['new_like_count'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($windowTotals['self_pay_amount'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($windowTotals['insurance_amount'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($windowTotals['expense_amount'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($windowTotals['total_amount'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($windowTotals['people_count'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($windowTotals['receipt_burden_amount'] ?? 0) }}</td>
                    <td class="text-right">{{ number_format($windowTotals['difference_amount'] ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>

        <table class="window-summary-box">
            <tbody>
                <tr>
                    <th>銀行入金</th>
                    <td class="text-right">¥{{ number_format($windowTotals['total_amount'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>保険合計</th>
                    <td class="text-right">¥{{ number_format($windowTotals['insurance_amount'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>レセ負担金</th>
                    <td class="text-right">¥{{ number_format($windowTotals['summary_receipt_burden_amount'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>差額</th>
                    <td class="text-right">{{ number_format(($windowTotals['insurance_amount'] ?? 0) - ($windowTotals['summary_receipt_burden_amount'] ?? 0)) }}</td>
                </tr>
            </tbody>
        </table>
    </main>
</body>

</html>