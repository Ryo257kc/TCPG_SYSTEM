@php
    $showNum = static function ($value): string {
        if ($value === null || $value === '') {
            return '';
        }
        $number = (float) $value;
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    };

    $showInt = static function ($value): string {
        if ($value === null || $value === '') {
            return '';
        }
        return number_format((int) $value);
    };
@endphp

<section class="panel table-panel">
    <div class="table-head">
        <h2>離職の日以前の賃金支払状況等</h2>
        <p>申請済み離職票に合わせて、(8)(10)(11)(12) の並びで確認できます。</p>
    </div>
    <div class="table-wrap">
        <table class="report-table rishoku-table rishoku-table-v6">
            <colgroup>
                <col class="col-no">
                <col class="col-k8-period">
                <col class="col-k8-days">
                <col class="col-payment">
                <col class="col-k10-period">
                <col class="col-k11-days">
                <col class="col-work-days">
                <col class="col-absence">
                <col class="col-paid-leave">
                <col class="col-hours">
                <col class="col-wage-a">
                <col class="col-wage-b">
                <col class="col-gross">
                <col class="col-eligibility">
            </colgroup>
            <thead>
            <tr>
                <th>#</th>
                <th><span class="head-no">(8)</span><span class="head-label">被保険者期間</span><span class="head-label">算定対象期間</span></th>
                <th><span class="head-no">(9)</span><span class="head-label">賃金支払</span><span class="head-label">基礎日数</span></th>
                <th>支給日</th>
                <th><span class="head-no">(10)</span><span class="head-label">賃金支払</span><span class="head-label">対象期間</span></th>
                <th><span class="head-no">(11)</span><span class="head-label">基礎日数</span></th>
                <th>出勤日数</th>
                <th>欠勤日数</th>
                <th>有休日数</th>
                <th>労働時間</th>
                <th><span class="head-no">(12)</span><span class="head-label">賃金</span><span class="head-label">(A)</span></th>
                <th><span class="head-no">(12)</span><span class="head-label">賃金</span><span class="head-label">(B)</span></th>
                <th>計</th>
                <th>成立目安</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($report['rows'] as $row)
                <tr class="{{ $row['has_payroll'] ? '' : 'is-empty-row' }} {{ !empty($row['before_join']) ? 'is-before-join-row' : '' }}">
                    <th>{{ $row['row_no'] }}</th>
                    <td>{{ $row['separation_period'] }}</td>
                    <td class="num">{{ $showInt($row['payment_basis_days']) }}</td>
                    <td>{{ $row['payment_date'] }}</td>
                    <td>{{ $row['wage_period'] }}</td>
                    <td class="num">{{ $showNum($row['basis_days']) }}</td>
                    <td class="num">{{ $showNum($row['work_days']) }}</td>
                    <td class="num">{{ $showNum($row['absence_days']) }}</td>
                    <td class="num">{{ $showNum($row['paid_leave_days']) }}</td>
                    <td class="num">{{ $showNum($row['work_hours']) }}</td>
                    <td class="num">{{ $showInt($row['wage_a_amount']) }}</td>
                    <td class="num">{{ $showInt($row['wage_b_amount']) }}</td>
                    <td class="num">{{ $showInt($row['gross_amount']) }}</td>
                    <td class="eligibility-cell">
                        @if (!empty($row['before_join']))
                            <span class="status-tag is-muted">在籍前</span>
                        @elseif ($row['eligible_by_days'])
                            <span class="status-tag is-ok">11日以上</span>
                        @elseif ($row['eligible_by_hours'])
                            <span class="status-tag is-sub">80時間以上</span>
                        @else
                            <span class="status-tag is-muted">未成立</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="empty-cell">会社と離職者を選択すると、離職票確認用の一覧を表示します。</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
