<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 領収書{{ $isPrivate ? '（自費）' : '（保険）' }}印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        .receipt-print-block {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #000;
        }

        .receipt-print-heading {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: end;
            margin-bottom: 4px;
        }

        .receipt-print-heading h2 {
            margin: 0;
            font-size: 18px;
        }

        .receipt-print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .receipt-print-table th,
        .receipt-print-table td {
            border: 1px solid #000;
            padding: 3px 6px;
            text-align: center;
        }

        .receipt-print-table .text-left {
            text-align: left;
        }

        .receipt-print-store {
            font-size: 13px;
            text-align: right;
            padding-top: 22px;
        }

        .receipt-print-store div {
            margin-bottom: 4px;
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
        <h1 class="print-title">領収書{{ $isPrivate ? '（自費）' : '（保険）' }}</h1>
        <p>対象月：{{ \Carbon\Carbon::parse($month . '-01')->format('Y年n月') }}</p>

        @if($receipts->isEmpty())
        <p>「{{ $month }}」の{{ $isPrivate ? '自費' : '保険' }}対象のデータがありません。</p>
        @else
        @foreach($receipts as $receipt)
        <div class="receipt-print-block">
            <div>
                <div class="receipt-print-heading">
                    <h2>{{ $receipt['heading'] }}</h2>
                </div>
                <p>{{ $receipt['patient_name'] }} 様
                    @if($isPrivate)
                    （自費）
                    @else
                    （負担割合 {{ $receipt['burden_ratio'] }}割）
                    @endif
                </p>

                <table class="receipt-print-table">
                    <thead>
                        <tr>
                            <th>内容</th>
                            <th>単価</th>
                            <th>回数</th>
                            <th>合計</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($receipt['lines'] as $line)
                        <tr>
                            <td class="text-left">{{ $line['description'] }}</td>
                            <td>{{ number_format((float) $line['unit_price']) }}</td>
                            <td>{{ $line['billing_count'] }}</td>
                            <td>{{ number_format($line['subtotal']) }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="3"><strong>合計</strong></td>
                            <td><strong>{{ number_format($receipt['total']) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="receipt-print-store">
                <div>{{ $receipt['billing_date_label'] }}</div>
                <div>{{ $receipt['store_name'] }}</div>
                <div>{{ $receipt['store_address'] }}</div>
                <div>TEL：{{ $receipt['phone'] }}</div>
            </div>
        </div>
        @endforeach
        @endif

        <div class="print-generated-at">出力日時：{{ now()->format('Y-m-d H:i:s') }}</div>
    </section>
</body>

</html>
