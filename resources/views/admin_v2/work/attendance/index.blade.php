<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">

    <style>
        /* body {
            margin: 0;
            font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
            background: #edf3fc;
            color: #1f2937;
        }

        .wrap {
            width: min(1440px, 96vw);
            margin: 6px auto 18px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .title {
            font-size: 24px;
            font-weight: 800;
            color: #1f4f8f;
        }

        .panel {
            background: #fff;
            border: 1px solid #d3dff0;
            border-radius: 12px;
            padding: 12px;
        } */

        .filters {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        /* table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        } */

        /* th,
        td {
            border-bottom: 1px solid #e5ecf8;
            padding: 5px 5px;
            font-size: 13px;
        }

        th {
            background: #f8fbff;
            text-align: left;
        } */

        /* th.num {
            text-align: right;
        }

        th.center {
            text-align: center;
        }

        td.num {
            text-align: right;
        }

        td.center {
            text-align: center;
        } */
        select {
            height: 28px;
            min-width: 20px;
        }

        .attendance-list-toolbar {
            display: flex;
            justify-content: flex-end;
            margin: 0 0 8px;
        }

        .attendance-list-row.is-active td {
            background: #eef5ff;
        }

        .attendance-confirm-toggle {
            min-width: 68px;
        }

        .attendance-confirm-toggle.is-confirmed {
            background: #edf8ef;
            border-color: #b9d8c0;
            color: #256b37;
        }

        .attendance-confirm-toggle.is-unconfirmed {
            background: #fff4e8;
            border-color: #efcfac;
            color: #9a4d00;
        }



        /*
        .daily-panel {
            background: #fff;
            border: 1px solid #d3dff0;
            border-radius: 12px;
            padding: 10px;
        } */

        .daily-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        /* .daily-title {
            font-size: 16px;
            font-weight: 800;
            color: #1f4f8f;
        } */

        .daily-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .daily-summary-group {
            border: 1px solid #dce7f7;
            border-radius: 10px;
            padding: 6px 9px;
            background: #f8fbff;
        }

        .daily-summary-title {
            font-size: 13px;
            color: #1f4f8f;
            font-weight: 800;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .daily-summary-row {
            display: grid;
            grid-template-columns: 100px minmax(0, 1fr) 50px minmax(0, 1fr);
            gap: 4px 10px;
            align-items: center;
        }

        .daily-summary-label {
            font-size: 12px;
            color: #53627a;
            font-weight: 700;
            line-height: 1.2;
        }

        .daily-summary-value {
            font-size: 16px;
            color: #1f4f8f;
            font-weight: 800;
        }

        .daily-statuses {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .daily-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border: 1px solid #c8d7eb;
            border-radius: 999px;
            background: #f6f9fd;
            color: #53627a;
            font-size: 12px;
            font-weight: 700;
        }

        .daily-status-badge.is-on {
            border-color: #b9d8c0;
            background: #edf8ef;
            color: #256b37;
        }

        .daily-status-badge.is-off {
            border-color: #fb9325;
            background: #f8f1ed;
            color: #256b37;
        }

        .daily-empty {
            border: 1px dashed #b7cae8;
            border-radius: 10px;
            background: #f8fbff;
            padding: 12px;
        }

        .daily-empty-title {
            font-size: 15px;
            font-weight: 800;
            color: #1f4f8f;
            margin-bottom: 6px;
        }

        .daily-empty-text {
            margin: 4px 0;
            font-size: 12px;
            line-height: 1.6;
            color: #42556f;
        }

        .create-inline {
            margin-bottom: 10px;
            border: 1px solid #dce7f7;
            border-radius: 12px;
            background: #f8fbff;
            padding: 10px;
        }

        .create-inline-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .create-inline-title {
            font-size: 16px;
            font-weight: 800;
            color: #1f4f8f;
        }

        .create-inline-actions,
        .create-inline-row {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .create-inline-note {
            font-size: 12px;
            color: #53627a;
            margin-bottom: 8px;
        }

        .create-inline-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 8px;
        }

        .create-inline-item {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            padding: 10px;
            border: 1px solid #d3dff0;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
        }

        .create-inline-item.existing {
            background: #eef2f7;
            color: #6b7280;
        }

        .create-inline-item-main {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .create-inline-item-title {
            font-weight: 700;
            color: #1f2937;
        }

        .create-inline-item.existing .create-inline-item-title {
            color: #4b5563;
        }

        .create-inline-item-sub {
            font-size: 12px;
            color: #53627a;
        }

        .create-inline-empty {
            font-size: 13px;
            color: #53627a;
            padding: 8px 0;
        }

        .action-cell {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
            max-width: 100%;
        }

        /* .word {
            width: 100%;
            table-layout: fixed;
        } */

        .action-cell button,
        .action-cell a {
            white-space: nowrap;
            flex-shrink: 0;
        }

        td.action-cell {
            width: 40px;
            word-break: break-word;

        }

        @media (max-width:980px) {
            .daily-summary {
                grid-template-columns: 1fr;
            }

            .daily-summary-row {
                grid-template-columns: 140px 1fr;
            }
        }

        /* .daily-wrap,
        .table-wrap {
            overflow: auto;
            border: 1px solid #dce7f7;
            border-radius: 10px;
        }

        .daily-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1280px;
            background: #fff;
        }

        .daily-table th,
        .daily-table td,
        .data-table th,
        .data-table td {
            border-bottom: 1px solid #e5ecf8;
            border-left: 1px solid #e5ecf8;
            padding: 1px 3px;
            font-size: 12px;
            white-space: nowrap;
        }

        .daily-table th:first-child,
        .daily-table td:first-child,
        .data-table th:first-child,
        .data-table td:first-child {
            border-left: 0;
        }

        .daily-table th,
        .data-table th {
            background: #f8fbff;
            text-align: center;
        }

        .daily-table td,
        .data-table td {
            background: #fff;
        }

        .daily-table .daily-work-store-col,
        .data-table .daily-work-store-col {
            width: 42px;
            min-width: 42px;
            white-space: normal;
            line-height: 1.1;
            word-break: break-all;
        }

        .daily-table .daily-work-store-col .daily-view,
        .data-table .daily-work-store-col .daily-view {
            justify-content: flex-start;
            align-items: flex-start;
            min-height: auto;
        }

        .daily-table tr.daily-row-muted td,
        .data-table tr.daily-row-muted td {
            background: #f1f4f8;
            color: #6b7280;
        }

        .daily-table td.daily-value-alert,
        .data-table td.daily-value-alert {
            background: #fdecec;
            color: #b71c1c;
            font-weight: 700;
            box-shadow: inset 0 0 0 1px #f3b4b4;
        } */

        .daily-note-cell {
            width: 68px;
            max-width: 68px;
            position: relative;
            vertical-align: top;
            overflow: visible;
        }

        .daily-note-text {
            display: inline-block;
            max-width: 68px;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: bottom;
            white-space: nowrap;
        }

        .daily-note-cell .daily-view {
            justify-content: flex-start;
        }

        .daily-view {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 20px;
        }

        .daily-edit {
            display: none !important;
        }

        .data-table tr.is-editing .daily-view {
            display: none !important;
        }

        .data-table tr.is-editing .daily-edit:not(.daily-note-textarea) {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }

        .data-table td.daily-value-alert,
        .data-table td.daily-value-alert {
            background: #fdecec;
            color: #b71c1c;
            font-weight: 700;
            box-shadow: inset 0 0 0 1px #f3b4b4;
        }

        .data-table td.daily-value-under {
            background: #eaf3ff;
            color: #1558a8;
            font-weight: 700;
            box-shadow: inset 0 0 0 1px #9fc5f8;
        }

        .daily-edit-input {
            width: 38px;
            max-width: 100%;
            padding: 1px 3px;
            /* height: 22px; */
            line-height: 1.1;
            box-sizing: border-box;
            text-align: center;
            font-size: 12px;
        }

        .row-holiday {
            background-color: #f2f2f2;
            /* 薄いグレー */
            color: #999;
            /* background-color: #f7f7f7;
            color: #666; */
        }

        /* .daily-edit-input-num {
            width: 42px;
        } */

        /* .daily-edit-input-category {
            width: 28px;
        } */

        .daily-edit-input-store {
            width: 60px;
            max-width: 100%;
            padding: 1px 1px;
        }

        .daily-note-textarea {
            display: none;
            width: 100px;
            min-height: 54px;
            padding: 4px 6px;
            resize: vertical;
            box-sizing: border-box;
            font: inherit;
            line-height: 1.3;
            white-space: normal;
            position: absolute;
            top: 0;
            right: 0;
            z-index: 10;
            background: #fff;
            border: 1px solid #b8cbe8;
            border-radius: 6px;
            box-shadow: 0 6px 18px rgba(31, 79, 143, 0.14);
            text-align: left;
        }

        .daily-note-cell.note-editing .daily-note-textarea {
            display: block !important;
        }

        .daily-note-cell.note-editing .daily-note-text {
            display: none !important;
        }

        .daily-actions {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .daily-btn-muted {
            background: #fff;
            color: #53627a;
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')
    <div class="wrap">
        <div class="top">
            <div class="title">TCPG SYSTEM 勤怠管理</div>
        </div>
        @if (session('status'))
        <div class="status">
            {{ session('status') }}
        </div>
        @endif
        <section class="panel" id="attendance-main">
            <form method="GET" class="filters">
                <label for="month">対象月</label>
                <input id="month" name="month" type="month" value="{{ $selectedMonth }}">

                <label for="company_id">会社</label>
                <select id="company_id" name="company_id">
                    <option value="">全社</option>
                    @foreach ($companyOptions as $company)
                    <option value="{{ $company }}" @selected($selectedCompanyId===$company)>{{ $company }}</option>
                    @endforeach
                </select>

                <label for="staff_id">名前</label>
                <select id="staff_id" name="staff_id">
                    <option value=""></option>
                    @foreach ($staffRows as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($selectedStaffId===$staff['staff_id'])>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary">表示</button>
                <button type="button" class="btn" id="shift-create-btn">シフト作成</button>
                @if ($payrollLinkPaymentDate !== '')
                <a class="btn" href="{{ route('admin.payroll.index', ['payment_date' => $payrollLinkPaymentDate, 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">給与計算へ</a>
                @else
                <span class="btn" style="opacity:.45; cursor:not-allowed;" title="対応する給与データがありません">給与計算へ</span>
                @endif
            </form>

            <div class="create-inline" id="attendance-shift-inline" hidden>
                <div class="create-inline-head">
                    <div class="create-inline-title">月次シフト作成</div>
                    <div class="create-inline-actions">
                        <button type="button" class="btn" id="attendance-shift-select-all-btn">全選択</button>
                        <button type="button" class="btn" id="attendance-shift-clear-btn">解除</button>
                        <button type="button" class="btn" id="attendance-shift-close-btn">閉じる</button>
                    </div>
                </div>
                <div class="create-inline-body">
                    <div class="create-inline-row">
                        <button type="button" class="btn btn-primary" id="attendance-shift-create-submit-btn">作成</button>
                        <button type="button" class="btn" id="attendance-shift-delete-submit-btn">削除</button>
                    </div>
                    <div class="create-inline-note">開くと自動で対象者一覧を表示します。作成は未作成のみ、削除は打刻実績が無い作成済のみ対象です。</div>
                    <div class="create-inline-list" id="attendance-shift-list"></div>
                    <div class="create-inline-empty" id="attendance-shift-empty" hidden>対象者はありません。</div>
                </div>
            </div>

            @php
            $baseParams = ['month' => $selectedMonth, 'company_id' => $selectedCompanyId];
            @endphp
            <div class="attendance-list-toolbar">
                <form method="post" action="{{ route('admin.attendance.reflect') }}">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    <button class="btn" type="submit">勤怠一括反映</button>
                </form>
            </div>
            <div style="overflow:auto; border:1px solid #dce7f7; border-radius:10px; margin-bottom:10px;">
                <table class="data-table f_size12">
                    <thead>
                        <tr>
                            <th>詳細</th>
                            <th>ID</th>
                            <th>名前</th>
                            <th>部署</th>
                            <th>店舗</th>
                            <th class="center">勤怠確定</th>
                            <th class="num">出勤日数</th>
                            <th class="num">欠勤日数</th>
                            <th class="num">休出日数</th>
                            <th class="num">有休日数</th>
                            <th class="num">実働時間</th>
                            <th class="num">残業</th>
                            <th class="num">深夜</th>
                            <th class="num">休出時間</th>
                            <th class="num">遅早時間</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        @php
                        $m = (array) ($row['metrics'] ?? []);
                        $isDetail = $selectedStaffId === $row['staff_id'];
                        $toggleParams = $baseParams + ['staff_id' => $isDetail ? '' : $row['staff_id']];
                        $confirmFormId = 'attendance-confirm-' . $row['staff_id'];
                        $isAttendanceChecked = !empty($m['attendance_checked']);
                        $showOrBlank = function ($value) {
                        if ($value == 0 || $value === null || $value === '') {
                        return '';
                        }

                        $num = (float) $value;

                        if (abs($num - round($num)) < 0.00001) {
                            return (string) (int) round($num);
                            }

                            return rtrim(rtrim(number_format($num, 2, '.' , '' ), '0' ), '.' );
                            };
                            @endphp
                            <tr @class(['attendance-list-row', 'is-active'=> $isDetail])>

                            <td><a class="btn btn-small" href="{{ route('admin.attendance.index', $toggleParams) }}">{!! $isDetail ? '一覧' : '日別' !!}</a></td>
                            <td>{{ $row['staff_id'] }}</td>
                            <td>{{ $row['staff_name'] }}</td>
                            <td>{{ $row['division'] }}</td>
                            <td>{{ $row['store_name'] }}</td>
                            <td class="center">
                                <button
                                    @class([ 'btn' , 'btn-small' , 'attendance-confirm-toggle' , 'is-confirmed'=> $isAttendanceChecked,
                                    'is-unconfirmed' => !$isAttendanceChecked,
                                    ])
                                    type="submit"
                                    form="{{ $confirmFormId }}"
                                    >{!! $isAttendanceChecked ? '確定済' : '未確定' !!}</button>
                                <form id="{{ $confirmFormId }}" method="post" action="{{ route('admin.attendance.confirm') }}">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                                    <input type="hidden" name="staff_id" value="{{ $row['staff_id'] }}">
                                    <input type="hidden" name="checked" value="{{ $isAttendanceChecked ? 0 : 1 }}">
                                </form>
                            </td>
                            <td class="num">{{ number_format((float) ($m['work_days'] ?? 0), 0) }}</td>
                            <td class="num">{{ $showOrBlank($m['absence_days'] ?? 0) }}</td>
                            <td class="num">{{ $showOrBlank($m['holiday_work_days'] ?? 0) }}</td>
                            <td class="num">{{ $showOrBlank($m['paid_leave_days'] ?? 0) }}</td>
                            <td class="num">{{ number_format((float) ($m['change_scheduled_total'] ?? 0), 2) }}</td>
                            <td class="num">{{ $showOrBlank($m['overtime_total'] ?? 0) }}</td>
                            <td class="num">{{ $showOrBlank($m['night_overtime_total'] ?? 0) }}</td>
                            <td class="num">{{ $showOrBlank(($m['category_totals']['休出'] ?? 0)) }}</td>
                            <td class="num">
                                {{ $showOrBlank(
        ($m['category_totals']['遅刻'] ?? 0)
        + ($m['category_totals']['早退'] ?? 0)
        + ($m['category_totals']['遅早'] ?? 0)
    ) }}
                            </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="center" colspan="15">表示データがありません。</td>
                            </tr>
                            @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin_v2.work.attendance.daily_table')
            <div>
                <a href="{{ route('admin.dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </div>
    @include('admin_v2.work.attendance.page_script')
</body>

</html>
