<div class="sales-table-card">
    <div class="sales-table-head">
        <h2 class="panel-title">部署別売上</h2>
        <div class="sales-head-meta">
            <div class="meta-count">{{ count($salesRows ?? []) }}件</div>
            <div class="sales-grand-total">{{ number_format((float) ($grandTotal ?? 0)) }}</div>
        </div>
    </div>

    <div class="sales-table-wrap">
        <table class="sales-table">
            <thead>
                <tr>
                    <th>部署</th>
                    <th>店舗名</th>
                    <th>保険請求額</th>
                    <th>窓口収入</th>
                    <th>自費</th>
                    <th>合計金</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($salesRows ?? []) as $row)
                    <tr>
                        <td>{{ $row['department_name'] }}</td>
                        <td>{{ $row['store_name'] }}</td>
                        <td>{{ number_format((float) $row['insurance_amount']) }}</td>
                        <td>{{ number_format((float) $row['counter_amount']) }}</td>
                        <td>{{ number_format((float) $row['self_pay_amount']) }}</td>
                        <td>{{ number_format((float) $row['total_amount']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="sales-empty">対象データを選んで表示してください。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
