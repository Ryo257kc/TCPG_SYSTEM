<section class="panel santei-table-panel">
    <div class="santei-head">
        <div>
            <h2>確認一覧</h2>
            <p>{{ $selectedYear }}年の賞与支払届CSVに載せる確認用データです。</p>
        </div>
        <div class="santei-meta">{{ count($rows) }}件</div>
    </div>

    <div class="table-wrap">
        <table class="santei-table bonus-payment-table">
            <thead>
            <tr>
                <th>整理</th>
                <th>氏名</th>
                <th>氏名カナ</th>
                <th>生年月日</th>
                <th>支給日</th>
                <th>会社</th>
                <th>店舗</th>
                <th>通貨額</th>
                <th>合計賞与額</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['syaho_seiri_num_padded'] }}</td>
                    <td>{{ $row['staff_name'] !== '' ? $row['staff_name'] : '-' }}</td>
                    <td>{{ $row['staff_name_furi'] !== '' ? $row['staff_name_furi'] : '-' }}</td>
                    <td>{{ $row['birthday_wareki'] !== '' ? $row['birthday_wareki'] : '-' }}</td>
                    <td>{{ $row['payment_date'] !== '' ? $row['payment_date'] : '-' }}</td>
                    <td>{{ $row['company_name'] !== '' ? $row['company_name'] : '-' }}</td>
                    <td>{{ $row['store_name'] !== '' ? $row['store_name'] : '-' }}</td>
                    <td class="num">{{ number_format((float) ($row['bonus_amo'] ?? 0)) }}</td>
                    <td class="num">{{ number_format((float) ($row['bonus_total_rounded'] ?? 0)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty-cell">対象データがありません。</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
