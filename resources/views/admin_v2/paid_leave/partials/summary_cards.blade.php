<section class="summary-grid">
    <div class="panel summary-panel">
        <div class="summary-head">基本情報</div>
        <dl class="kv-list">
            <div><dt>staff_id</dt><dd>{{ $summary['staff_id'] !== '' ? $summary['staff_id'] : '-' }}</dd></div>
            <div><dt>氏名</dt><dd>{{ $summary['staff_name'] ?: '-' }}</dd></div>
            <div><dt>入社年月日</dt><dd>{{ $summary['join_date'] ?: '-' }}</dd></div>
            <div><dt>初回加算日</dt><dd>{{ $summary['first_grant_date'] ?: '-' }}</dd></div>
        </dl>
    </div>
    <div class="panel summary-panel">
        <div class="summary-head">集計</div>
        <dl class="kv-list">
            <div><dt>有給残日数</dt><dd>{{ $summary['remaining_days'] ?? '-' }}</dd></div>
            <div><dt>消滅日数</dt><dd>{{ $summary['extinguish_days'] ?? '-' }}</dd></div>
            <div><dt>次回加算年月</dt><dd>{{ $summary['next_grant_date'] ?: '-' }}</dd></div>
            <div><dt>勤続年数</dt><dd>{{ $summary['service_years_label'] ?: '-' }}</dd></div>
            <div><dt>次年加算日数</dt><dd>{{ $summary['next_grant_days'] ?? '-' }}</dd></div>
        </dl>
    </div>
</section>
