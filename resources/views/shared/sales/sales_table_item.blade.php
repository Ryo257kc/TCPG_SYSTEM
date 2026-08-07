<div class="sales-table-card">
    <div class="sales-table-head">
        <h2 class="panel-title">部門別売上</h2>
        <div class="sales-head-meta">
            <div class="meta-count">{{ count($salesRows ?? []) }}件</div>
            <div class="sales-grand-total">{{ number_format((float) ($grandTotal ?? 0)) }}円</div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>部門</th>
                    <th>保険請求額</th>
                    <th>保険窓口負担</th>
                    <th>個人振込分</th>
                    <th>自費</th>
                    <th>合計金</th>
                    <th>店舗経費</th>
                    <th>銀行入金額</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($salesRows ?? []) as $row)
                <tr>
                    <td>{{ $row['department_name'] }}</td>
                    <td class="num">{{ number_format((float) $row['insurance_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['counter_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['personal_transfer_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['self_pay_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['total_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['expense_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['bank_deposit_amount']) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="sales-empty">対象データを選んで表示してください。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (!empty($companyTotals ?? []))
    <div class="sales-table-head sales-company-total-head">
        <h2 class="panel-title">会社別合計</h2>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>会社</th>
                    <th>保険請求額</th>
                    <th>保険窓口負担</th>
                    <th>個人振込分</th>
                    <th>自費</th>
                    <th>合計金</th>
                    <th>店舗経費</th>
                    <th>銀行入金額</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($companyTotals ?? []) as $row)
                <tr>
                    <td>{{ $row['company_name'] }}</td>
                    <td class="num">{{ number_format((float) $row['insurance_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['counter_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['personal_transfer_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['self_pay_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['total_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['expense_amount']) }}</td>
                    <td class="num">{{ number_format((float) $row['bank_deposit_amount']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>