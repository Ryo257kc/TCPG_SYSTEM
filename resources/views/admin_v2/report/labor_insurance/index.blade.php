<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 年度更新確認</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/report.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/labor_insurance.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 年度更新確認</div>
    </div>

    <section class="panel report-main-panel">
        <section class="panel filter-panel">
            <form class="report-filter-grid" method="get" action="{{ route('admin.report.labor-insurance.index') }}">
                <label class="field">
                    <span>年度</span>
                    <select name="year">
                        @foreach ($availableYears as $year)
                            <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}年度</option>
                        @endforeach
                    </select>
                </label>

                <label class="field field-wide">
                    <span>会社</span>
                    <select name="company">
                        <option value="">すべて</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company }}" @selected($selectedCompany === $company)>{{ $company }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="filter-actions">
                    <button class="btn btn-primary" type="submit">表示</button>
                    <a
                        class="btn btn-secondary"
                        href="{{ route('admin.report.labor-insurance.print', ['year' => $selectedYear, 'company' => $selectedCompany]) }}"
                        target="_blank"
                        rel="noopener"
                    >
                        印刷用
                    </a>
                    @if ($selectedCompany !== '')
                        <a
                            class="btn btn-secondary"
                            href="{{ route('admin.report.labor-insurance.excel', ['year' => $selectedYear, 'company' => $selectedCompany]) }}"
                        >
                            Excel出力
                        </a>
                    @endif
                </div>
            </form>
        </section>


        <section class="labor-layout">
            <article class="panel summary-panel">
                <div class="summary-head">
                    <h2>基本情報</h2>
                    <p>{{ $report['filters']['period_label'] }}</p>
                </div>
                <dl class="summary-list">
                    <div>
                        <dt>年度</dt>
                        <dd>{{ $report['filters']['fiscal_year'] }}年度</dd>
                    </div>
                    <div>
                        <dt>会社</dt>
                        <dd>{{ $report['filters']['company_name'] !== '' ? $report['filters']['company_name'] : 'すべて' }}</dd>
                    </div>
                    <div>
                        <dt>表示期間</dt>
                        <dd>{{ $report['filters']['period_label'] }}</dd>
                    </div>
                    <div>
                        <dt>表示月数</dt>
                        <dd>{{ count($report['monthly_rows']) }}月</dd>
                    </div>
                </dl>
            </article>

            <article class="panel summary-panel">
                <div class="summary-head">
                    <h2>提出用サマリー</h2>
                    <p>申告書へ転記する主な項目を見やすく確認できます。</p>
                </div>
                <dl class="summary-list compact-list">
                    <div>
                        <dt>(4) 左側金額</dt>
                        <dd>{{ number_format((float) $report['yearly_totals']['left_total_amount']) }}</dd>
                    </div>
                    <div>
                        <dt>(7) 右側金額</dt>
                        <dd>{{ number_format((float) $report['yearly_totals']['right_total_amount']) }}</dd>
                    </div>
                    <div>
                        <dt>(9) 合計人数</dt>
                        <dd>{{ number_format((int) $report['report_totals']['workers_total']) }}</dd>
                    </div>
                    <div>
                        <dt>(10) 合計賃金</dt>
                        <dd>{{ number_format((float) $report['report_totals']['wages_total']) }}</dd>
                    </div>
                    <div>
                        <dt>(11) 霟用保険人数</dt>
                        <dd>{{ number_format((int) $report['report_totals']['employment_workers']) }}</dd>
                    </div>
                    <div>
                        <dt>(12) 霟用保険賃金</dt>
                        <dd>{{ number_format((float) $report['report_totals']['employment_wages_total']) }}</dd>
                    </div>
                </dl>
            </article>
        </section>



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





        <section class="panel table-panel">
            <div class="table-head">
                <div>
                    <h2>現在の計算ルール</h2>
                    <p>いま画面に表示している数値が、どの条件で集計されているかをそのまま確認できます。</p>
                </div>
            </div>

            <div class="formula-grid">
                <article class="formula-card">
                    <h3>人数の集計</h3>
                    <ul class="formula-list">
                        <li>(1) 常用: 雇用保険加入あり かつ 兼務役員ではない</li>
                        <li>(2) 役員: <code>staff_division = 兼務役員</code></li>
                        <li>(3) 臨時: 雇用保険人数ではなく、在籍者ベースではない</li>
                        <li>(5) 常用: (1) と同じ集計条件</li>
                        <li>(6) 役員: 兼務役員 かつ 雇用保険加入あり</li>
                        <li>未設定の雇用区分 / 在籍情報は確認対象外</li>
                    </ul>
                </article>

                <article class="formula-card">
                    <h3>賃金と人数</h3>
                    <ul class="formula-list">
                        <li>賃金元: <code>mx_kyuyo_shou.rouho_target_sum</code></li>
                        <li>左側: 労働保険 / 一般拠出金対象の賃金合計</li>
                        <li>右側: 雇用保険対象の賃金合計</li>
                        <li>賞与月も賃金集計のみ対象で、人数はカウントしません</li>
                        <li>月の合計人数: 表示中の各月人数をそのまま合算</li>
                        <li>提出用人数: service 側の合計人数集計</li>
                    </ul>
                </article>
            </div>
        </section>
    </section>

    @include('admin_v2.report.labor_insurance.popup_script')
</div>
</body>
</html>
