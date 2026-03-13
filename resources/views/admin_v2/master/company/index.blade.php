<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会社マスタ</title>
    <style>
        body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px;color:#1f2937}
        .card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px}
        .btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
        .row{display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap}
        table{width:100%;border-collapse:collapse;margin-top:10px}
        th,td{border:1px solid #d3dff0;padding:6px;white-space:nowrap;text-align:center}
        th{background:#f5f8fd}
        input,button{padding:6px 8px;border:1px solid #d3dff0;border-radius:8px}
        button{background:#1f4f8f;color:#fff;border-color:#1f4f8f}
        .wrap{overflow:auto}
    </style>
</head>
<body>
<div class="card">
    <div class="row">
        <h2 style="margin:0;">会社マスタ</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn" href="{{ route('admin.master.staff') }}">スタッフマスタ</a>
            <a class="btn" href="{{ route('admin.master.store') }}">店舗マスタ</a>
            <a class="btn" href="{{ route('admin.master.allowance') }}">手当設定</a>
            <a class="btn" href="{{ route('admin.master.calendar') }}">カレンダー</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
        </div>
    </div>

    @if (session('status'))<p>{{ session('status') }}</p>@endif

    <form method="get" style="margin-top:8px;display:flex;gap:8px;">
        <input type="text" name="q" value="{{ $keyword }}" placeholder="会社名/会社コード/事業所番号">
        <button type="submit">検索</button>
    </form>

    <p>件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>

    <div class="wrap">
        <table>
            <thead><tr><th>ID</th><th>Legacy</th><th>会社コード</th><th>会社名</th><th>カナ</th><th>住所</th><th>事業所番号</th><th>TEL</th><th>FAX</th><th>保存</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <form method="post" action="{{ route('admin.master.company.update') }}">
                        @csrf
                        <input type="hidden" name="q" value="{{ $keyword }}">
                        <input type="hidden" name="company_id" value="{{ $row['company_id'] }}">
                        <td>{{ $row['company_id'] }}</td>
                        <td>{{ $row['legacy_store_no'] }}</td>
                        <td><input type="text" name="company_code" value="{{ $row['company_code'] }}"></td>
                        <td><input type="text" name="company_name" value="{{ $row['company_name'] }}" required></td>
                        <td><input type="text" name="company_name_kana" value="{{ $row['company_name_kana'] }}"></td>
                        <td><input type="text" name="company_address" value="{{ $row['company_address'] }}"></td>
                        <td><input type="text" name="office_number" value="{{ $row['office_number'] }}"></td>
                        <td><input type="text" name="phone" value="{{ $row['phone'] }}"></td>
                        <td><input type="text" name="fax" value="{{ $row['fax'] }}"></td>
                        <td><button type="submit">更新</button></td>
                    </form>
                </tr>
            @empty
                <tr><td colspan="10">データがありません</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
