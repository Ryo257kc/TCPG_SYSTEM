<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>往診売上詳細</title>
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

        .home-visit-detail-title {
            margin: 0 0 5mm;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
        }

        .home-visit-detail-meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5mm;
            font-size: 13px;
            font-weight: 700;
        }

        .staff-detail-page {
            break-after: page;
            page-break-after: always;
        }

        .staff-detail-page:last-child {
            break-after: auto;
            page-break-after: auto;
        }

        .staff-detail-name {
            margin: 0 0 2mm;
            font-size: 14px;
            font-weight: 700;
        }

        .home-visit-detail-table {
            font-size: 15px;
            /* line-height: 1.7; */
            margin-bottom: 0;
        }

        .home-visit-detail-table th,
        .home-visit-detail-table td {
            padding: 2px 2px;
            line-height: 1.2;
        }

        .home-visit-detail-table tr {
            padding: 2px 2px;
            font-size: 14px;
            line-height: 1.2;
        }

        .home-visit-detail-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .home-visit-detail-table .total-col {
            background: #eef5ff;
            font-weight: 700;
        }

        .home-visit-detail-table .all-total-col {
            background: #fff2cc;
            font-weight: 700;
        }

        .home-visit-detail-table tfoot th,
        .home-visit-detail-table tfoot td {
            background: #f3f4f6;
            font-weight: 700;
        }

        .summary {
            line-height: 1.8;
            margin-top: 18px;
            width: 350px;
            font-size: 20px;
            font-weight: bolder;
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
    $distance = static fn($value): string => (float) $value == 0.0 ? '' : rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    @endphp

    @if (count($groupedRows) === 0)
    <section class="print-page">
        <h1 class="home-visit-detail-title">{{ $salesYear }}年{{ $salesMonth }}月 往診売上詳細</h1>
        <div class="home-visit-detail-meta">
            <div>期間：{{ date('Y/n/j', strtotime($fromDate)) }} - {{ date('Y/n/j', strtotime($toDate)) }}</div>
            <div>給与日：{{ $selectedPaymentDate }}</div>
            <div>会社：{{ $selectedCompanyId !== '' ? $selectedCompanyId : '全社' }}</div>
        </div>
        <div class="empty-message">表示するデータがありません。</div>
    </section>
    @else
    @foreach ($groupedRows as $staffGroup)
    <section class="print-page staff-detail-page">
        <h1 class="home-visit-detail-title">{{ $salesYear }}年{{ $salesMonth }}月 往診売上詳細</h1>
        <div class="home-visit-detail-meta">
            <div>期間：{{ date('Y/n/j', strtotime($fromDate)) }} - {{ date('Y/n/j', strtotime($toDate)) }}</div>
            <div>給与日：{{ $selectedPaymentDate }}</div>
            <div>会社：{{ $selectedCompanyId !== '' ? $selectedCompanyId : '全社' }}</div>
        </div>
        <h2 class="staff-detail-name">{{ $staffGroup['staff_name'] !== '' ? $staffGroup['staff_name'] : $staffGroup['staff_id'] }}</h2>
        <table class="print-table home-visit-detail-table">
            <colgroup>
                <col style="width: 17mm;">
                <col style="width: 10mm;">
                <col style="width: 10mm;">
                <col style="width: 14mm;">
                <col style="width: 14mm;">
                <col style="width: 14mm;">
                <col style="width: 14mm;">
                <col style="width: 14mm;">
                <col style="width: 14mm;">
                <col style="width: 14mm;">
                <col style="width: 14mm;">
                <!-- <col style="width: 14mm;">
                <col style="width: 16mm;">
                <col style="width: 16mm;"> -->
            </colgroup>
            <thead>
                <tr>
                    <th>施術日</th>
                    <th>人数</th>
                    <th>距離</th>
                    <th>北在家</th>
                    <th>東加古川</th>
                    <th>播磨町</th>
                    <th>さくら<br>鍼灸</th>
                    <th>織田鍼灸</th>
                    <th>宮本鍼灸</th>
                    <th>横井鍼灸</th>
                    <th>自費</th>
                    <!-- <th>未収</th>
                    <th class="total-col">店舗合計</th>
                    <th class="all-total-col">店舗合計<br>鍼灸含む</th> -->
                </tr>
            </thead>
            <tbody>
                @foreach ($staffGroup['rows'] as $row)
                <tr>
                    <td>{{ $row['treatment_date'] }}</td>
                    <td class="num">{{ $count($row['people_count']) }}</td>
                    <td class="num">{{ $distance($row['distance_total']) }}</td>
                    <td class="num">{{ $money($row['kitazaike_total']) }}</td>
                    <td class="num">{{ $money($row['higashi_kakogawa_total']) }}</td>
                    <td class="num">{{ $money($row['harima_total']) }}</td>
                    <td class="num">{{ $money($row['sakura_total']) }}</td>
                    <td class="num">{{ $money($row['orita_total']) }}</td>
                    <td class="num">{{ $money($row['miyamoto_total']) }}</td>
                    <td class="num">{{ $money($row['yokoi_total']) }}</td>
                    <td class="num">{{ $money($row['private_fee_total']) }}</td>
                    <!-- <td class="num">{{ $money($row['uncollected_total']) }}</td>
                    <td class="num total-col">{{ $money($row['store_total']) }}</td>
                    <td class="num all-total-col">{{ $money($row['store_total_with_hari']) }}</td> -->
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>合計</th>
                    <td class="num">{{ $count($staffGroup['totals']['people_count'] ?? 0) }}</td>
                    <td class="num">{{ $distance($staffGroup['totals']['distance_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($staffGroup['totals']['kitazaike_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($staffGroup['totals']['higashi_kakogawa_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($staffGroup['totals']['harima_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($staffGroup['totals']['sakura_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($staffGroup['totals']['orita_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($staffGroup['totals']['miyamoto_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($staffGroup['totals']['yokoi_total'] ?? 0) }}</td>
                    <td class="num">{{ $money($staffGroup['totals']['private_fee_total'] ?? 0) }}</td>


                    <!-- <td class="num">{{ $money($staffGroup['totals']['uncollected_total'] ?? 0) }}</td>
                    <td class="num total-col">{{ $money($staffGroup['totals']['store_total'] ?? 0) }}</td>
                    <td class="num all-total-col">{{ $money($staffGroup['totals']['store_total_with_hari'] ?? 0) }}</td> -->
                </tr>
            </tfoot>
        </table>
        <div class="summary">
            <div style="display:flex; justify-content:space-between;">
                <span>未収入金</span>
                <span style="font-variant-numeric: tabular-nums;">
                    {{ $money($staffGroup['totals']['uncollected_total'] ?? 0) ?: 0 }}円
                </span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span>合計（未収入金含む）</span>
                <span style="font-variant-numeric: tabular-nums;">
                    {{ $money($staffGroup['totals']['store_total_with_hari'] ?? 0) ?: 0 }}円
                    <!-- {{ $money($staffGroup['totals']['store_total_with_hari'] ?? 0) }}円 -->
                </span>
            </div>
        </div>
    </section>
    @endforeach
    @endif
</body>

</html>
