<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 手当項目設定</title>
    <style>
        body{font-family:"Segoe UI","Hiragino Kaku Gothic ProN",Meiryo,sans-serif;background:#ecf2fb;margin:0;padding:18px;color:#1f2937}
        .card{background:#fff;border:1px solid #d3dff0;border-radius:14px;padding:16px;box-shadow:0 10px 24px rgba(20,61,113,.08)}
        h1{margin:0 0 10px;font-size:22px;color:#1f4f8f}.top{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between}
        .btn{display:inline-flex;padding:8px 12px;border:1px solid #b7ccef;border-radius:10px;background:#e7effc;color:#1f4f8f;text-decoration:none;font-weight:700}
        .meta{color:#667085;font-size:13px;margin:8px 0}
        .ok{margin:8px 0;padding:8px 10px;border:1px solid #9dd7b5;background:#eafaf0;border-radius:10px;color:#1b6b3c}
        .warn{margin:8px 0;padding:8px 10px;border:1px solid #f0b8b8;background:#fff1f1;border-radius:10px;color:#9b1c1c}
        .filter{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:10px 0}
        select{padding:6px 8px;border:1px solid #d3dff0;border-radius:8px}
        table{width:100%;border-collapse:collapse;background:#fff}
        th,td{border:1px solid #d3dff0;padding:5px 4px;white-space:nowrap;text-align:center}
        th{background:#f5f8fd}
        tbody tr:nth-child(even){background:#fafcff}
        tbody tr:hover{background:#eef5ff}
        tbody tr:focus-within{background:#e4f0ff}
        .wrap{overflow:auto}
        input[type=text],input[type=number]{width:140px;padding:5px 6px;border:1px solid #d3dff0;border-radius:8px}
        input[type=number]{width:64px}
        input[type=checkbox]{transform:scale(1.1)}
        button{padding:6px 10px;border:1px solid #1f4f8f;border-radius:8px;background:#1f4f8f;color:#fff;cursor:pointer}
        tbody tr:nth-child(even) .save-col{background:#fafcff}
        tbody tr:hover .save-col{background:#eef5ff}
        tbody tr:focus-within .save-col{background:#e4f0ff}
        tbody tr:focus-within .save-col button{box-shadow:0 0 0 2px rgba(31,79,143,.22)}
        .save-col{position:sticky;right:0;background:#fff;z-index:2}
        th.save-col{background:#f5f8fd;z-index:3}
    </style>
</head>
<body>
<div class="card">
    <div class="top">
        <h1>手当項目設定</h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn" href="{{ route('admin.master.company') }}">会社マスタ</a>
            <a class="btn" href="{{ route('admin.master.staff') }}">スタッフマスタ</a>
            <a class="btn" href="{{ route('admin.master.store') }}">店舗マスタ</a>
            <a class="btn" href="{{ route('admin.dashboard') }}">管理ダッシュボード</a>
        </div>
    </div>

    @if (session('status'))
        <div class="ok">{{ session('status') }}</div>
    @endif
    @if (!empty($duplicateAmountKeys))
        <div class="warn">
            AmountKey 重複あり:
            @foreach ($duplicateAmountKeys as $dup)
                <span style="margin-right:10px;">[{{ $dup['office_name'] }}] {{ $dup['amount_column_key'] }} x{{ $dup['count'] }}</span>
            @endforeach
        </div>
    @endif

    <form method="get" class="filter">
        <label for="office_name">会社で絞り込み:</label>
        <select id="office_name" name="office_name">
            <option value="">全会社</option>
            @foreach ($companyOptions as $company)
                <option value="{{ $company['company_code'] }}" @selected($selectedOfficeName === $company['company_code'])>
                    {{ $company['company_code'] }} {{ $company['company_name'] }}
                </option>
            @endforeach
        </select>
        <button type="submit">表示</button>
    </form>
    @if ($selectedOfficeName !== '')
        <form method="post" action="{{ route('admin.master.allowance.ensure-slots') }}" class="filter" style="margin-top:-2px;">
            @csrf
            <input type="hidden" name="office_name_filter" value="{{ $selectedOfficeName }}">
            <button type="submit" onclick="return confirm('選択会社の未登録の支給項目を手当設定へ追加します。実行しますか？');">支給項目を補完</button>
        </form>
    @endif

    <p class="meta">件数: {{ number_format($rowCount) }} / ソース: {{ $source }}</p>
    <p class="meta">※ AmountKey（amount_column_key）は必須です。支給項目の紐づけに使用します。</p>

    @php
        $companyNameByCode = [];
        foreach ($companyOptions as $company) {
            $companyNameByCode[(string)($company['company_code'] ?? '')] = (string)($company['company_name'] ?? '');
        }
    @endphp

    <div class="wrap">
        <table>
            <thead>
            <tr>
                <th>No</th>
                <th>表示順</th>
                <th>AmountKey</th>
                <th>手当名称</th>
                <th>労保対象</th>
                <th>社保対象</th>
                <th>課税対象</th>
                <th>固定賃金</th>
                <th>割増基礎</th>
                <th>控除基礎</th>
                <th class="save-col">保存</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <form method="post" action="{{ route('admin.master.allowance.update') }}">
                        @csrf
                        <input type="hidden" name="allowance_no" value="{{ $row['allowance_no'] }}">
                        <input type="hidden" name="office_name_filter" value="{{ $selectedOfficeName }}">
                        <td>{{ $row['display_no'] }}</td>
                        <td><input type="number" min="1" max="999" name="display_order" value="{{ $row['display_order'] }}"></td>
                        <td><input type="text" name="amount_column_key" value="{{ $row['amount_column_key'] }}" placeholder="allowance_amo_3"></td>
                        <td><input type="text" name="allowance_name" value="{{ $row['allowance_name'] }}"></td>
                        <td><input type="checkbox" name="rou_target" value="1" @checked($row['rou_target'] === 1)></td>
                        <td><input type="checkbox" name="syaho_target" value="1" @checked($row['syaho_target'] === 1)></td>
                        <td><input type="checkbox" name="tax_target" value="1" @checked($row['tax_target'] === 1)></td>
                        <td><input type="checkbox" name="kotei_wage" value="1" @checked($row['kotei_wage'] === 1)></td>
                        <td><input type="checkbox" name="warimasi_kiso" value="1" @checked($row['warimasi_kiso'] === 1)></td>
                        <td><input type="checkbox" name="koujyo_kiso" value="1" @checked($row['koujyo_kiso'] === 1)></td>
                        <td class="save-col"><button type="submit">保存</button></td>
                    </form>
                </tr>
            @empty
                <tr><td colspan="11">データがありません</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
