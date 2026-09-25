<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 部門マスタ</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">

    <style>
        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d3dff0;
            padding: 6px;
            white-space: normal;
            word-break: break-word;
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

        .department-table-wrap {
            overflow: auto;
            border: 1px solid #d3dff0;
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            margin-top: 10px;
        }

        .department-table-wrap table {
            margin-top: 0;
        }

        .filter-form input[type="text"] {
            box-sizing: border-box;
        }

        .chk {
            width: 20px;
            height: 20px;
        }

        .meta-count {
            margin: 6px 0 0;
            color: #5b708f;
            font-size: 12px;
            line-height: 1.2;
        }

        .note {
            margin: 6px 0 0;
            color: #8ca0ba;
            font-size: 12px;
        }

        td input[type="text"],
        td input[type="date"] {
            width: 100%;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')
    <div class="wrap">
        @include('shared.status_message')
        <div class="top">
            <div class="title">TCPG SYSTEM 部門マスタ</div>
        </div>
        <section class="panel">

            <form method="get" class="filter-form">
                <label for="q">検索:</label>
                <input type="text" id="q" name="q" value="{{ $keyword }}" placeholder="部門No・店舗略称・部門名・店舗コード">
                <button type="submit">表示</button>
            </form>

            <p class="meta-count">件数: {{ number_format($rowCount) }}</p>
            <p class="note">店舗略称(store_short_name)は仕訳データが直接参照しているキーのため編集できません。</p>

            <div class="department-table-wrap">
                <table class="f_size12">
                    <colgroup>
                        <col style="width: 40px;">
                        <col style="width: 90px;">
                        <col style="width: 120px;">
                        <col style="width: 60px;">
                        <col style="width: 120px;">
                        <col style="width: 120px;">
                        <col style="width: 70px;">
                        <col style="width: 50px;">
                        <col style="width: 110px;">
                        <col style="width: 100px;">
                        <col style="width: 40px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>部門No</th>
                            <th>店舗略称</th>
                            <th>部門名</th>
                            <th>紐づく店舗コード</th>
                            <th>店舗名(参考)</th>
                            <th>会社(参考)</th>
                            <th>レセ店舗No</th>
                            <th>レセ有</th>
                            <th>通帳選択</th>
                            <th>閉店日</th>
                            <th>更新</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <form method="post" action="{{ route('admin.master.department.update') }}">
                                @csrf
                                <input type="hidden" name="department_no" value="{{ $row['department_no'] }}">
                                <input type="hidden" name="q" value="{{ $keyword }}">
                                <td>{{ $row['department_no'] }}</td>
                                <td>{{ $row['store_short_name'] }}</td>
                                <td><input type="text" name="store_category" value="{{ $row['store_category'] }}"></td>
                                <td><input type="text" name="official_store_no" value="{{ $row['official_store_no'] }}"></td>
                                <td>{{ $row['store_name'] }}</td>
                                <td>{{ $row['company_name'] }}</td>
                                <td><input type="text" name="receipt_store_no" value="{{ $row['receipt_store_no'] }}"></td>
                                <td><input class="chk" type="checkbox" name="has_receipt" value="1" @checked($row['has_receipt']===1)></td>
                                <td><input type="text" name="bank_account_selection" value="{{ $row['bank_account_selection'] }}"></td>
                                <td><input type="date" name="closed_on" value="{{ $row['closed_on'] }}"></td>
                                <td><button type="submit">更新</button></td>
                            </form>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11">データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>

</html>
