<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 手当マスタ</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/allowance.css') }}"> -->

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d3dff0;
            padding: 6px;
            white-space: nowrap;
            text-align: center;
        }

        th {
            background: #f5f8fd;
        }

        .filter-form {
            margin-top: 0;
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .allowance-table-wrap {
            overflow: auto;
            border: 1px solid #d3dff0;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            margin-top: 10px;
        }

        .allowance-table-wrap table {
            margin-top: 0;
        }

        .filter-form input[type="text"],
        .filter-form input[type="number"],
        .filter-form select {
            box-sizing: border-box;
        }

        .filter-form select {
            min-width: 220px;
        }

        .chk {
            width: 20px;
            height: 20px;
        }

        .allow-row {
            cursor: pointer;
        }

        .allow-row:hover td {
            background: #f7fbff;
        }

        .allow-row.allow-active td {
            background: #e8f1ff;
        }

        .allow-row:focus-within td {
            background: #eef5ff;
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
            <div class="title">TCPG SYSTEM 手当マスタ</div>
        </div>
        <section class="panel">
            @if(session('status'))
            <p class="status">{{ session('status') }}</p>
            @endif

            <form method="get" class="filter-form">
                <label for="office_name">会社:</label>
                <select id="office_name" name="office_name">
                    <option value="">全社</option>
                    @foreach($companyOptions as $c)
                    <option value="{{ $c['company_id'] }}" @selected($selectedOfficeName===$c['company_id'])>
                        {{ $c['company_name'] }}
                    </option>
                    @endforeach
                </select>
                <button type="submit">表示</button>
            </form>

            <p class="meta-count">件数: {{ number_format($rowCount) }}</p>

            <div class="allowance-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>会社</th>
                            <th>表示順</th>
                            <th>AmountKey</th>
                            <th>手当名称</th>
                            <th>労保</th>
                            <th>社保</th>
                            <th>課税</th>
                            <th>固定賃金</th>
                            <th>割増基礎</th>
                            <th>控除基礎</th>
                            <th>更新</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr class="allow-row" data-allowance-no="{{ $row['allowance_no'] }}">
                            <form method="post" action="{{ route('admin.master.allowance.update') }}">
                                @csrf
                                <input type="hidden" name="allowance_no" value="{{ $row['allowance_no'] }}">
                                <input type="hidden" name="office_name_filter" value="{{ $selectedOfficeName }}">
                                <td>{{ $row['allowance_no'] }}</td>
                                <td>{{ $row['office_name'] }}</td>
                                <td><input type="number" name="display_order" min="1" max="999" value="{{ $row['display_order'] }}" style="width:72px"></td>
                                <td><input type="text" name="amount_column_key" value="{{ $row['amount_column_key'] }}"></td>
                                <td><input type="text" name="allowance_name" value="{{ $row['allowance_name'] }}"></td>
                                <td><input class="chk" type="checkbox" name="rou_target" value="1" @checked((int)$row['rou_target']===1)></td>
                                <td><input class="chk" type="checkbox" name="syaho_target" value="1" @checked((int)$row['syaho_target']===1)></td>
                                <td><input class="chk" type="checkbox" name="tax_target" value="1" @checked((int)$row['tax_target']===1)></td>
                                <td><input class="chk" type="checkbox" name="kotei_wage" value="1" @checked((int)$row['kotei_wage']===1)></td>
                                <td><input class="chk" type="checkbox" name="warimasi_kiso" value="1" @checked((int)$row['warimasi_kiso']===1)></td>
                                <td><input class="chk" type="checkbox" name="koujyo_kiso" value="1" @checked((int)$row['koujyo_kiso']===1)></td>
                                <td><button type="submit">更新</button></td>
                            </form>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12">データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    @include('admin_v2.master.allowance.page_script')
</body>

</html>
