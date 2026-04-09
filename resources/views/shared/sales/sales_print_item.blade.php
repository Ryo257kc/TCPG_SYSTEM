@php
    $targetMonthText = (string) ($targetMonth ?? '');
    $warekiTitle = $targetMonthText;

    if (preg_match('/^(\d{4})-(\d{2})$/', $targetMonthText, $matches)) {
        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $warekiTitle = '令和' . ($year - 2018) . '年' . $month . '月';
    }
@endphp

<div class="page">
    <header class="page-header">
        <h1 class="page-title">{{ $warekiTitle }} 売上一覧</h1>
        <div class="page-meta">
            <span>{{ $companyName }}</span>
            <span>総売上 {{ number_format((float) ($grandTotal ?? 0)) }}</span>
        </div>
    </header>

    <div class="department-grid">
        @foreach (($stores ?? []) as $store)
            @foreach (($store['rows'] ?? []) as $row)
                <article class="department-card">
                    <div class="department-head">
                        <div class="store-name">{{ $store['store_name'] }}</div>
                        <div class="department-name">{{ $row['department_name'] }}</div>
                    </div>

                    <table class="summary-table">
                        <tbody>
                            <tr>
                                <td>一般</td>
                                <td>{{ number_format((float) $row['general_amount']) }}</td>
                            </tr>
                            <tr>
                                <td>後期高齢</td>
                                <td>{{ number_format((float) $row['late_elderly_amount']) }}</td>
                            </tr>
                            <tr>
                                <td>助成請求</td>
                                <td>{{ number_format((float) $row['aid_amount']) }}</td>
                            </tr>
                            <tr class="total-row">
                                <td>保険請求額</td>
                                <td>{{ number_format((float) $row['insurance_amount']) }}</td>
                            </tr>
                            <tr>
                                <td>窓口収入</td>
                                <td>{{ number_format((float) $row['counter_amount']) }}</td>
                            </tr>
                            <tr>
                                <td>個人振込</td>
                                <td>{{ number_format((float) $row['personal_transfer_amount']) }}</td>
                            </tr>
                            <tr>
                                <td>自費</td>
                                <td>{{ number_format((float) $row['self_pay_amount']) }}</td>
                            </tr>
                            <tr class="total-row grand-row">
                                <td>総売上</td>
                                <td>{{ number_format((float) $row['total_amount']) }}</td>
                            </tr>
                            <tr>
                                <td>店舗経費</td>
                                <td>{{ number_format((float) $row['expense_amount']) }}</td>
                            </tr>
                            <tr>
                                <td>銀行入金額</td>
                                <td>{{ number_format((float) $row['bank_deposit_amount']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </article>
            @endforeach
        @endforeach
    </div>
</div>
