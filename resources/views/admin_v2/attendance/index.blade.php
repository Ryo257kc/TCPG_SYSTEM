<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 勤怠管理</title>
    <style>
        body { margin: 0; font-family: "Segoe UI", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif; background: #edf3fc; color: #1f2937; }
        .wrap { width: min(1400px, 96vw); margin: 18px auto; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .title { font-size: 24px; font-weight: 800; color: #1f4f8f; }
        .panel { background: #fff; border: 1px solid #d3dff0; border-radius: 12px; padding: 12px; }
        .filters { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 10px; }
        input, select, button, .btn { border: 1px solid #d3dff0; border-radius: 8px; padding: 7px 10px; background: #fff; text-decoration: none; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border-bottom: 1px solid #e5ecf8; padding: 7px 8px; font-size: 13px; }
        th { background: #f8fbff; text-align: left; }
        td.num { text-align: right; }
        td.center { text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 勤怠管理</div>
        <div style="display:flex; gap:8px;">
            <a class="btn" href="{{ route('admin.payroll.index', ['month' => date('Y-m', strtotime($selectedMonth . '-01 +1 month')), 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">給与計算</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
        </div>
    </div>

    <section class="panel">
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

            <button type="submit">表示</button>
        </form>

        <div style="overflow:auto; border:1px solid #dce7f7; border-radius:10px;">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>名前</th>
                    <th>部署</th>
                    <th>店舗</th>
                    <th class="center">勤怠確認</th>
                    <th class="num">出勤日数</th>
                    <th class="num">欠勤日数</th>
                    <th class="num">休日出勤日数</th>
                    <th class="num">実働時間</th>
                    <th class="num">有休使用日数</th>
                    <th class="num">普通残業時間</th>
                    <th class="num">深夜残業時間</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    @php $m = (array) ($row['metrics'] ?? []); @endphp
                    <tr>
                        <td>{{ $row['staff_id'] }}</td>
                        <td>{{ $row['staff_name'] }}</td>
                        <td>{{ $row['division'] }}</td>
                        <td>{{ $row['store_name'] }}</td>
                        <td class="center">{{ !empty($m['attendance_checked']) ? '済' : '未' }}</td>
                        <td class="num">{{ number_format((float) ($m['work_in_num'] ?? 0), 2) }}</td>
                        <td class="num">{{ number_format((float) ($m['absence_num'] ?? 0), 2) }}</td>
                        <td class="num">{{ number_format((float) ($m['work_holiday_num'] ?? 0), 2) }}</td>
                        <td class="num">{{ number_format((float) ($m['work_time'] ?? 0), 2) }}</td>
                        <td class="num">{{ number_format((float) ($m['holiday_true'] ?? 0), 2) }}</td>
                        <td class="num">{{ number_format((float) ($m['overtime'] ?? 0), 2) }}</td>
                        <td class="num">{{ number_format((float) ($m['night_over_time'] ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="center" colspan="12">表示データがありません。</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>




