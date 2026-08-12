<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診日報 印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        /* body {
            margin: 0;
            background: #d8d8d8;
            color: #000;
            font-family: "Yu Gothic", "Meiryo", sans-serif;
            font-size: 12px;
        }

        .print-actions {
            padding: 12px;
        } */

        /* .print-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 16px;
            padding: 10mm 10mm 8mm;
            background: #fff;
            box-sizing: border-box;
        } */

        .print-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: end;
            margin-bottom: 4px;
            font-size: 15px;
            font-weight: 700;
        }

        .print-title {
            margin: 0 0 4px;
            text-align: center;
            font-size: 24px;
            letter-spacing: 0.12em;
            font-weight: 700;
        }

        .print-date {
            font-size: 17px;
        }

        .daily-report-print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }

        .daily-report-print-table th,
        .daily-report-print-table td {
            border: 1px solid #000;
            height: 24px;
            padding: 2px 4px;
            text-align: center;
            vertical-align: middle;
            box-sizing: border-box;
        }

        .daily-report-print-table th {
            font-weight: 700;
        }

        .daily-report-print-table .text-left {
            text-align: left;
        }

        .daily-report-print-table .no-treatment {
            background: #d9d9d9;
        }

        .store-fee {
            background: #56d64e;
        }

        .private-fee {
            background: #67b8e8;
        }

        .print-summary {
            margin-top: 8px;
            font-size: 14px;
            line-height: 1.6;
        }

        .print-generated-at {
            margin-top: 8px;
            font-size: 10px;
        }

        /*
        @media print {
            body {
                background: #fff;
            }

            .print-actions {
                display: none;
            }

            .print-page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }
        } */
    </style>
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    @php
    $targetDate = \Carbon\Carbon::parse($date);
    $weekLabels = ['日', '月', '火', '水', '木', '金', '土'];
    $shownItems = $items->values();
    $rowCount = max(17, $shownItems->count());
    $total_patients = (int) (($summary->total_patients ?? 0) - ($summary->duplicate_patients ?? 0));
    $duplicate_patients = (int) ($summary->duplicate_patients ?? 0);
    $total_distance = $summary->total_distance ?? null;
    $formatNumber = static function ($value): string {
    if ($value === null || $value === '') {
    return '';
    }
    if (!is_numeric($value)) {
    return (string) $value;
    }
    $number = (float) $value;
    if ($number == 0.0) {
    return '';
    }
    return rtrim(rtrim(number_format($number, 1, '.', ''), '0'), '.');
    };
    $formatMoney = static function ($value): string {
    if ($value === null || $value === '' || !is_numeric($value)) {
    return '';
    }
    $number = (float) $value;
    if ($number == 0.0) {
    return '';
    }
    return number_format($number);
    };
    $formatTime = static function ($value): string {
    if (empty($value)) {
    return '';
    }
    $time = \Carbon\Carbon::parse($value)->format('H:i');
    return $time === '00:00' ? '' : $time;
    };
    @endphp

    <section class="print-page">
        <div class="print-header">
            <div>担当：{{ $staffName }}</div>
            <div class="print-date">{{ $targetDate->format('Y/n/j') }}({{ $weekLabels[$targetDate->dayOfWeek] }})</div>
        </div>
        <h1 class="print-title">訪問鍼灸マッサージ・柔整 日報</h1>

        <table class="daily-report-print-table">
            <colgroup>
                <col style="width: 24px;">
                <col style="width: 120px;">
                <col style="width: 95px;">
                <col style="width: 48px;">
                <col style="width: 40px;">
                <col style="width: 40px;">
                <col style="width: 34px;">
                <col style="width: 48px;">
                <col style="width: 64px;">
                <col style="width: 48px;">
                <col style="width: 36px;">
                <col style="width: 36px;">
                <col style="width: 36px;">
                <col>
            </colgroup>
            <thead>
                <tr>
                    <th>No</th>
                    <th>患者名</th>
                    <th>町名</th>
                    <th>距離</th>
                    <th>開始</th>
                    <th>終了</th>
                    <th>往診</th>
                    <th>北在家</th>
                    <th>東加古川</th>
                    <th>播磨町</th>
                    <th>売上金額</th>
                    <th>自費</th>
                    <th>未収金</th>
                    <th>備考</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < $rowCount; $i++)
                    @php
                    $item=$shownItems->get($i);
                    $daily_report_store_name = (string) ($item->daily_report_store_name ?? '');
                    $uncollected_amount = $item ? $formatMoney($item->uncollected_amount) : '';
                    $sales_amount = $item ? $formatMoney(((float) ($item->sales_amount ?? 0)) * 100) : '';
                    $private_fee = $item ? $formatMoney($item->private_fee) : '';
                    $kitazaike_fee = $item && str_contains($daily_report_store_name, '北在家') ? $private_fee : '';
                    $higashikakogawa_fee = $item && str_contains($daily_report_store_name, '東加古川') ? $private_fee : '';
                    $harimacho_fee = $item && str_contains($daily_report_store_name, '播磨町') ? $private_fee : '';
                    $other_private_fee = $item && $private_fee !== '' && $kitazaike_fee === '' && $higashikakogawa_fee === '' && $harimacho_fee === '' ? $private_fee : '';
                    @endphp
                    <tr class="{{ $item && !empty($item->is_no_treatment) ? 'no-treatment' : '' }}">
                        <td>{{ $item ? $i + 1 : '' }}</td>
                        <td>{{ $item->patient_name ?? '' }}</td>
                        <td>{{ $item->daily_report_town_name ?? '' }}</td>
                        <td>{{ $item ? $formatNumber($item->distance) : '' }}</td>
                        <td>{{ $item ? $formatTime($item->start_time) : '' }}</td>
                        <td>{{ $item ? $formatTime($item->end_time) : '' }}</td>
                        <td>{{ $item && !empty($item->is_home_visit) ? '○' : '' }}</td>
                        <td class="{{ $kitazaike_fee !== '' ? 'store-fee' : '' }}">{{ $kitazaike_fee }}</td>
                        <td class="{{ $higashikakogawa_fee !== '' ? 'store-fee' : '' }}">{{ $higashikakogawa_fee }}</td>
                        <td class="{{ $harimacho_fee !== '' ? 'private-fee' : '' }}">{{ $harimacho_fee }}</td>
                        <td>{{ $sales_amount }}</td>
                        <td>{{ $other_private_fee }}</td>
                        <td>{{ $uncollected_amount }}</td>
                        <td class="text-left">{{ $item->remarks ?? '' }}</td>
                    </tr>
                    @endfor
            </tbody>
        </table>

        <div class="print-summary">
            人数：{{ $total_patients }}名（重複人数：{{ $duplicate_patients }}名）<br>
            距離：{{ $formatNumber($total_distance) }}km
        </div>
        <div class="print-generated-at">出力日時：{{ now()->format('Y-m-d H:i:s') }}</div>
    </section>
</body>

</html>
