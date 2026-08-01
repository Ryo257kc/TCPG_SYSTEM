<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>先生別日報 印刷</title>
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
        }

        .print-actions {
            padding: 12px;
        }

        .print-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 16px;
            padding: 8mm 10mm;
            box-sizing: border-box;
            background: #fff;
        } */

        .teacher-columns {
            column-count: 2;
            column-gap: 8mm;
        }

        .teacher-block {
            break-inside: avoid;
            margin-bottom: 8mm;
            page-break-inside: avoid;
        }

        .teacher-head {
            display: grid;
            grid-template-columns: 1fr 28mm;
            gap: 3mm;
            align-items: end;
            margin-bottom: 2mm;
            font-size: 15px;
        }

        .teacher-date {
            text-align: center;
        }

        .teacher-name {
            text-align: right;
        }

        .teacher-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5px;
        }

        .teacher-table th,
        .teacher-table td {
            border: 1px solid #333;
            padding: 1.5px 3px;
        }

        .teacher-table th {
            font-weight: 400;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .daily-order-new {
            background-color: #9ed8ff !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .daily-order-new-like {
            background-color: #fff36a !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .teacher-total td {
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
        <div class="print-store">{{ $selectedStore !== '' ? $selectedStore : '全店舗' }}</div>

        <div class="teacher-columns">
            @forelse (($teacherDailyGroups ?? []) as $teacherGroup)
            <section class="teacher-block">
                <div class="teacher-head">
                    <div class="teacher-date">{{ $teacherGroup['日付'] }}</div>
                    <div class="teacher-name">{{ $teacherGroup['担当者名'] !== '' ? $teacherGroup['担当者名'] : $teacherGroup['担当者ID'] }}</div>
                </div>

                <table class="teacher-table">
                    <colgroup>
                        <col style="width: 9mm;">
                        <col style="width: 14mm;">
                        <col>
                        <col style="width: 16mm;">
                        <col style="width: 16mm;">
                        <col style="width: 16mm;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th></th>
                            <th>時刻</th>
                            <th>患者名</th>
                            <th>自費</th>
                            <th>保険負担</th>
                            <th>請求金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (($teacherGroup['rows'] ?? []) as $row)
                        @php
                        $dailyOrderClass = match (trim((string) ($row['新患'] ?? ''))) {
                        '新患' => 'daily-order-new',
                        '新患扱い' => 'daily-order-new-like',
                        default => '',
                        };
                        @endphp
                        <tr>
                            <td class="text-center {{ $dailyOrderClass }}">{{ $row['日別順'] }}</td>
                            <td class="text-center">{{ $row['時刻'] }}</td>
                            <td>{{ $row['患者名'] }}</td>
                            <td class="text-right">{{ $row['自費'] }}</td>
                            <td class="text-right">{{ $row['保険負担'] }}</td>
                            <td class="text-right">{{ $row['請求金額'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="teacher-total">
                            <td colspan="3" class="text-right">総合計</td>
                            <td class="text-right">{{ $teacherGroup['totals']['自費'] }}</td>
                            <td class="text-right">{{ $teacherGroup['totals']['保険負担'] }}</td>
                            <td class="text-right">{{ $teacherGroup['totals']['請求金額'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </section>
            @empty
            <p>対象データがありません。</p>
            @endforelse
        </div>
    </main>
</body>

</html>
