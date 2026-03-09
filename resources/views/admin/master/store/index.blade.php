<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - Store Master</title>
    <style>
        body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px;color:#1f2937}
        .card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px;box-shadow:0 10px 24px rgba(20,61,113,.08)}
        h1{margin:0 0 10px;font-size:22px;color:#1f4f8f}.top{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between}
        .btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
        .filter{display:flex;gap:8px;margin:10px 0;flex-wrap:wrap}
        input,button{padding:8px 10px;border:1px solid #d3dff0;border-radius:10px}
        button{background:#1f4f8f;color:#fff;border-color:#1f4f8f}
        .meta{color:#667085;font-size:13px;margin:8px 0}
        table{width:100%;border-collapse:collapse;background:#fff}
        th,td{border:1px solid #d3dff0;padding:7px;white-space:nowrap;text-align:center}
        th{background:#f5f8fd}
        .wrap{overflow:auto}
    </style>
</head>
<body>
<div class="card">
    <div class="top">
        <h1>店舗マスタ</h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn" href="{{ route('admin.master.company') }}">会社マスタ</a>
            <a class="btn" href="{{ route('admin.master.staff') }}">スタッフマスタ</a>
            <a class="btn" href="{{ route('admin.master.allowance') }}">手当項目設定</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">管理ダッシュボード</a>
        </div>
    </div>

    <form method="get" class="filter">
        <input type="text" name="q" value="{{ $keyword }}" placeholder="店舗コード / 店舗名 / 社名">
        <button type="submit">検索</button>
    </form>

    <p class="meta">件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>

    <div class="wrap">
        <table>
            <thead>
            <tr>
                <th>店舗コード</th><th>店舗名</th><th>社名</th><th>略称</th><th>電話</th><th>閉店</th><th>Legacy No</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['store_code'] }}</td>
                    <td>{{ $row['store_name'] }}</td>
                    <td>{{ $row['company_name'] }}</td>
                    <td>{{ $row['store_short_name'] }}</td>
                    <td>{{ $row['phone'] }}</td>
                    <td>{{ (int)$row['is_closed'] === 1 ? '1' : '0' }}</td>
                    <td>{{ $row['legacy_no'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7">データがありません</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
