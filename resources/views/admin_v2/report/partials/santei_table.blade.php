<section class="panel santei-table-panel">
    <div class="santei-head">
        <div>
            <h2>確認一覧</h2>
            <p>{{ $selectedYear }}年 4月〜6月の通常給与をまとめています。</p>
        </div>
        <div class="santei-meta">{{ count($rows) }}件</div>
    </div>

    <div class="table-wrap">
        <table class="santei-table">
            <thead>
            <tr>
                <th rowspan="2">整理番号</th>
                <th rowspan="2">氏名</th>
                <th rowspan="2">生年月日</th>
                <th rowspan="2">事業所</th>
                <th colspan="2">4月</th>
                <th colspan="2">5月</th>
                <th colspan="2">6月</th>
                <th rowspan="2">平均額</th>
                <th rowspan="2">健保標準<br>報酬月額</th>
                <th rowspan="2">厚保標準<br>報酬月額</th>
            </tr>
            <tr>
                <th>日数</th>
                <th>社保対象額</th>
                <th>日数</th>
                <th>社保対象額</th>
                <th>日数</th>
                <th>社保対象額</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['syaho_seiri_num_padded'] }}</td>
                    <td>
                        <div>{{ $row['staff_name'] }}</div>
                        <div class="sub">{{ $row['staff_id'] }} / {{ $row['staff_division'] }}</div>
                    </td>
                    <td>{{ $row['birthday_wareki'] !== '' ? $row['birthday_wareki'] : '-' }}</td>
                    <td>
                        <div>{{ $row['company_name_formatted'] !== '' ? $row['company_name_formatted'] : '-' }}</div>
                        <div class="sub">{{ $row['store_name'] }}</div>
                    </td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) ($row['months'][4]['basis_days'] ?? 0), 1), '0'), '.') }}</td>
                    <td class="num">{{ number_format((float) ($row['months'][4]['syaho_target_sum'] ?? 0)) }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) ($row['months'][5]['basis_days'] ?? 0), 1), '0'), '.') }}</td>
                    <td class="num">{{ number_format((float) ($row['months'][5]['syaho_target_sum'] ?? 0)) }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) ($row['months'][6]['basis_days'] ?? 0), 1), '0'), '.') }}</td>
                    <td class="num">{{ number_format((float) ($row['months'][6]['syaho_target_sum'] ?? 0)) }}</td>
                    <td class="num">{{ number_format((float) ($row['average_syaho_target'] ?? 0)) }}</td>
                    <td class="num">{{ number_format((float) ($row['shaho_info']['kenpo_monthly_amo'] ?? 0)) }}</td>
                    <td class="num">{{ number_format((float) ($row['shaho_info']['kounen_monthly_amo'] ?? 0)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="empty-cell">対象データがありません。</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
