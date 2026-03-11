<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - Company Master</title>
    <style>
        body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px;color:#1f2937}
        .card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px;box-shadow:0 10px 24px rgba(20,61,113,.08)}
        h1{margin:0 0 10px;font-size:22px;color:#1f4f8f}
        .top{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between}
        .btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
        .filter{display:flex;gap:8px;margin:10px 0;flex-wrap:wrap}
        input,button{padding:8px 10px;border:1px solid #d3dff0;border-radius:10px}
        button{background:#1f4f8f;color:#fff;border-color:#1f4f8f;cursor:pointer}
        .meta{color:#667085;font-size:13px;margin:8px 0}
        .ok{margin:8px 0;color:#065f46;background:#ecfdf3;border:1px solid #a7f3d0;border-radius:8px;padding:8px 10px}
        table{width:100%;border-collapse:collapse;background:#fff}
        th,td{border:1px solid #d3dff0;padding:7px;white-space:nowrap;text-align:center}
        th{background:#f5f8fd}
        .wrap{overflow:auto}
        .txt{width:100%;min-width:120px;box-sizing:border-box;text-align:left;background:#fff;color:#111827}
        .txt.num{text-align:center;min-width:80px}
        .save{padding:6px 10px;border:1px solid #1f4f8f;border-radius:8px;background:#1f4f8f;color:#fff;font-weight:700}
    </style>
</head>
<body>
<div class="card">
    <div class="top">
        <h1>会社マスタ</h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn" href="{{ route('admin.master.staff') }}">スタッフマスタ</a>
            <a class="btn" href="{{ route('admin.master.store') }}">店舗マスタ</a>
            <a class="btn" href="{{ route('admin.master.allowance') }}">手当項目設定</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">管理ダッシュボード</a>
        </div>
    </div>

    @if (session('status'))
        <div class="ok">{{ session('status') }}</div>
    @endif

    <form method="get" class="filter">
        <input type="text" name="q" value="{{ $keyword }}" placeholder="会社コード / 会社名 / 事業所番号">
        <button type="submit">検索</button>
    </form>

    <p class="meta">件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>

    <div class="wrap">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Legacy</th>
                <th>会社コード</th>
                <th>会社名</th>
                <th>カナ</th>
                <th>住所</th>
                <th>事業所番号</th>
                <th>TEL</th>
                <th>FAX</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <form method="post" action="{{ route('admin.master.company.update') }}">
                        @csrf
                        <input type="hidden" name="q" value="{{ $keyword }}">
                        <input type="hidden" name="company_id" value="{{ $row['company_id'] }}">
                        <td>{{ $row['company_id'] }}</td>
                        <td>{{ $row['legacy_store_no'] }}</td>
                        <td>
                            @if(($editable['company_code'] ?? false) === true)
                                <input class="txt" type="text" name="company_code" value="{{ $row['company_code'] }}">
                            @else
                                {{ $row['company_code'] }}
                            @endif
                        </td>
                        <td><input class="txt" type="text" name="company_name" value="{{ $row['company_name'] }}" required></td>
                        <td>
                            @if(($editable['company_name_kana'] ?? false) === true)
                                <input class="txt" type="text" name="company_name_kana" value="{{ $row['company_name_kana'] }}">
                            @else
                                {{ $row['company_name_kana'] }}
                            @endif
                        </td>
                        <td>
                            @if(($editable['company_address'] ?? false) === true)
                                <input class="txt" type="text" name="company_address" value="{{ $row['company_address'] }}">
                            @else
                                {{ $row['company_address'] }}
                            @endif
                        </td>
                        <td>
                            @if(($editable['office_number'] ?? false) === true)
                                <input class="txt num" type="text" name="office_number" value="{{ $row['office_number'] }}">
                            @else
                                {{ $row['office_number'] }}
                            @endif
                        </td>
                        <td>
                            @if(($editable['phone'] ?? false) === true)
                                <input class="txt num" type="text" name="phone" value="{{ $row['phone'] }}">
                            @else
                                {{ $row['phone'] }}
                            @endif
                        </td>
                        <td>
                            @if(($editable['fax'] ?? false) === true)
                                <input class="txt num" type="text" name="fax" value="{{ $row['fax'] }}">
                            @else
                                {{ $row['fax'] }}
                            @endif
                        </td>
                        <td><button class="save" type="submit">保存</button></td>
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