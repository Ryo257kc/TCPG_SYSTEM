<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 離職票</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/report.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/rishoku.css') }}"> -->
    <style>
        .rishoku-filter-bar {
            margin-bottom: 8px;
        }

        .rishoku-filter-grid {
            display: grid;
            grid-template-columns:
                minmax(150px, 250px) minmax(180px, 500px) minmax(120px, 150px) auto;
            gap: 8px;
        }

        .rishoku-filter-grid-compact {
            align-items: end;
        }

        .rishoku-filter-grid .field {
            display: grid;
            gap: 4px;
        }

        .rishoku-filter-grid .field span {
            font-size: 11px;
            color: var(--muted);
            font-weight: 600;
        }

        .rishoku-filter-grid .field-inline {
            display: grid;
            grid-template-columns: 56px minmax(0, 1fr);
            gap: 8px;
            align-items: center;
        }

        .rishoku-filter-grid .field-inline span {
            margin: 0;
            white-space: nowrap;
        }

        .rishoku-filter-grid .field-inline-wide {
            grid-template-columns: 56px minmax(0, 1fr);
        }

        .rishoku-filter-grid select {
            min-height: 34px;
            width: 100%;
            box-sizing: border-box;
            min-width: 0;
        }

        .rishoku-filter-grid .field {
            min-width: 0;
        }

        .rishoku-filter-grid .field-inline>*:last-child {
            min-width: 0;
        }

        .field-staff select {
            max-width: 100%;
        }

        .field-months select {
            max-width: 100%;
        }

        .rishoku-summary-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(220px, 0.55fr);
            gap: 12px;
            margin-bottom: 12px;
            align-items: start;
        }

        .summary-panel {
            padding: 14px;
        }

        .summary-panel-full {
            grid-column: 1 / -1;
        }

        .summary-head h2 {
            margin: 0;
            color: var(--primary);
            font-size: 17px;
        }

        .summary-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 14px;
            margin: 12px 0 0;
        }

        .summary-list div {
            display: grid;
            gap: 2px;
        }

        .summary-list dt {
            color: var(--muted);
            font-size: 12px;
        }

        .summary-list dd {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: var(--primary);
        }

        .compact-list dd {
            font-size: 15px;
        }

        .summary-list-inline {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .summary-list-inline div {
            grid-template-columns: 104px minmax(0, 1fr);
            align-items: baseline;
            gap: 10px;
        }

        .summary-list-inline dt {
            font-size: 12px;
            white-space: nowrap;
        }

        .summary-list-inline dd {
            font-size: 15px;
            min-width: 0;
        }

        .summary-two-column {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 22px;
            margin-top: 12px;
        }

        .summary-two-column .summary-list {
            margin-top: 0;
        }

        .table-panel {
            padding: 10px 12px 12px;
        }

        .table-head {
            margin-bottom: 8px;
        }

        .table-head h2 {
            margin: 0;
            color: var(--primary);
            font-size: 17px;
        }

        .table-head p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .rishoku-table {
            /* min-width: 1000px; */
            border-collapse: collapse;
            font-size: 14px;
        }

        /* .rishoku-table .col-no { width: 42px; }
.rishoku-table .col-k8-period { width: 118px; }
.rishoku-table .col-k8-days { width: 58px; }
.rishoku-table .col-payment { width: 84px; }
.rishoku-table .col-k10-period { width: 106px; }
.rishoku-table .col-k11-days { width: 58px; }
.rishoku-table .col-work-days { width: 56px; }
.rishoku-table .col-absence { width: 56px; }
.rishoku-table .col-paid-leave { width: 56px; }
.rishoku-table .col-hours { width: 60px; }
.rishoku-table .col-wage-a { width: 76px; }
.rishoku-table .col-wage-b { width: 76px; }
.rishoku-table .col-gross { width: 76px; }
.rishoku-table .col-eligibility { width: 74px; } */
        .rishoku-table thead th {
            font-size: 12px;
            padding: 4px 4px;
        }

        .rishoku-table td,
        .rishoku-table tbody th {
            padding: 3px 4px;
            /* font-size: 11px;  */
        }

        .rishoku-table .head-no,
        .rishoku-table .head-label {
            display: block;
        }

        .rishoku-table .head-no {
            font-size: 10px;
            line-height: 1.05;
            margin-bottom: 1px;
        }

        .rishoku-table .head-label {
            line-height: 1.1;
        }

        .rishoku-table th,
        .rishoku-table td {
            font-variant-numeric: tabular-nums;
        }

        .rishoku-table tbody th {
            background: #fbfcfe;
            text-align: center;
            white-space: nowrap;
        }

        .rishoku-table td {
            line-height: 1.2;
            white-space: nowrap;
            border: 1px solid #333;
        }

        .rishoku-table td.num {
            text-align: right;
        }

        .rishoku-table .eligibility-cell {
            text-align: center;
        }

        .status-tag {
            display: inline-flex;
            align-items: center;
            min-height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-tag.is-ok {
            background: #e8f7eb;
            color: #2e7d32;
        }

        .status-tag.is-sub {
            background: #fff3db;
            color: #9a5a00;
        }

        .status-tag.is-muted {
            background: #eef1f5;
            color: #637589;
        }

        .is-empty-row td,
        .is-empty-row th {
            background: #fffdf8;
        }

        .is-before-join-row td,
        .is-before-join-row th {
            background: #dfe4ea;
            color: #596577;
        }

        @media (max-width: 760px) {
            .rishoku-summary-grid {
                grid-template-columns: 1fr;
            }

            .summary-panel-full {
                grid-column: auto;
            }

            .summary-two-column {
                grid-template-columns: 1fr;
            }

            .rishoku-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
            <div class="title">TCPG SYSTEM 離職票</div>
        </div>

        <section class="panel report-main-panel">
            <section class="rishoku-filter-bar">
                <form method="GET" class="report-filter-grid rishoku-filter-grid rishoku-filter-grid-compact">
                    <label class="field field-inline">
                        <span>会社</span>
                        <select name="company">
                            <option value="">全社</option>
                            @foreach ($companyOptions as $company)
                            <option value="{{ $company }}" @selected($selectedCompany===$company)>{{ $company }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field field-staff field-inline field-inline-wide">
                        <span>退職者</span>
                        <select name="staff">
                            <option value="">選択してください</option>
                            @foreach ($staffOptions as $staffOption)
                            <option value="{{ $staffOption['staff_id'] }}" @selected($selectedStaffId===$staffOption['staff_id'])>{{ $staffOption['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field field-months field-inline">
                        <span>表示月数</span>
                        <select name="months">
                            @foreach ([24, 36, 48] as $months)
                            <option value="{{ $months }}" @selected($selectedMonths===$months)>{{ $months }}か月</option>
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
                    <div class="summary-head">
                        <h2>会社情報</h2>
                    </div>
                    <div class="summary-two-column">
                        <dl class="summary-list compact-list summary-list-inline">
                            <div>
                                <dt>会社</dt>
                                <dd>{{ $report['profile']['company_name'] !== '' ? $report['profile']['company_name'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>店舗</dt>
                                <dd>{{ $report['profile']['store_name'] !== '' ? $report['profile']['store_name'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>労働保険番号</dt>
                                <dd>{{ $report['profile']['labor_insurance_number'] !== '' ? $report['profile']['labor_insurance_number'] : '-' }}</dd>
                            </div>
                        </dl>
                        <dl class="summary-list compact-list summary-list-inline">
                            <div>
                                <dt>会社郵便番号</dt>
                                <dd>{{ $report['profile']['company_post_num'] !== '' ? $report['profile']['company_post_num'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>会社住所</dt>
                                <dd>{{ $report['profile']['company_address'] !== '' ? $report['profile']['company_address'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>会社電話</dt>
                                <dd>{{ $report['profile']['company_tel'] !== '' ? $report['profile']['company_tel'] : '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </article>

                <article class="panel summary-panel summary-panel-narrow">
                    <div class="summary-head">
                        <h2>確認メモ</h2>
                    </div>
                    <dl class="summary-list compact-list">
                        <div>
                            <dt>表示月数</dt>
                            <dd>{{ number_format((int) $report['filters']['months']) }}か月</dd>
                        </div>
                        <div>
                            <dt>11日以上の月</dt>
                            <dd>{{ number_format((int) $report['totals']['eligible_by_days']) }}か月</dd>
                        </div>
                        <div>
                            <dt>80時間以上のみの月</dt>
                            <dd>{{ number_format((int) $report['totals']['eligible_by_hours']) }}か月</dd>
                        </div>
                        <div>
                            <dt>表示総額</dt>
                            <dd>{{ number_format((int) $report['totals']['gross_total']) }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="panel summary-panel summary-panel-wide summary-panel-full">
                    <div class="summary-head">
                        <h2>離職者情報</h2>
                    </div>
                    <div class="summary-two-column">
                        <dl class="summary-list compact-list summary-list-inline">
                            <div>
                                <dt>従業員コード</dt>
                                <dd>{{ $report['profile']['staff_id'] !== '' ? $report['profile']['staff_id'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>フリガナ</dt>
                                <dd>{{ $report['profile']['staff_name_kana'] !== '' ? $report['profile']['staff_name_kana'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>氏名</dt>
                                <dd>{{ $report['profile']['staff_name'] !== '' ? $report['profile']['staff_name'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>マイナンバー</dt>
                                <dd>{{ $report['profile']['my_number'] !== '' ? $report['profile']['my_number'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>入社日</dt>
                                <dd>{{ $report['profile']['join_date'] !== '' ? $report['profile']['join_date'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>離職日</dt>
                                <dd>{{ $report['profile']['retire_date'] !== '' ? $report['profile']['retire_date'] : '-' }}</dd>
                            </div>
                        </dl>
                        <dl class="summary-list compact-list summary-list-inline">
                            <div>
                                <dt>雇用区分</dt>
                                <dd>{{ $report['profile']['staff_division'] !== '' ? $report['profile']['staff_division'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>雇用保険番号</dt>
                                <dd>{{ $report['profile']['koyou_num'] !== '' ? $report['profile']['koyou_num'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>郵便番号</dt>
                                <dd>{{ $report['profile']['post_num'] !== '' ? $report['profile']['post_num'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>住所</dt>
                                <dd>{{ $report['profile']['address'] !== '' ? $report['profile']['address'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>自宅電話</dt>
                                <dd>{{ $report['profile']['home_tel'] !== '' ? $report['profile']['home_tel'] : '-' }}</dd>
                            </div>
                            <div>
                                <dt>携帯番号</dt>
                                <dd>{{ $report['profile']['mobile_tel'] !== '' ? $report['profile']['mobile_tel'] : '-' }}</dd>
                            </div>
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
                            <col style="width: 30px;">
                            <col style="width: 120px;">
                            <col style="width: 70px;">
                            <col style="width: 100px;">
                            <col style="width: 120px;">
                            <col style="width: 60px;">
                            <col style="width: 60px;">
                            <col style="width: 60px;">
                            <col style="width: 60px;">
                            <col style="width: 60px;">
                            <col style="width: 90px;">
                            <col style="width: 90px;">
                            <col style="width: 90px;">
                            <col style="width: 60px;">
                            <!-- <col class="col-no">
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
                            <col class="col-eligibility"> -->
                        </colgroup>
                        <thead>
                            <tr>
                                <th></th>
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
                                <!-- <td class="num">{{ $showInt($row['wage_a_amount']) }}</td> -->
                                <td class="num">{{ $row['wage_a_amount'] }}</td>
                                <!-- <td class="num">{{ $showInt($row['wage_b_amount']) }}</td> -->
                                <td class="num">{{ $row['wage_b_amount'] }}</td>
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
