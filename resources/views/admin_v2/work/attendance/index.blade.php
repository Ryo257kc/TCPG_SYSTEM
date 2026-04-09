<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/attendance.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/daily_table_item.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 勤怠管理</div>
    </div>

    <section class="panel" id="attendance-main">
        <form method="GET" class="filters">
            <label for="month">対象月</label>
            <input id="month" name="month" type="month" value="{{ $selectedMonth }}">

            <label for="company_id">会社</label>
            <select id="company_id" name="company_id">
                <option value="">全社</option>
                @foreach ($companyOptions as $company)
                    <option value="{{ $company }}" @selected($selectedCompanyId === $company)>{{ $company }}</option>
                @endforeach
            </select>

            <label for="staff_id">名前</label>
            <select id="staff_id" name="staff_id">
                <option value=""></option>
                @foreach ($staffRows as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($selectedStaffId === $staff['staff_id'])>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary">表示</button>
            <button type="button" class="btn" id="shift-create-btn">シフト作成</button>
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
            <table>
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
                        $showOrBlank = static fn ($value, int $decimals = 2): string => ((float) $value) === 0.0 ? '' : number_format((float) $value, $decimals);
                    @endphp
                    <tr @class(['attendance-list-row', 'is-active' => $isDetail])>
                        <td><a class="btn btn-small" href="{{ route('admin.attendance.index', $toggleParams) }}">{!! $isDetail ? '一覧' : '日別' !!}</a></td>
                        <td>{{ $row['staff_id'] }}</td>
                        <td>{{ $row['staff_name'] }}</td>
                        <td>{{ $row['division'] }}</td>
                        <td>{{ $row['store_name'] }}</td>
                        <td class="center">
                            <button
                                @class([
                                    'btn',
                                    'btn-small',
                                    'attendance-confirm-toggle',
                                    'is-confirmed' => $isAttendanceChecked,
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
                        <td class="num">{{ number_format((float) ($m['work_in_num'] ?? 0), 2) }}</td>
                        <td class="num">{{ $showOrBlank($m['absence_num'] ?? 0) }}</td>
                        <td class="num">{{ $showOrBlank($m['work_holiday_num'] ?? 0) }}</td>
                        <td class="num">{{ $showOrBlank($m['holiday_true'] ?? 0) }}</td>
                        <td class="num">{{ number_format((float) ($m['work_time'] ?? 0), 2) }}</td>
                        <td class="num">{{ $showOrBlank($m['overtime'] ?? 0) }}</td>
                        <td class="num">{{ $showOrBlank($m['night_over_time'] ?? 0) }}</td>
                        <td class="num">{{ $showOrBlank($m['holiday_work_time'] ?? 0) }}</td>
                        <td class="num">{{ $showOrBlank($m['late_early_time'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td class="center" colspan="15">表示データがありません。</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin_v2.work.attendance.daily_table')
    </section>
</div>
@include('admin_v2.work.attendance.page_script')
</body>
</html>
