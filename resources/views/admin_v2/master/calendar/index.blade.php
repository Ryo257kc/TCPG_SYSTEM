<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カレンダーマスタ</title>
    <style>
        body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px;color:#1f2937}
        .card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px}
        .page{max-width:1440px;margin:18px auto}
        .btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
        .row{display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap}
        table{width:100%;border-collapse:collapse;margin-top:10px}
        th,td{border:1px solid #d3dff0;padding:6px;white-space:nowrap;text-align:center}
        th{background:#f5f8fd}
        input,select,button{padding:6px 8px;border:1px solid #d3dff0;border-radius:8px}
        button{background:#1f4f8f;color:#fff;border-color:#1f4f8f}
        .wrap{overflow:auto;max-height:70vh}
        .text-input{width:180px}
        .holiday-name{display:inline-block;min-width:140px;color:#4b5563}
        .value-text{display:inline-block;min-width:120px;color:#1f2937}
        .value-text.empty{color:#9aa4b2}
        .view-field{display:inline-flex;align-items:center;justify-content:center}
        .edit-field{display:none !important}
        tr.is-editing .view-field{display:none !important}
        tr.is-editing .edit-field{display:inline-flex !important;align-items:center;justify-content:center}
        tbody tr:hover td{background:#f7fbff}
        tbody tr:focus-within td{background:#eef5ff}
        .calendar-row.is-company-holiday-row td{background:#fff6eb}
        .calendar-row.is-company-holiday-row:hover td,
        .calendar-row.is-company-holiday-row:focus-within td{background:#fff1dd}
        .inline-actions{display:flex;gap:8px;justify-content:center}
        .btn-edit{background:#fff;color:#1f4f8f}
        .btn-cancel{background:#fff;color:#6b7280}
        .btn-delete{background:#fff;color:#b42318;border-color:#efc2bf}
        .add-form{margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .select-input{min-width:120px;background:#fff}
        .hint{color:#4b5563;font-size:13px}
    </style>
</head>
<body>
<div class="page">
<div class="card">
    <div class="row">
        <h2 style="margin:0;">カレンダーマスタ</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn" href="{{ route('admin.master.company') }}">会社マスタ</a>
            <a class="btn" href="{{ route('admin.master.staff') }}">スタッフマスタ</a>
            <a class="btn" href="{{ route('admin.master.store') }}">店舗マスタ</a>
            <a class="btn" href="{{ route('admin.master.allowance') }}">手当設定</a>
            <a class="btn" href="{{ route('admin.master.calendar') }}">カレンダー</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
        </div>
    </div>

    @if (session('status'))<p>{{ session('status') }}</p>@endif

    <div style="margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0;">
            <label for="year">対象年</label>
            <select id="year" name="year">
                @foreach ($yearOptions as $year)
                    <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                @endforeach
            </select>
            <button type="submit">表示</button>
        </form>

        <form method="post" action="{{ route('admin.master.calendar.import-public-holidays') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0;">
            @csrf
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <button type="submit">祝日を自動取得</button>
        </form>
    </div>

    <p class="hint">祝日名称と会社休日の名称を必要に応じて入力します。</p>
    <p>件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>

    <form method="post" action="{{ route('admin.master.calendar.update') }}" class="add-form">
        @csrf
        <input type="hidden" name="year" value="{{ $selectedYear }}">
        <input type="date" name="calendar_day" value="{{ $selectedYear }}-01-01" required>
        <input class="text-input" type="text" name="public_holiday" value="" placeholder="祝日名称">
        <select class="select-input" name="work_holiday">
            <option value="">未設定</option>
            <option value="祝日">祝日</option>
            <option value="休日" selected>休日</option>
        </select>
        <button type="submit">追加</button>
    </form>

    <div class="wrap">
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
                            <option value="祝日" @selected($row['work_holiday'] === '祝日')>祝日</option>
                            <option value="休日" @selected($row['work_holiday'] === '休日')>休日</option>
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
                <tr><td colspan="5">祝日・会社休日はまだありません</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<script>
document.querySelectorAll('.calendar-row').forEach((row) => {
    const editButton = row.querySelector('[data-action="edit"]');
    const cancelButton = row.querySelector('[data-action="cancel"]');
    const fields = row.querySelectorAll('[data-original]');

    if (editButton) {
        editButton.addEventListener('click', () => {
            row.classList.add('is-editing');
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', () => {
            fields.forEach((field) => {
                field.value = field.dataset.original ?? '';
            });
            row.classList.remove('is-editing');
        });
    }
});
</script>
</body>
</html>
