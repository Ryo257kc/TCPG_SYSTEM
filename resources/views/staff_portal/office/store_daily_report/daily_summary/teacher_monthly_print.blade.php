<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>先生別月報 印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        /* @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            margin: 0;
            background: #f3f3f3;
            color: #111;
            font-family: "Yu Gothic", "Meiryo", sans-serif;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        } */

        /* .print-actions {
            padding: 12px;
        } */

        /* .print-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 16px;
            padding: 8mm 10mm;
            box-sizing: border-box;
            background: #fff;
        } */

        /* .print-header {
            margin-bottom: 3mm;
            text-align: center;
        } */

        .print-store {
            font-size: 12px;
            text-align: left;
        }

        /* .print-title { */
        /* border: 1px solid #888; */
        /* display: inline-block;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.08em;
            padding: 1mm 8mm;
        } */

        .teacher-columns {
            column-count: 2;
            column-gap: 6mm;
        }

        .teacher-block {
            break-inside: avoid;
            margin-bottom: 4mm;
            page-break-inside: avoid;
        }

        .teacher-head {
            display: grid;
            grid-template-columns: 18mm 1fr 12mm 22mm;
            align-items: end;
            gap: 1.5mm;
            margin-bottom: 1mm;
        }

        .teacher-label {
            color: #1f5f99;
            font-size: 10.5px;
        }

        .teacher-value {
            border-bottom: 1px solid #777;
            font-size: 12px;
            padding: 0.4mm 1mm;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9.5px;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #777;
            padding: 1px 2px;
        }

        .print-table th {
            background: #f4f4f4;
            color: #1f5f99;
            font-weight: 700;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .teacher-total-box {
            border-collapse: collapse;
            font-size: 10.5px;
            margin: 2.5mm auto 0;
            width: 58mm;
        }

        .teacher-total-box th,
        .teacher-total-box td {
            border: 1px solid #888;
            padding: 1px 3px;
        }

        .teacher-total-box th {
            color: #1f5f99;
            font-weight: 700;
            text-align: right;
            width: 24mm;
        }

        .teacher-total-box td {
            text-align: right;
        }

        .teacher-total-box .money {
            text-align: right;
        }

        .teacher-total-box .count {
            text-align: right;
            width: 12mm;
        }

        .teacher-total-box .unit {
            text-align: left;
            width: 5mm;
        }

        .report-total {
            border-top: 2px solid #333;
            font-weight: 700;
        }

        /* @media print {
            body {
                background: #fff;
            }

            .print-actions {
                display: none;
            }

            .print-page {
                margin: 0;
            }
        } */
    </style>
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    <main class="print-page">
        <header class="print-header">
            <div class="print-store">{{ $selectedStore !== '' ? $selectedStore : '全店舗' }}</div>
            <div class="print-title">施術者別日報集計</div>
        </header>

        <div class="teacher-columns">
            @forelse (($teacherGroups ?? []) as $teacherGroup)
            <section class="teacher-block">
                <div class="teacher-head">
                    <div class="teacher-label">担当者ID</div>
                    <div class="teacher-value">{{ $teacherGroup['担当者名'] !== '' ? $teacherGroup['担当者名'] : $teacherGroup['担当者ID'] }}</div>
                    <div class="teacher-label">日付</div>
                    <div class="teacher-value">{{ $targetMonth }}</div>
                </div>

                <table class="print-table">
                    <colgroup>
                        <col style="width: 18mm;">
                        <col style="width: 11mm;">
                        <col style="width: 17mm;">
                        <col style="width: 11mm;">
                        <col style="width: 17mm;">
                        <col style="width: 17mm;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>人数</th>
                            <th>自費合計</th>
                            <th>人数</th>
                            <th>保険負担</th>
                            <th>請求金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($teacherGroup['rows'] ?? []) as $row)
                        <tr>
                            <td>{{ $row['日付'] }}</td>
                            <td class="text-right">{{ $row['自費人数'] }}</td>
                            <td class="text-right">{{ $row['自費'] }}</td>
                            <td class="text-right">{{ $row['保険人数'] }}</td>
                            <td class="text-right">{{ $row['保険負担'] }}</td>
                            <td class="text-right">{{ $row['請求金額'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <table class="teacher-total-box">
                    <tbody>
                        <tr>
                            <th>日数計</th>
                            <td>{{ $teacherGroup['totals']['日数計'] }}日</td>
                            <td></td>
                        </tr>
                        <tr>
                            <th>自費合計</th>
                            <td class="money">{{ $teacherGroup['totals']['自費'] }}</td>
                            <td class="count">{{ $teacherGroup['totals']['自費人数'] }}人</td>
                        </tr>
                        <tr>
                            <th>保険合計</th>
                            <td class="money">{{ $teacherGroup['totals']['保険合計'] }}</td>
                            <td class="count">{{ $teacherGroup['totals']['保険人数'] }}人</td>
                        </tr>
                        <tr>
                            <th>合計</th>
                            <td class="money">{{ $teacherGroup['totals']['合計'] }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </section>
            @empty
            <p>対象データがありません。</p>
            @endforelse
        </div>

        @if (!empty($teacherGroups ?? []))
        <!-- <table class="print-table">
            <tfoot>
                <tr class="report-total">
                    <td>総合計</td>
                    <td class="text-right">{{ $printTotals['自費人数'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['自費'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['保険人数'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['保険合計'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['合計'] ?? '' }}</td>
                </tr>
            </tfoot>
        </table> -->
        @endif
    </main>
</body>

</html>
