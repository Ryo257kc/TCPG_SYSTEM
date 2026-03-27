<section class="panel table-panel">
    @php
        $countableRows = collect($report['monthly_rows'])->filter(fn ($row) => empty($row['is_bonus']));
        $displayLeftRegularCount = $countableRows->sum(fn ($row) => (int) ($row['left_regular_count'] ?? 0));
        $displayLeftExecutiveCount = $countableRows->sum(fn ($row) => (int) ($row['left_executive_count'] ?? 0));
        $displayLeftTemporaryCount = $countableRows->sum(fn ($row) => (int) ($row['left_temporary_count'] ?? 0));
        $displayRightRegularCount = $countableRows->sum(fn ($row) => (int) ($row['right_regular_count'] ?? 0));
        $displayRightExecutiveCount = $countableRows->sum(fn ($row) => (int) ($row['right_executive_count'] ?? 0));
        $leftTotalCount = $countableRows->sum(fn ($row) => (int) (($row['left_regular_count'] ?? 0) + ($row['left_executive_count'] ?? 0) + ($row['left_temporary_count'] ?? 0)));
        $rightTotalCount = $countableRows->sum(fn ($row) => (int) (($row['right_regular_count'] ?? 0) + ($row['right_executive_count'] ?? 0)));
        $workersTotalDisplay = rtrim(rtrim(number_format((float) ($report['report_totals']['workers_total'] ?? 0), 2, '.', ''), '0'), '.');
        $employmentWorkersDisplay = rtrim(rtrim(number_format((float) ($report['report_totals']['employment_workers'] ?? 0), 2, '.', ''), '0'), '.');
    @endphp

    <div class="table-head">
        <div>
            <h2>年度更新集計表</h2>
            <p>月別の支払実績と申告書転記用の主な数値を確認できます。</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="report-table labor-table">
            <thead>
            <tr>
                <th rowspan="2">対象月</th>
                <th colspan="4">労災保険および一般拠出金算出基礎賃金等</th>
                <th colspan="3">雇用保険</th>
            </tr>
            <tr>
                <th>(1) 一般</th>
                <th>(2) 役員</th>
                <th>(3) 臨時</th>
                <th>(4) 計</th>
                <th>(5) 一般</th>
                <th>(6) 役員</th>
                <th>(7) 計</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($report['monthly_rows'] as $row)
                @php
                    $monthKey = (string) ($row['month_key'] ?? '');
                    $rowType = !empty($row['is_bonus']) ? 'bonus' : 'regular';
                    $detailKey = static fn (string $bucket): string => implode('|', [$monthKey, $rowType, $bucket]);
                @endphp
                <tr class="{{ !empty($row['is_bonus']) ? 'is-bonus' : '' }}">
                    <th>{{ $row['label'] }}</th>
                    <td>
                        <div class="cell-pair">
                            <span class="amount">
                                @if ((float) ($row['left_regular_amount'] ?? 0) > 0)
                                    <button class="amount-link amount-popover-trigger" type="button" data-detail-key="{{ $detailKey('left_regular') }}">{{ number_format((float) ($row['left_regular_amount'] ?? 0)) }}</button>
                                @else
                                    {{ number_format((float) ($row['left_regular_amount'] ?? 0)) }}
                                @endif
                            </span>
                            <span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) ($row['left_regular_count'] ?? 0)) . '人' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="cell-pair">
                            <span class="amount">
                                @if ((float) ($row['left_executive_amount'] ?? 0) > 0)
                                    <button class="amount-link amount-popover-trigger" type="button" data-detail-key="{{ $detailKey('left_executive') }}">{{ number_format((float) ($row['left_executive_amount'] ?? 0)) }}</button>
                                @else
                                    {{ number_format((float) ($row['left_executive_amount'] ?? 0)) }}
                                @endif
                            </span>
                            <span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) ($row['left_executive_count'] ?? 0)) . '人' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="cell-pair">
                            <span class="amount">
                                @if ((float) ($row['left_temporary_amount'] ?? 0) > 0)
                                    <button class="amount-link amount-popover-trigger" type="button" data-detail-key="{{ $detailKey('left_temporary') }}">{{ number_format((float) ($row['left_temporary_amount'] ?? 0)) }}</button>
                                @else
                                    {{ number_format((float) ($row['left_temporary_amount'] ?? 0)) }}
                                @endif
                            </span>
                            <span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) ($row['left_temporary_count'] ?? 0)) . '人' }}</span>
                        </div>
                    </td>
                    <td class="total-cell">
                        <div class="cell-pair">
                            <span class="amount">{{ number_format((float) ($row['left_total_amount'] ?? 0)) }}</span>
                            <span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) (($row['left_regular_count'] ?? 0) + ($row['left_executive_count'] ?? 0) + ($row['left_temporary_count'] ?? 0))) . '人' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="cell-pair">
                            <span class="amount">
                                @if ((float) ($row['right_regular_amount'] ?? 0) > 0)
                                    <button class="amount-link amount-popover-trigger" type="button" data-detail-key="{{ $detailKey('right_regular') }}">{{ number_format((float) ($row['right_regular_amount'] ?? 0)) }}</button>
                                @else
                                    {{ number_format((float) ($row['right_regular_amount'] ?? 0)) }}
                                @endif
                            </span>
                            <span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) ($row['right_regular_count'] ?? 0)) . '人' }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="cell-pair">
                            <span class="amount">
                                @if ((float) ($row['right_executive_amount'] ?? 0) > 0)
                                    <button class="amount-link amount-popover-trigger" type="button" data-detail-key="{{ $detailKey('right_executive') }}">{{ number_format((float) ($row['right_executive_amount'] ?? 0)) }}</button>
                                @else
                                    {{ number_format((float) ($row['right_executive_amount'] ?? 0)) }}
                                @endif
                            </span>
                            <span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) ($row['right_executive_count'] ?? 0)) . '人' }}</span>
                        </div>
                    </td>
                    <td class="total-cell">
                        <div class="cell-pair">
                            <span class="amount">{{ number_format((float) ($row['right_total_amount'] ?? 0)) }}</span>
                            <span class="count">{{ !empty($row['is_bonus']) ? '-' : number_format((int) (($row['right_regular_count'] ?? 0) + ($row['right_executive_count'] ?? 0))) . '人' }}</span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-cell">対象データがありません。</td>
                </tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr>
                <th>年合計</th>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) ($report['yearly_totals']['left_regular_amount'] ?? 0)) }}</span><span class="count">{{ number_format($displayLeftRegularCount) }}人</span></div></td>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) ($report['yearly_totals']['left_executive_amount'] ?? 0)) }}</span><span class="count">{{ number_format($displayLeftExecutiveCount) }}人</span></div></td>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) ($report['yearly_totals']['left_temporary_amount'] ?? 0)) }}</span><span class="count">{{ number_format($displayLeftTemporaryCount) }}人</span></div></td>
                <td class="total-cell"><div class="cell-pair"><span class="amount">{{ number_format((float) ($report['yearly_totals']['left_total_amount'] ?? 0)) }}</span><span class="count">{{ number_format($leftTotalCount) }}人</span></div></td>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) ($report['yearly_totals']['right_regular_amount'] ?? 0)) }}</span><span class="count">{{ number_format($displayRightRegularCount) }}人</span></div></td>
                <td><div class="cell-pair"><span class="amount">{{ number_format((float) ($report['yearly_totals']['right_executive_amount'] ?? 0)) }}</span><span class="count">{{ number_format($displayRightExecutiveCount) }}人</span></div></td>
                <td class="total-cell"><div class="cell-pair"><span class="amount">{{ number_format((float) ($report['yearly_totals']['right_total_amount'] ?? 0)) }}</span><span class="count">{{ number_format($rightTotalCount) }}人</span></div></td>
            </tr>
            </tfoot>
        </table>
    </div>

    <div class="amount-popover" id="laborInsuranceAmountPopover" hidden>
        <div class="amount-popover__head">
            <strong class="amount-popover__title"></strong>
            <button class="amount-popover__close" type="button" aria-label="閉じる">×</button>
        </div>
        <div class="amount-popover__meta"></div>
        <div class="amount-popover__body"></div>
    </div>

    <div class="transfer-grid">
        <article class="transfer-card">
            <h3>申告書転記用</h3>
            <dl>
                <div>
                    <dt>(9) 労働者数 <span class="formula-inline">{{ number_format((int) ($report['report_totals']['workers_source_total'] ?? 0)) }}人 / 12 =</span></dt>
                    <dd>{{ $workersTotalDisplay }}</dd>
                </div>
                <div><dt>(10) 賃金等総額</dt><dd>{{ number_format((float) ($report['report_totals']['wages_total'] ?? 0)) }}</dd></div>
                <div><dt>(10) 千円未満切捨</dt><dd>{{ number_format((float) ($report['report_totals']['wages_total_truncated'] ?? 0)) }}</dd></div>
            </dl>
        </article>
        <article class="transfer-card">
            <h3>雇用保険転記用</h3>
            <dl>
                <div>
                    <dt>(11) 被保険者数 <span class="formula-inline">{{ number_format((int) ($report['report_totals']['employment_workers_source_total'] ?? 0)) }}人 / 12 =</span></dt>
                    <dd>{{ $employmentWorkersDisplay }}</dd>
                </div>
                <div><dt>(12) 賃金等総額</dt><dd>{{ number_format((float) ($report['report_totals']['employment_wages_total'] ?? 0)) }}</dd></div>
                <div><dt>(12) 千円未満切捨</dt><dd>{{ number_format((float) ($report['report_totals']['employment_wages_total_truncated'] ?? 0)) }}</dd></div>
            </dl>
        </article>
    </div>
</section>
