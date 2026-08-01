<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 保険請求未収入金</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        .print-company {
            font-size: 13px;
        }

        /* .print-meta {
            text-align: right;
            font-size: 12px;
        } */

        .company-section {
            margin-bottom: 8mm;
            break-after: page;
            page-break-after: always;
        }

        .company-section:last-of-type {
            break-after: auto;
            page-break-after: auto;
        }

        .company-section h2 {
            margin: 0 0 3mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #111827;
            font-size: 15px;
        }

        .store-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4mm 6mm;
            align-items: start;
        }

        .store-section {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .store-title {
            display: flex;
            gap: 8px;
            margin-bottom: 1mm;
            font-size: 13px;
            font-weight: 700;
        }

        .uncollected-print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .uncollected-print-table th,
        .uncollected-print-table td {
            padding: 3px 6px;
            border: 1px solid #111827;
            vertical-align: middle;
        }

        .uncollected-print-table th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }

        .uncollected-print-table th:nth-child(1),
        .uncollected-print-table td:nth-child(1) {
            width: 35%;
        }

        .uncollected-print-table th:nth-child(2),
        .uncollected-print-table td:nth-child(2) {
            width: 20%;
        }

        .uncollected-print-table th:nth-child(3),
        .uncollected-print-table td:nth-child(3) {
            width: 45%;
        }

        .number {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .uncollected-print-table tfoot td {
            font-weight: 700;
            background: #f9fafb;
        }

        .uncollected-detail-print-page {
            width: 277mm;
            min-height: 190mm;
            padding: 5mm;
        }

        .uncollected-detail-company-section {
            margin-bottom: 6mm;
            break-after: auto;
            page-break-after: auto;
        }

        .uncollected-detail-store-section {
            margin-bottom: 4mm;
            break-inside: auto;
            page-break-inside: auto;
        }

        .uncollected-detail-print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9px;
        }

        .uncollected-detail-print-table th,
        .uncollected-detail-print-table td {
            padding: 2px 3px;
            border: 1px solid #111827;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        .uncollected-detail-print-table th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }

        .uncollected-detail-print-table th:nth-child(1),
        .uncollected-detail-print-table td:nth-child(1) {
            width: 8%;
        }

        .uncollected-detail-print-table th:nth-child(2),
        .uncollected-detail-print-table td:nth-child(2) {
            width: 9%;
        }

        .uncollected-detail-print-table th:nth-child(3),
        .uncollected-detail-print-table td:nth-child(3) {
            width: 17%;
        }

        .uncollected-detail-print-table th:nth-child(4),
        .uncollected-detail-print-table td:nth-child(4) {
            width: 5%;
        }

        .uncollected-detail-print-table th:nth-child(12),
        .uncollected-detail-print-table td:nth-child(12) {
            width: 17%;
        }

        .grand-total {
            margin-top: 8mm;
            padding-top: 3mm;
            border-top: 2px solid #111827;
            text-align: right;
            font-size: 18px;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    <main class="print-page">
        <header class="print-header">
            <h1>保険請求未収入金</h1>
            <div class="print-meta12">印刷月 {{ $paymentMonth ?? '' }} 月迄／ {{ now()->format('Y/m/d H:i') }} 現在</div>
        </header>

        @forelse (collect($printRows ?? [])->groupBy('company_name') as $companyName => $companyRows)
        <section class="company-section">
            <h2>{{ $companyName }}</h2>

            <div class="store-grid">
                @foreach ($companyRows->groupBy('store_category') as $storeCategory => $storeRows)
                <section class="store-section">
                    <div class="store-title">
                        <span>{{ $storeCategory }}</span>
                        @if (($storeRows->first()['store_label'] ?? '') !== '')
                        <span>{{ $storeRows->first()['store_label'] }}</span>
                        @endif
                    </div>

                    <table class="uncollected-print-table">
                        <thead>
                            <tr>
                                <th>施術月</th>
                                <th>件数</th>
                                <th>未収入金</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($storeRows as $row)
                            <tr>
                                <td>{{ $row['treatment_month'] }}</td>
                                <td class="number">{{ $row['receipt_count'] }}</td>
                                <td class="number">{{ $row['remaining_amount_display'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">合計</td>
                                <td class="number">{{ number_format((float) $storeRows->sum('remaining_amount')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </section>
                @endforeach
            </div>
        </section>
        @empty
        <p class="empty-message">対象データがありません。</p>
        @endforelse

        <footer class="grand-total">
            総合計 {{ number_format((float) ($grandTotal ?? 0)) }}
        </footer>
    </main>
</body>

</html>
