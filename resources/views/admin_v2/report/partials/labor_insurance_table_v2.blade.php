<section class="panel table-panel">
    @php
        $countableRows = collect($report['monthly_rows'])->filter(fn ($row) => empty($row['is_bonus']));
        $displayLeftRegularCount = $countableRows->sum(fn ($row) => (int) ($row['left_regular_count'] ?? 0));
        $displayLeftExecutiveCount = $countableRows->sum(fn ($row) => (int) ($row['left_executive_count'] ?? 0));
        $displayLeftTemporaryCount = $countableRows->sum(fn ($row) => (int) ($row['left_temporary_count'] ?? 0));
        $displayRightRegularCount = $countableRows->sum(fn ($row) => (int) ($row['right_regular_count'] ?? 0));
        $displayRightExecutiveCount = $countableRows->sum(fn ($row) => (int) ($row['right_executive_count'] ?? 0));
        $displayLeftTotalCount = $displayLeftRegularCount + $displayLeftExecutiveCount + $displayLeftTemporaryCount;
        $displayRightTotalCount = $displayRightRegularCount + $displayRightExecutiveCount;
    @endphp

    <div class="table-head">
        <div>
            <h2>年度更新集計表</h2>
            <p>月別の労保対象賃金と人数を見ながら、申告書へ転記する金額を確認できます。</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="report-table labor-table">
            <thead>
            <tr>
                <th rowspan="2">対象月</th>
                <th colspan="4">労災保険および一般拠出金</th>
                <th colspan="3">雇用保険</th>
            </tr>
            <tr>
                <th>(1) 常用</th>
                <th>(2) 兼務役員</th>
                <th>(3) 臨時</th>
                <th>(4) 合計</th>
                <th>(5) 常用</th>
                <th>(6) 兼務役員</th>
                <th>(7) 合計</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($report['monthly_rows'] as $row)
                <tr class="{{ !empty($row['is_bonus']) ? 'is-bonus' : '' }}">
                    <th>{{ $row['label'] }}</th>
                    <td><div class="cell-pair"><span class="amount">{{ number_format((float) $row['left_regular_amount']) }}</span><span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) $row['left_regular_count']) . '人' }}</span></div></td>
                    <td><div class="cell-pair"><span class="amount">{{ number_format((float) $row['left_executive_amount']) }}</span><span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) $row['left_executive_count']) . '人' }}</span></div></td>
                    <td><div class="cell-pair"><span class="amount">{{ number_format((float) $row['left_temporary_amount']) }}</span><span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) $row['left_temporary_count']) . '人' }}</span></div></td>
                    <td class="total-cell"><div class="cell-pair"><span class="amount">{{ number_format((float) $row['left_total_amount']) }}</span><span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) ($row['left_regular_count'] + $row['left_executive_count'] + $row['left_temporary_count'])) . '人' }}</span></div></td>
                    <td><div class="cell-pair"><span class="amount">{{ number_format((float) $row['right_regular_amount']) }}</span><span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) $row['right_regular_count']) . '人' }}</span></div></td>
                    <td><div class="cell-pair"><span class="amount">{{ number_format((float) $row['right_executive_amount']) }}</span><span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) $row['right_executive_count']) . '人' }}</span></div></td>
                    <td class="total-cell"><div class="cell-pair"><span class="amount">{{ number_format((float) $row['right_total_amount']) }}</span><span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) ($row['right_regular_count'] + $row['right_executive_count'])) . '人' }}</span></div></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-cell">集計対象のデータがありません。</td>
                </tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr>
                <th>年合計</th>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) $report['yearly_totals']['left_regular_amount']) }}</span><span class="count">{{ number_format($displayLeftRegularCount) }}人</span></div></td>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) $report['yearly_totals']['left_executive_amount']) }}</span><span class="count">{{ number_format($displayLeftExecutiveCount) }}人</span></div></td>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) $report['yearly_totals']['left_temporary_amount']) }}</span><span class="count">{{ number_format($displayLeftTemporaryCount) }}人</span></div></td>
                <td class="total-cell"><div class="cell-pair"><span class="amount">{{ number_format((float) $report['yearly_totals']['left_total_amount']) }}</span><span class="count">{{ number_format($displayLeftTotalCount) }}人</span></div></td>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) $report['yearly_totals']['right_regular_amount']) }}</span><span class="count">{{ number_format($displayRightRegularCount) }}人</span></div></td>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) $report['yearly_totals']['right_executive_amount']) }}</span><span class="count">{{ number_format($displayRightExecutiveCount) }}人</span></div></td>
                <td class="total-cell"><div class="cell-pair"><span class="amount">{{ number_format((float) $report['yearly_totals']['right_total_amount']) }}</span><span class="count">{{ number_format($displayRightTotalCount) }}人</span></div></td>
            </tr>
            </tfoot>
        </table>
    </div>

    <div class="transfer-grid">
        <article class="transfer-card">
            <h3>申告書転記欄</h3>
            <dl>
                <div><dt>(9) 合計人数</dt><dd>{{ number_format((int) $report['report_totals']['workers_total']) }}</dd></div>
                <div><dt>(10) 合計賃金</dt><dd>{{ number_format((float) $report['report_totals']['wages_total']) }}</dd></div>
                <div><dt>(10) 千円未満切捨</dt><dd>{{ number_format((float) $report['report_totals']['wages_total_truncated']) }}</dd></div>
            </dl>
        </article>
        <article class="transfer-card">
            <h3>雇用保険転記欄</h3>
            <dl>
                <div><dt>(11) 被保険者数</dt><dd>{{ number_format((int) $report['report_totals']['employment_workers']) }}</dd></div>
                <div><dt>(12) 雇用保険賃金</dt><dd>{{ number_format((float) $report['report_totals']['employment_wages_total']) }}</dd></div>
                <div><dt>(12) 千円未満切捨</dt><dd>{{ number_format((float) $report['report_totals']['employment_wages_total_truncated']) }}</dd></div>
            </dl>
        </article>
    </div>
</section>
