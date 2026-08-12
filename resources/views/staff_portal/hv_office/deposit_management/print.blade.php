<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 入金管理表 印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
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

        .deposit-print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }

        .deposit-print-table th,
        .deposit-print-table td {
            border: 1px solid #000;
            height: 24px;
            padding: 2px 4px;
            text-align: center;
            vertical-align: middle;
            box-sizing: border-box;
        }

        .deposit-print-table th {
            font-weight: 700;
        }

        .deposit-print-table .text-left {
            text-align: left;
        }

        .print-confirmed {
            margin-top: 8px;
            font-size: 12px;
        }

        .print-generated-at {
            margin-top: 8px;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    <section class="print-page">
        <div class="print-header">
            <div>担当：{{ $staffName }}</div>
            <div class="print-date">{{ \Carbon\Carbon::parse($target_month . '-01')->format('Y/n') }}月</div>
        </div>
        <h1 class="print-title">入金管理表</h1>

        @if($rows->count() === 0)
        <p>「{{ $target_month }}」のデータがありません。</p>
        @else
        <table class="deposit-print-table">
            <thead>
                <tr>
                    <th>患者名</th>
                    <th>負担割合</th>
                    <th>距離(Km)</th>
                    <th>医療助成</th>
                    <th>単価×回数</th>
                    <th>請求金額</th>
                    <th>自費</th>
                    <th>過不足金額</th>
                    <th>集金額</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                @php
                $isPrivate = (int) ($row->is_private_charge ?? 0) === 1;
                $rowTotal = (float) $row->unit_price * (float) $row->billing_count;
                $collectedDisplay = (int) ($row->is_bank_transfer ?? 0) === 1
                    ? '振込'
                    : number_format((float) ($row->collected_amount ?? 0));
                @endphp
                <tr>
                    <td class="text-left">{{ $row->patient_name }}</td>
                    <td>{{ $row->burden_ratio }}割</td>
                    <td>{{ (float) ($row->window_distance ?? 0) == 0.0 ? '' : number_format((float) $row->window_distance, 1) }}</td>
                    <td>{{ (int) ($row->subsidy_limit_count ?? 0) === 0 ? '' : '〇' }}</td>
                    <td>{{ number_format((float) $row->unit_price) }} × {{ $row->billing_count }}</td>
                    <td>{{ $isPrivate ? '' : number_format($rowTotal) }}</td>
                    <td>{{ $isPrivate ? number_format($rowTotal) : '' }}</td>
                    <td>{{ number_format((float) ($row->adjustment_amount ?? 0)) }}</td>
                    <td>{{ $collectedDisplay }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="5">合計</td>
                    <td>{{ number_format($totals['billing_total']) }}</td>
                    <td>{{ number_format($totals['private_total']) }}</td>
                    <td>{{ number_format($totals['adjustment_total']) }}</td>
                    <td>{{ number_format($totals['collected_total']) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="print-confirmed">
            @if($isAllConfirmed)
            確定日：{{ $confirmedAt ? \Carbon\Carbon::parse($confirmedAt)->format('Y-m-d H:i:s') : '' }}
            @else
            この入金管理表は未確定です
            @endif
        </div>
        @endif

        <div class="print-generated-at">出力日時：{{ now()->format('Y-m-d H:i:s') }}</div>
    </section>
</body>

</html>
