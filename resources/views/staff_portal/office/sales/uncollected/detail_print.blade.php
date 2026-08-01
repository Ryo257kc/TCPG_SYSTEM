<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 保険請求未収入金 個別一覧</title>
    <!-- <link rel="stylesheet" href="{{ asset('css/staff_portal/uncollected_print.css') }}"> -->
    <!-- <link rel="stylesheet" href="{{ asset('css/staff_portal/uncollected_detail_print.css') }}"> -->
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
</head>

<style>
    @media print {
        @page {
            size: A4 landscape;
        }
    }

    /* @page {
    size: A4 landscape;
    margin: 8mm;
}

@media print {
    .uncollected-detail-print-body .print-header {
        break-after: avoid;
        page-break-after: avoid;
    }

    .uncollected-detail-print-body .company-section,
    .uncollected-detail-print-body .store-section,
    .uncollected-detail-print-body .uncollected-detail-print-table {
        break-before: auto;
        break-inside: auto;
        page-break-before: auto;
        page-break-inside: auto;
    }

    .uncollected-detail-print-page {
        width: auto;
        min-height: auto;
        margin: 0;
        padding: 0;
    }
} */
</style>

<body>
    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    <main class="print-page landscape">
        <header class="print-header">
            <!-- <div></div> -->
            <h1>保険請求個別未収入金</h1>
            <div class="print-meta12">印刷月 {{ $paymentMonth ?? '' }}月迄／ {{ now()->format('Y/m/d H:i') }} 現在</div>
        </header>

        @forelse (collect($printRows ?? [])->groupBy('company_name') as $companyName => $companyRows)
        <section class="company-section uncollected-detail-company-section">
            <h2>{{ $companyName }}</h2>

            @foreach ($companyRows->groupBy('store_category') as $storeCategory => $storeRows)
            <section class="store-section uncollected-detail-store-section">
                <div class="store-title">
                    <span>{{ $storeCategory }}</span>
                    <span>未収合計 {{ number_format((float) $storeRows->sum('remaining_amount')) }}</span>
                </div>

                <table class="print-table">
                    <colgroup>
                        <col style="width: 60px;">
                        <col style="width: 70px;">
                        <col style="width: 130px;">
                        <col style="width: 40px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 130px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>施術月</th>
                            <th>保険者No</th>
                            <th>保険者</th>
                            <th>区分</th>
                            <th>合計金額</th>
                            <th>一部負担金</th>
                            <th>請求金額</th>
                            <th>現金回収</th>
                            <th>修正額</th>
                            <th>返戻額</th>
                            <th>入金額</th>
                            <th>備考</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($storeRows as $row)
                        <tr>
                            <td>{{ $row['treatment_month'] }}</td>
                            <td>{{ $row['insurer_number'] }}</td>
                            <td>{{ $row['insurer_name'] }}</td>
                            <td>{{ $row['summary_category'] }}</td>
                            <td class="number">{{ $row['total_amount'] }}</td>
                            <td class="number">{{ $row['copayment_amount'] }}</td>
                            <td class="number">{{ $row['claim_amount'] }}</td>
                            <td class="number">{{ $row['cash_collected_amount'] }}</td>
                            <td class="number">{{ $row['adjustment_amount'] }}</td>
                            <td class="number">{{ $row['returned_amount'] }}</td>
                            <td class="number">{{ $row['payment_amount'] }}</td>
                            <td>{{ $row['remarks'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
            @endforeach
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
