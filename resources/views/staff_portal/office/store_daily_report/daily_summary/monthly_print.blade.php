<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>月報 印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        /* @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            margin: 0;
            background: #f3f3f3;
            color: #111;
            font-family: "Yu Gothic", "Meiryo", sans-serif;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .print-actions {
            padding: 12px;
        }

        .print-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 16px;
            padding: 12mm;
            box-sizing: border-box;
            background: #fff;
        }

        .print-header {
            margin-bottom: 8mm;
        }

        .print-title {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 4mm;
        }

        .print-meta {
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: bolder;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12px;
        } */

        /* .print-table th,
        .print-table td {
            border: 1px solid #666;
            padding: 4px 6px;
        } */

        .print-table th {
            background: #f4f4f4;
            text-align: center;
        }

        /* .text-right {
            text-align: right;
        }

        .print-total td {
            font-weight: 700;
            border-top: 2px solid #333;
        } */

        /*
        @media print {
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
            <!-- <div class="print-title"> -->
            <h1>月報</h1>
            <h1>{{ $selectedStore !== '' ? $selectedStore : '全店舗' }}</h1>
            <!-- <span>その他一覧</span> -->
            <!-- </div> -->
            <!-- <h1 class="print-title">月報</h1>
            <div class="print-meta">
                <span>{{ $selectedStore !== '' ? $selectedStore : '全店舗' }}</span>
                <span>{{ $targetMonth }}</span>
            </div> -->
        </header>

        <table class="print-table">
            <colgroup>
                <col style="width: 80px;">
            </colgroup>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>来院人数</th>
                    <th>予約人数</th>
                    <th>院内人数</th>
                    <th>レセコン</th>
                    <th>先生別計</th>
                    <th>差額</th>
                    <th>レセ負担金</th>
                    <th>交通事故</th>
                    <th>レジ</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($printRows ?? []) as $row)
                <tr>
                    <td>{{ $row['日付'] }}</td>
                    <td class="text-right">{{ $row['来院人数'] }}</td>
                    <td class="text-right">{{ $row['予約人数'] }}</td>
                    <td class="text-right">{{ $row['院内人数'] }}</td>
                    <td class="text-right">{{ $row['レセコン'] }}</td>
                    <td class="text-right">{{ $row['先生別計'] }}</td>
                    <td class="text-right">{{ $row['差額'] }}</td>
                    <td class="text-right">{{ $row['レセ負担金'] }}</td>
                    <td class="text-right">{{ $row['交通事故'] }}</td>
                    <td class="text-right">{{ $row['レジ'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">対象データがありません。</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="print-total">
                    <td>合計</td>
                    <td class="text-right">{{ $printTotals['来院人数'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['予約人数'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['院内人数'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['レセコン'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['先生別計'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['差額'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['レセ負担金'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['交通事故'] ?? '' }}</td>
                    <td class="text-right">{{ $printTotals['レジ'] ?? '' }}</td>
                </tr>
            </tfoot>
        </table>
    </main>
</body>

</html>
