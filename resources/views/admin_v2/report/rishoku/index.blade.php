<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 離職票確認</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/report.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/rishoku.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
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
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 離職票確認</div>
    </div>

    <section class="panel report-main-panel">
        <section class="rishoku-filter-bar">
            <form method="GET" class="report-filter-grid rishoku-filter-grid rishoku-filter-grid-compact">
                <label class="field field-inline">
                    <span>会社</span>
                    <select name="company">
                        <option value="">全社</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company }}" @selected($selectedCompany === $company)>{{ $company }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field field-staff field-inline field-inline-wide">
                    <span>退職者</span>
                    <select name="staff">
                        <option value="">選択してください</option>
                        @foreach ($staffOptions as $staffOption)
                            <option value="{{ $staffOption['staff_id'] }}" @selected($selectedStaffId === $staffOption['staff_id'])>{{ $staffOption['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field field-months field-inline">
                    <span>表示月数</span>
                    <select name="months">
                        @foreach ([24, 36, 48] as $months)
                            <option value="{{ $months }}" @selected($selectedMonths === $months)>{{ $months }}か月</option>
                        @endforeach
                    </select>
                </label>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">表示</button>
                </div>
            </form>
        </section>

        <section class="rishoku-summary-grid">
            <article class="panel summary-panel summary-panel-wide">
                <div class="summary-head"><h2>会社情報</h2></div>
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
                <div class="summary-head"><h2>確認メモ</h2></div>
                <dl class="summary-list compact-list">
                    <div><dt>表示月数</dt><dd>{{ number_format((int) $report['filters']['months']) }}か月</dd></div>
                    <div><dt>11日以上の月</dt><dd>{{ number_format((int) $report['totals']['eligible_by_days']) }}か月</dd></div>
                    <div><dt>80時間以上のみの月</dt><dd>{{ number_format((int) $report['totals']['eligible_by_hours']) }}か月</dd></div>
                    <div><dt>表示総額</dt><dd>{{ number_format((int) $report['totals']['gross_total']) }}</dd></div>
                </dl>
            </article>

            <article class="panel summary-panel summary-panel-wide summary-panel-full">
                <div class="summary-head"><h2>離職者情報</h2></div>
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

        <section class="panel table-panel">
            <div class="table-head">
                <h2>離職の日以前の賃金支払状況等</h2>
                <p>申請画面に合わせて、(8)(9)(10)(11)(12) の並びで確認できます。</p>
            </div>
            <div class="table-wrap">
                <table class="report-table rishoku-table">
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
                            <td colspan="14" class="empty-cell">会社と退職者を選択すると、離職票確認画面の一覧を表示します。</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>
</body>
</html>
