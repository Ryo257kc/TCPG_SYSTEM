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
                <dt>集計期間</dt>
                <dd>{{ $report['filters']['period_label'] }}</dd>
            </div>
            <div>
                <dt>表示行数</dt>
                <dd>{{ count($report['monthly_rows']) }}行</dd>
            </div>
        </dl>
    </article>

    <article class="panel summary-panel">
        <div class="summary-head">
            <h2>転記値サマリー</h2>
            <p>申告書へ転記する主要な値を先に確認できます。</p>
        </div>
        <dl class="summary-list compact-list">
            <div>
                <dt>(4) 左側合計</dt>
                <dd>{{ number_format((float) $report['yearly_totals']['left_total_amount']) }}</dd>
            </div>
            <div>
                <dt>(7) 右側合計</dt>
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
                <dt>(11) 被保険者数</dt>
                <dd>{{ number_format((int) $report['report_totals']['employment_workers']) }}</dd>
            </div>
            <div>
                <dt>(12) 雇用保険賃金</dt>
                <dd>{{ number_format((float) $report['report_totals']['employment_wages_total']) }}</dd>
            </div>
        </dl>
    </article>
</section>
