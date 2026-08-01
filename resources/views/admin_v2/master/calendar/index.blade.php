<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - カレンダーマスタ</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/calendar.css') }}"> -->

    <style>
        .calendar-controls {
            justify-content: space-between;
            margin-top: 0;
        }

        .filter-form-inline {
            margin: 0;
        }

        .add-form {
            margin-top: 0;
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .calendar-table-wrap {
            overflow: auto;
            max-height: 70vh;
            border: 1px solid #d3dff0;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            margin-top: 10px;
        }

        .calendar-table-wrap table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .calendar-table-wrap th,
        .calendar-table-wrap td {
            border: 1px solid #d3dff0;
            padding: 6px;
            white-space: nowrap;
            text-align: center;
        }

        .calendar-table-wrap th {
            background: #f5f8fd;
        }

        .text-input {
            width: 180px;
        }

        .select-input {
            min-width: 120px;
            background: #fff;
        }

        .value-text {
            display: inline-block;
            min-width: 120px;
            color: #1f2937;
        }

        .value-text.empty {
            color: #9aa4b2;
        }

        .view-field {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .edit-field {
            display: none !important;
        }

        tr.is-editing .view-field {
            display: none !important;
        }

        tr.is-editing .edit-field {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }

        tbody tr:hover td {
            background: #f7fbff;
        }

        tbody tr:focus-within td {
            background: #eef5ff;
        }

        .calendar-row.is-company-holiday-row td {
            background: #fff6eb;
        }

        .calendar-row.is-company-holiday-row:hover td,
        .calendar-row.is-company-holiday-row:focus-within td {
            background: #fff1dd;
        }

        .inline-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-edit {
            background: #fff;
            color: #1f4f8f;
        }

        .btn-cancel {
            background: #fff;
            color: #6b7280;
        }

        .btn-delete {
            background: #fff;
            color: #b42318;
            border-color: #efc2bf;
        }

        .meta-count {
            margin: 6px 0 0;
            color: #5b708f;
            font-size: 12px;
            line-height: 1.2;
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')
    <div class="wrap">
        <div class="top">
            <div class="title">TCPG SYSTEM カレンダーマスタ</div>
        </div>
        <section class="panel">
            @if (session('status'))
            <p>{{ session('status') }}</p>
            @endif

            <div class="filter-form calendar-controls">
                <form method="get" class="filter-form filter-form-inline">
                    <label for="year">対象年</label>
                    <select id="year" name="year">
                        @foreach ($yearOptions as $year)
                        <option value="{{ $year }}" @selected($selectedYear===$year)>{{ $year }}</option>
                        @endforeach
                    </select>
                    <button type="submit">表示</button>
                </form>

                <form method="post" action="{{ route('admin.master.calendar.import-public-holidays') }}" class="filter-form filter-form-inline">
                    @csrf
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <button type="submit">祝日を一括取込</button>
                </form>
            </div>

            <p class="meta-count">件数: {{ number_format($rowCount) }}</p>

            <form method="post" action="{{ route('admin.master.calendar.update') }}" class="add-form">
                @csrf
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="date" name="calendar_day" value="{{ $selectedYear }}-01-01" required>
                <input class="text-input" type="text" name="public_holiday" value="" placeholder="祝日名称">
                <select class="select-input" name="work_holiday">
                    <option value="">未設定</option>
                    <option value="祝日">祝日</option>
                    <option value="会社休">会社休</option>
                </select>
                <button type="submit">追加</button>
            </form>

            <div class="calendar-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>曜日</th>
                            <th>祝日名称</th>
                            <th>会社休日</th>
                            <th>更新</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        @php
                        $hasCompanyHoliday = $row['work_holiday'] !== '';
                        $isCompanyOnlyHoliday = $hasCompanyHoliday && $row['work_holiday'] !== '祝日';
                        $formId = 'calendar-update-' . str_replace('-', '', $row['calendar_day']);
                        $deleteFormId = 'calendar-delete-' . str_replace('-', '', $row['calendar_day']);
                        @endphp
                        <tr class="calendar-row {{ $isCompanyOnlyHoliday ? 'is-company-holiday-row' : '' }}">
                            <td>{{ $row['date_label'] }}</td>
                            <td>{{ $row['weekday_label'] }}</td>
                            <td class="public-holiday-cell">
                                <span class="view-field value-text {{ $row['public_holiday'] === '' ? 'empty' : '' }}">{{ $row['public_holiday'] !== '' ? $row['public_holiday'] : '-' }}</span>
                                <input class="edit-field text-input" type="text" name="public_holiday" value="{{ $row['public_holiday'] }}" placeholder="祝日名称" data-original="{{ $row['public_holiday'] }}" form="{{ $formId }}">
                            </td>
                            <td class="company-holiday-cell">
                                <span class="view-field value-text {{ $row['work_holiday'] === '' ? 'empty' : '' }}">{{ $row['work_holiday'] !== '' ? $row['work_holiday'] : '-' }}</span>
                                <select class="edit-field select-input" name="work_holiday" data-original="{{ $row['work_holiday'] }}" form="{{ $formId }}">
                                    <option value="">未設定</option>
                                    <option value="祝日" @selected($row['work_holiday']==='祝日' )>祝日</option>
                                    <option value="会社休" @selected($row['work_holiday']==='会社休' )>会社休</option>
                                </select>
                            </td>
                            <td>
                                <div class="inline-actions view-field">
                                    <button class="btn-edit" type="button" data-action="edit">編集</button>
                                    <button class="btn-delete" type="submit" form="{{ $deleteFormId }}">削除</button>
                                </div>
                                <div class="inline-actions edit-field">
                                    <button type="submit" form="{{ $formId }}">保存</button>
                                    <button class="btn-cancel" type="button" data-action="cancel">キャンセル</button>
                                </div>
                                <form id="{{ $formId }}" method="post" action="{{ route('admin.master.calendar.update') }}">
                                    @csrf
                                    <input type="hidden" name="calendar_day" value="{{ $row['calendar_day'] }}">
                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                </form>
                                <form id="{{ $deleteFormId }}" method="post" action="{{ route('admin.master.calendar.delete') }}">
                                    @csrf
                                    <input type="hidden" name="calendar_day" value="{{ $row['calendar_day'] }}">
                                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">祝日・会社休日はまだありません</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    @include('admin_v2.master.calendar.page_script')
</body>

</html>
