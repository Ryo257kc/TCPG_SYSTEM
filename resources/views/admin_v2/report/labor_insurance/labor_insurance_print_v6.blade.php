<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 年度更新 印刷用</title>
    @include('admin_v2.report.labor_insurance.print_style')
</head>

<body>
    @php
    $countableRows = collect($report['monthly_rows'])->filter(fn ($row) => empty($row['is_bonus']));
    $displayLeftRegularCount = $countableRows->sum(fn ($row) => (int) ($row['left_regular_count'] ?? 0));
    $displayLeftExecutiveCount = $countableRows->sum(fn ($row) => (int) ($row['left_executive_count'] ?? 0));
    $displayLeftTemporaryCount = $countableRows->sum(fn ($row) => (int) ($row['left_temporary_count'] ?? 0));
    $displayRightRegularCount = $countableRows->sum(fn ($row) => (int) ($row['right_regular_count'] ?? 0));
    $displayRightExecutiveCount = $countableRows->sum(fn ($row) => (int) ($row['right_executive_count'] ?? 0));
    $leftTotalCount = $countableRows->sum(fn ($row) => (int) (($row['left_regular_count'] ?? 0) + ($row['left_executive_count'] ?? 0) + ($row['left_temporary_count'] ?? 0)));
    $rightTotalCount = $countableRows->sum(fn ($row) => (int) (($row['right_regular_count'] ?? 0) + ($row['right_executive_count'] ?? 0)));
    $meta = $report['company_meta'] ?? [];
    $labor = $meta['labor_parts'] ?? [];
    @endphp

    <div class="print-page">
        <header class="sheet-header">
            <div class="sheet-title">
                <h1>令和{{ $selectedYear - 2018 }}年度　確定保険料・一般拠出金算定基礎賃金集計表</h1>
                <p>（算定期間 令和{{ $selectedYear - 2018 }}年4月 〜 令和{{ $selectedYear - 2017 }}年3月）</p>
            </div>
            <div class="sheet-note">※ 概算・確定保険料・一般拠出金申告書（事業主控）と一緒に保管してください</div>
        </header>

        <section class="sheet-top">
            <div class="box labor-no-box">
                <div class="box-title">労働保険番号</div>
                <div class="labor-no-grid">
                    <div class="part"><span>府県</span><strong>{{ $labor['prefecture'] ?? '' }}</strong></div>
                    <div class="part"><span>所掌</span><strong>{{ $labor['jurisdiction'] ?? '' }}</strong></div>
                    <div class="part"><span>管轄</span><strong>{{ $labor['office'] ?? '' }}</strong></div>
                    <div class="part wide"><span>基幹番号</span><strong>{{ $labor['base'] ?? '' }}</strong></div>
                    <div class="part wide"><span>枝番号</span><strong>{{ $labor['branch'] ?? '' }}</strong></div>
                </div>
            </div>

            <div class="box dispatch-box">
                <div class="dispatch-row dispatch-title"><span>出納者の有無</span></div>
                <div class="dispatch-row"><span>受</span><strong></strong><em>名</em></div>
                <div class="dispatch-row"><span>出</span><strong></strong><em>名</em></div>
            </div>

            <div class="box company-box">
                <div class="company-grid">
                    <div class="field field-name">
                        <span>事業の名称</span>
                        <strong>{{ $meta['company_name'] ?? '' }}</strong>
                    </div>
                    <div class="field field-tel">
                        <span>電話</span>
                        <strong>{{ $meta['tel'] ?? '' }}</strong>
                    </div>
                    <div class="field field-work">
                        <span>労保適用区分</span>
                        <strong>{{ $meta['labor_install_category'] ?? '' }}</strong>
                    </div>
                    <div class="field field-address">
                        <span>事業所の所在地</span>
                        <strong>{{ $meta['company_address'] ?? '' }}</strong>
                    </div>
                    <div class="field field-post">
                        <span>郵便番号</span>
                        <strong></strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="sheet-main">
            <table class="sheet-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-label">月</th>
                        <th colspan="4">労災保険および一般拠出金（対象者数及び賃金）</th>
                        <th colspan="3">雇用保険（対象者数及び賃金）</th>
                    </tr>
                    <tr>
                        <th>(1)常用労働者</th>
                        <th>(2)役員で労働者の人</th>
                        <th>(3)臨時労働者</th>
                        <th>(4)合計 ((1)+(2)+(3))</th>
                        <th>(5)常用労働者</th>
                        <th>(6)役員で雇用保険の資格のある人</th>
                        <th>(7)合計 ((5)+(6))</th>
                    </tr>
                    <tr class="subhead">
                        <th></th>
                        <th><span>(人)</span><span>(円)</span></th>
                        <th><span>(人)</span><span>(円)</span></th>
                        <th><span>(人)</span><span>(円)</span></th>
                        <th><span>(人)</span><span>(円)</span></th>
                        <th><span>(人)</span><span>(円)</span></th>
                        <th><span>(人)</span><span>(円)</span></th>
                        <th><span>(人)</span><span>(円)</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['monthly_rows'] as $row)
                    <tr class="{{ !empty($row['is_bonus']) ? 'is-bonus' : '' }}">
                        <th>{{ $row['label'] }}</th>
                        <td><span class="people">{{ !empty($row['is_bonus']) ? '' : (int) $row['left_regular_count'] }}</span><span class="yen">{{ number_format((float) $row['left_regular_amount']) }}</span></td>
                        <td><span class="people">{{ !empty($row['is_bonus']) ? '' : (int) $row['left_executive_count'] }}</span><span class="yen">{{ number_format((float) $row['left_executive_amount']) }}</span></td>
                        <td><span class="people">{{ !empty($row['is_bonus']) ? '' : (int) $row['left_temporary_count'] }}</span><span class="yen">{{ number_format((float) $row['left_temporary_amount']) }}</span></td>
                        <td class="sum-cell"><span class="people">{{ !empty($row['is_bonus']) ? '' : (int) (($row['left_regular_count'] ?? 0) + ($row['left_executive_count'] ?? 0) + ($row['left_temporary_count'] ?? 0)) }}</span><span class="yen">{{ number_format((float) $row['left_total_amount']) }}</span></td>
                        <td><span class="people">{{ !empty($row['is_bonus']) ? '' : (int) $row['right_regular_count'] }}</span><span class="yen">{{ number_format((float) $row['right_regular_amount']) }}</span></td>
                        <td><span class="people">{{ !empty($row['is_bonus']) ? '' : (int) $row['right_executive_count'] }}</span><span class="yen">{{ number_format((float) $row['right_executive_amount']) }}</span></td>
                        <td class="sum-cell"><span class="people">{{ !empty($row['is_bonus']) ? '' : (int) (($row['right_regular_count'] ?? 0) + ($row['right_executive_count'] ?? 0)) }}</span><span class="yen">{{ number_format((float) $row['right_total_amount']) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>合計</th>
                        <td><span class="people">{{ $displayLeftRegularCount }}</span><span class="yen">{{ number_format((float) $report['yearly_totals']['left_regular_amount']) }}</span></td>
                        <td><span class="people">{{ $displayLeftExecutiveCount }}</span><span class="yen">{{ number_format((float) $report['yearly_totals']['left_executive_amount']) }}</span></td>
                        <td><span class="people">{{ $displayLeftTemporaryCount }}</span><span class="yen">{{ number_format((float) $report['yearly_totals']['left_temporary_amount']) }}</span></td>
                        <td class="sum-cell"><span class="people">{{ $leftTotalCount }}</span><span class="yen">{{ number_format((float) $report['yearly_totals']['left_total_amount']) }}</span></td>
                        <td><span class="people">{{ $displayRightRegularCount }}</span><span class="yen">{{ number_format((float) $report['yearly_totals']['right_regular_amount']) }}</span></td>
                        <td><span class="people">{{ $displayRightExecutiveCount }}</span><span class="yen">{{ number_format((float) $report['yearly_totals']['right_executive_amount']) }}</span></td>
                        <td class="sum-cell"><span class="people">{{ $rightTotalCount }}</span><span class="yen">{{ number_format((float) $report['yearly_totals']['right_total_amount']) }}</span></td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <section class="sheet-footer">
            <div class="footer-col footer-col-left">
                <div class="mini-box b-note-box">
                    <div class="b-note-text">
                        B　船員保険の被保険者、建設の事業において有期事業の一括の事業である事業の労働者数を除く、1日の平均使用労働者数を記入してください。
                    </div>
                </div>

                <div class="formula-box large">
                    <div class="label">(9)の合計人数</div>
                    <div class="formula">{{ number_format((int) ($report['report_totals']['workers_source_total'] ?? 0)) }}</div>
                    <div class="operator">÷12=</div>
                    <div class="result">{{ rtrim(rtrim(number_format((float) ($report['report_totals']['workers_total'] ?? 0), 2, '.', ''), '0'), '.') }}</div>
                    <div class="suffix">人</div>
                    <div class="transfer-label">申告書⑨欄へ転記</div>
                </div>

                <div class="executive-box">
                    <div class="executive-title">役員で労働者扱いの詳細</div>
                    <table class="executive-table">
                        <thead>
                            <tr>
                                <th>備考</th>
                                <th>氏名</th>
                                <th>役職</th>
                                <th>雇用保険の資格</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($report['executive_worker_summary'] ?? []) as $row)
                            <tr>
                                <td></td>
                                <td>{{ $row['staff_name'] }}</td>
                                <td>{{ $row['role_label'] }}</td>
                                <td>{{ $row['employment_label'] }}</td>
                            </tr>
                            @empty
                            @for ($i = 0; $i < 4; $i++)
                                <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                </tr>
                                @endfor
                                @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="footer-col footer-col-middle">
                <div class="mini-box note-box wide-note">
                    <div class="mini-title">
                        ※ 各月賃金締切日等の労働者数の合計を記入し(11)の総合計人数を12で除し小数点以下切り捨てた月平均人数を記入してください。切り捨てた結果、0人となる場合は1人としてください。
                    </div>
                </div>

                <div class="mini-box note-box secondary-note">
                    <div class="mini-title">
                        また、年度途中で保険関係が成立した事業については、保険関係成立以降の月数で除してください。
                    </div>
                </div>

                <div class="formula-box small">
                    <div class="label">(11)の合計人数</div>
                    <div class="formula">{{ number_format((int) ($report['report_totals']['employment_workers_source_total'] ?? 0)) }}</div>
                    <div class="operator">÷12=</div>
                    <div class="result">{{ rtrim(rtrim(number_format((float) ($report['report_totals']['employment_workers'] ?? 0), 2, '.', ''), '0'), '.') }}</div>
                    <div class="suffix">人</div>
                    <div class="transfer-label">申告書⑪欄へ転記</div>
                </div>
            </div>

            <div class="footer-col footer-col-right">
                <table class="transfer-table">
                    <tr>
                        <th>労災保険対象賃金</th>
                        <td>(10)の合計額の千円未満を切り捨てた額</td>
                        <td>{{ number_format((float) ($report['report_totals']['wages_total_truncated'] ?? 0) / 1000) }}</td>
                        <td>千円</td>
                        <td>申告書⑧欄（ロ）へ転記</td>
                    </tr>
                    <tr>
                        <th>雇用保険対象賃金</th>
                        <td>(12)の合計額の千円未満を切り捨てた額</td>
                        <td>{{ number_format((float) ($report['report_totals']['employment_wages_total_truncated'] ?? 0) / 1000) }}</td>
                        <td>千円</td>
                        <td>申告書⑬欄（ホ）へ転記</td>
                    </tr>
                    <tr>
                        <th>一般拠出金</th>
                        <td>(10)の合計額の千円未満を切り捨てた額</td>
                        <td>{{ number_format((float) ($report['report_totals']['wages_total_truncated'] ?? 0) / 1000) }}</td>
                        <td>千円</td>
                        <td>申告書⑯欄（ヘ）へ転記</td>
                    </tr>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
