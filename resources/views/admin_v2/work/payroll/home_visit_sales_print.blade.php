<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>往診個人別売上</title>
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

        .home-visit-sales-title {
            margin: 0 0 6mm;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
        }

        .home-visit-sales-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 4mm;
            font-size: 13px;
            font-weight: 700;
        }

        .home-visit-sales-table {
            font-size: 14px;
        }

        .home-visit-sales-table th,
        .home-visit-sales-table td {
            padding: 2px 3px;
            line-height: 1.5;
        }

        .home-visit-sales-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .home-visit-sales-table .name {
            text-align: left;
        }

        .home-visit-sales-table .group-store {
            background: #fff2cc;
        }

        .home-visit-sales-table .group-home {
            background: #d9e2f3;
        }

        .home-visit-sales-table tfoot th,
        .home-visit-sales-table tfoot td {
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
    $count = static fn($value): string => (int) $value === 0 ? '' : number_format((int) $value);
    @endphp
    <section class="print-page landscape">
        <h1 class="home-visit-sales-title">{{ $salesYear }}年{{ $salesMonth }}月 個人別売上集計</h1>
        <div class="home-visit-sales-meta">
            <div>支給日：{{ $selectedPaymentDate }}</div>
            <div>
                <!-- 会社： -->
                {{-- $selectedCompanyId !== '' ? $selectedCompanyId : '全社' --}}{{ now()->format('Y/n/j H:i:s') }} 現在
            </div>
        </div>

        @if (count($staffGroups ?? []))
        <div class="empty-message">表示するデータがありません。</div>
        @else
        <table class="print-table home-visit-sales-table">
            <colgroup>
                <col style="width: 5mm;">
                <col style="width: 17mm;">
                <col style="width: 8mm;">
                <col style="width: 14mm;">
                <col style="width: 8mm;">
                <col style="width: 14mm;">
                <col style="width: 8mm;">
                <col style="width: 14mm;">
                <col style="width: 8mm;">
                <col style="width: 14mm;">
                <col style="width: 15mm;">
                <col style="width: 15mm;">
                <col style="width: 8mm;">
                <col style="width: 16mm;">
                <col style="width: 17mm;">
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">氏名</th>
                    <th colspan="4">さくら鍼灸整骨院</th>
                    <th colspan="4">ひなた鍼灸整骨院</th>
                    <th colspan="2" class="group-store">店舗合計</th>
                    <th colspan="2" class="group-home">往診</th>
                    <th rowspan="2">売上合計</th>
                </tr>
                <tr>
                    <th>人数</th>
                    <th>保険</th>
                    <th>人数</th>
                    <th>自費</th>
                    <th>人数</th>
                    <th>保険</th>
                    <th>人数</th>
                    <th>自費</th>
                    <th class="group-store">保険合計</th>
                    <th class="group-store">自費合計</th>
                    <th class="group-home">人数</th>
                    <th class="group-home">売上</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td class="name">{{ $row['staff_name'] !== '' ? $row['staff_name'] : $row['staff_id'] }}</td>
                    <td class="num">{{ $count($row['sakura_insurance_count']) }}</td>
                    <td class="num">{{ $money($row['sakura_insurance']) }}</td>
                    <td class="num">{{ $count($row['sakura_private_count']) }}</td>
                    <td class="num">{{ $money($row['sakura_private']) }}</td>
                    <td class="num">{{ $count($row['hinata_insurance_count']) }}</td>
                    <td class="num">{{ $money($row['hinata_insurance']) }}</td>
                    <td class="num">{{ $count($row['hinata_private_count']) }}</td>
                    <td class="num">{{ $money($row['hinata_private']) }}</td>
                    <td class="num group-store">{{ $money($row['store_insurance_total']) }}</td>
                    <td class="num group-store">{{ $money($row['store_private_total']) }}</td>
                    <td class="num group-home">{{ $count($row['home_visit_count']) }}</td>
                    <td class="num group-home">{{ $money($row['home_visit_sales']) }}</td>
                    <td class="num">{{ $money($row['sales_total']) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th></th>
                    <th>合計</th>
                    <td class="num">{{ $count($totals['sakura_insurance_count'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['sakura_insurance'] ?? 0) }}</td>
                    <td class="num">{{ $count($totals['sakura_private_count'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['sakura_private'] ?? 0) }}</td>
                    <td class="num">{{ $count($totals['hinata_insurance_count'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['hinata_insurance'] ?? 0) }}</td>
                    <td class="num">{{ $count($totals['hinata_private_count'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['hinata_private'] ?? 0) }}</td>

                    <td class="num">{{ $money($totals['store_insurance_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['store_private_total'] ?? 0) }}</td>
                    <td class="num">{{ $count($totals['home_visit_count'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['home_visit_sales'] ?? 0) }}</td>
                    <td class="num">{{ $money($totals['sales_total'] ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>
        @endif
    </section>
</body>

</html>
