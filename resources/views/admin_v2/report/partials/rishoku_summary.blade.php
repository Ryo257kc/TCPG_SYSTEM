<section class="rishoku-summary-grid">
    <article class="panel summary-panel summary-panel-wide">
        <div class="summary-head">
            <h2>会社情報</h2>
        </div>
        <div class="summary-two-column">
            <dl class="summary-list compact-list summary-list-inline">
                <div><dt>会社</dt><dd>{{ $report['profile']['company_name'] !== '' ? $report['profile']['company_name'] : '-' }}</dd></div>
                <div><dt>店舗</dt><dd>{{ $report['profile']['store_name'] !== '' ? $report['profile']['store_name'] : '-' }}</dd></div>
                <div><dt>労働保険番号</dt><dd>{{ $report['profile']['labor_insurance_number'] !== '' ? $report['profile']['labor_insurance_number'] : '-' }}</dd></div>
            </dl>
            <dl class="summary-list compact-list summary-list-inline">
                <div><dt>会社郵便番号</dt><dd>{{ $report['profile']['company_post_num'] !== '' ? $report['profile']['company_post_num'] : '-' }}</dd></div>
                <div><dt>会社住所</dt><dd>{{ $report['profile']['company_address'] !== '' ? $report['profile']['company_address'] : '-' }}</dd></div>
                <div><dt>会社電話</dt><dd>{{ $report['profile']['company_tel'] !== '' ? $report['profile']['company_tel'] : '-' }}</dd></div>
            </dl>
        </div>
    </article>

    <article class="panel summary-panel summary-panel-narrow">
        <div class="summary-head">
            <h2>確認メモ</h2>
        </div>
        <dl class="summary-list compact-list">
            <div><dt>表示月数</dt><dd>{{ number_format((int) $report['filters']['months']) }}か月</dd></div>
            <div><dt>11日以上の月</dt><dd>{{ number_format((int) $report['totals']['eligible_by_days']) }}か月</dd></div>
            <div><dt>80時間以上のみの月</dt><dd>{{ number_format((int) $report['totals']['eligible_by_hours']) }}か月</dd></div>
            <div><dt>表示総額</dt><dd>{{ number_format((int) $report['totals']['gross_total']) }}</dd></div>
        </dl>
    </article>

    <article class="panel summary-panel summary-panel-wide summary-panel-full">
        <div class="summary-head">
            <h2>離職者情報</h2>
        </div>
        <div class="summary-two-column">
            <dl class="summary-list compact-list summary-list-inline">
                <div><dt>従業員コード</dt><dd>{{ $report['profile']['staff_id'] !== '' ? $report['profile']['staff_id'] : '-' }}</dd></div>
                <div><dt>フリガナ</dt><dd>{{ $report['profile']['staff_name_kana'] !== '' ? $report['profile']['staff_name_kana'] : '-' }}</dd></div>
                <div><dt>氏名</dt><dd>{{ $report['profile']['staff_name'] !== '' ? $report['profile']['staff_name'] : '-' }}</dd></div>
                <div><dt>マイナンバー</dt><dd>{{ $report['profile']['my_number'] !== '' ? $report['profile']['my_number'] : '-' }}</dd></div>
                <div><dt>入社日</dt><dd>{{ $report['profile']['join_date'] !== '' ? $report['profile']['join_date'] : '-' }}</dd></div>
                <div><dt>離職日</dt><dd>{{ $report['profile']['retire_date'] !== '' ? $report['profile']['retire_date'] : '-' }}</dd></div>
            </dl>
            <dl class="summary-list compact-list summary-list-inline">
                <div><dt>雇用区分</dt><dd>{{ $report['profile']['staff_division'] !== '' ? $report['profile']['staff_division'] : '-' }}</dd></div>
                <div><dt>雇用保険番号</dt><dd>{{ $report['profile']['koyou_num'] !== '' ? $report['profile']['koyou_num'] : '-' }}</dd></div>
                <div><dt>郵便番号</dt><dd>{{ $report['profile']['post_num'] !== '' ? $report['profile']['post_num'] : '-' }}</dd></div>
                <div><dt>住所</dt><dd>{{ $report['profile']['address'] !== '' ? $report['profile']['address'] : '-' }}</dd></div>
                <div><dt>自宅電話</dt><dd>{{ $report['profile']['home_tel'] !== '' ? $report['profile']['home_tel'] : '-' }}</dd></div>
                <div><dt>携帯番号</dt><dd>{{ $report['profile']['mobile_tel'] !== '' ? $report['profile']['mobile_tel'] : '-' }}</dd></div>
            </dl>
        </div>
    </article>
</section>
