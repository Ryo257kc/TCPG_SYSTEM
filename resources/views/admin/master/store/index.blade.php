<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 店舗マスタ</title>
    <style>
        body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px;color:#1f2937}
        .card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px;box-shadow:0 10px 24px rgba(20,61,113,.08)}
        h1{margin:0 0 10px;font-size:22px;color:#1f4f8f}.top{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between}
        .btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
        .filter{display:flex;gap:8px;margin:10px 0;flex-wrap:wrap}
        input,button,select{padding:8px 10px;border:1px solid #d3dff0;border-radius:10px}
        button{background:#1f4f8f;color:#fff;border-color:#1f4f8f;cursor:pointer}
        .meta{color:#667085;font-size:13px;margin:8px 0}
        table{width:100%;border-collapse:collapse;background:#fff}
        th,td{border:1px solid #d3dff0;padding:7px;white-space:nowrap;text-align:center;vertical-align:middle}
        th{background:#f5f8fd}
        .wrap{overflow:auto}
        .txt{width:120px}
        .txt-wide{width:180px}
        .sel{min-width:160px}
        .status{margin:8px 0;padding:8px 10px;border-radius:10px;background:#ecfdf3;color:#027a48;border:1px solid #a6f4c5}
        .error{margin:8px 0;padding:8px 10px;border-radius:10px;background:#fef3f2;color:#b42318;border:1px solid #fecdca}
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
            <a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
        </div>
    </div>

    @if(session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    <form method="get" class="filter">
        <input type="text" name="q" value="{{ $keyword }}" placeholder="店舗コード / 店舗名 / 社名">
        <button type="submit">検索</button>
    </form>

    <p class="meta">件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>

    <div class="wrap">
        <table>
            <thead>
            <tr>
                <th>店舗コード</th>
                <th>店舗名</th>
                <th>会社</th>
                <th>短縮名</th>
                <th>電話</th>
                <th>閉店</th>
                <th>Legacy No</th>
                <th>保存</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <form method="post" action="{{ route('admin.master.store.update') }}">
                        @csrf
                        <input type="hidden" name="store_code" value="{{ $row['store_code'] }}">
                        <input type="hidden" name="q" value="{{ $keyword }}">
                        <td>{{ $row['store_code'] }}</td>
                        <td><input class="txt-wide" type="text" name="store_name" value="{{ $row['store_name'] }}"></td>
                        <td>
                            <select class="sel" name="company_id">
                                <option value="">(未設定)</option>
                                @foreach($companyOptions as $company)
                                    <option value="{{ $company['company_id'] }}" @selected($company['company_id'] === $row['company_id'])>{{ $company['company_name'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input class="txt" type="text" name="store_short_name" value="{{ $row['store_short_name'] }}"></td>
                        <td><input class="txt" type="text" name="phone" value="{{ $row['phone'] }}"></td>
                        <td>
                            <select name="is_closed">
                                <option value="0" @selected((int)$row['is_closed'] === 0)>0</option>
                                <option value="1" @selected((int)$row['is_closed'] === 1)>1</option>
                            </select>
                        </td>
                        <td>{{ $row['legacy_no'] }}</td>
                        <td><button type="submit">保存</button></td>
                    </form>
                </tr>
            @empty
                <tr><td colspan="8">データがありません</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
