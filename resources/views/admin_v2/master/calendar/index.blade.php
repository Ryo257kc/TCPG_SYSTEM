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
        .public-holiday-cell.is-company-holiday input{background:#fff6eb;border-color:#e8c89f}
        tbody tr:hover td{background:#f7fbff}
        tbody tr:focus-within td{background:#eef5ff}
        .company-holiday-cell.is-company-holiday input{background:#fff6eb;border-color:#e8c89f}
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

    <form method="post" action="{{ route('admin.master.calendar.update') }}" style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        @csrf
        <input type="hidden" name="year" value="{{ $selectedYear }}">
        <input type="date" name="calendar_day" value="{{ $selectedYear }}-01-01" required>
        <input class="text-input" type="text" name="public_holiday" value="" placeholder="祝日名称">
        <input class="text-input" type="text" name="work_holiday" value="休日" placeholder="会社休日">
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
                @endphp
                <tr>
                    <form method="post" action="{{ route('admin.master.calendar.update') }}">
                        @csrf
                        <input type="hidden" name="calendar_day" value="{{ $row['calendar_day'] }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <td>{{ $row['date_label'] }}</td>
                        <td>{{ $row['weekday_label'] }}</td>
                        <td class="public-holiday-cell {{ $isCompanyOnlyHoliday ? 'is-company-holiday' : '' }}"><input class="text-input" type="text" name="public_holiday" value="{{ $row['public_holiday'] }}" placeholder="祝日名称"></td>
                        <td class="company-holiday-cell {{ $isCompanyOnlyHoliday ? 'is-company-holiday' : '' }}"><input class="text-input" type="text" name="work_holiday" value="{{ $row['work_holiday'] }}" placeholder="休日"></td>
                        <td style="display:flex;gap:8px;justify-content:center;">
                            <button type="submit">更新</button>
                    </form>
                    <form method="post" action="{{ route('admin.master.calendar.delete') }}">
                        @csrf
                        <input type="hidden" name="calendar_day" value="{{ $row['calendar_day'] }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <button type="submit">削除</button>
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
</body>
</html>
