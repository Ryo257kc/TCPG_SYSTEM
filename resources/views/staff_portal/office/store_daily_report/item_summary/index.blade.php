<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 項目別集計</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .item-summary-filter {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-start;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">項目別集計</h2>
            </div>

            <form method="get" action="{{ route('office.store_daily_report.item_summary') }}" class="filter-row item-summary-filter">
                <label>
                    日付
                    <input type="month" name="target_month" value="{{ $targetMonth }}">
                </label>


                <button type="submit" class="btn">表示</button>
            </form>

            <div class="table-wrap">
                <table class="data-table f_size13">
                    <colgroup>
                        <col style="width: 40px;">
                        <col style="width: 20px;">
                        <col style="width: 50px;">
                        <col style="width: 30px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 60px;">
                        <col style="width: 35px;">
                        <col style="width: 35px;">
                        <col style="width: 35px;">
                        <col style="width: 35px;">
                        <col style="width: 60px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>日別順</th>
                            <th>患者名</th>
                            <th>保険証</th>
                            <th>回収日</th>
                            <th>項目</th>
                            <th>メニュー</th>
                            <th>自費</th>
                            <th>保険負担</th>
                            <th>請求金額</th>
                            <th>レセ差額</th>
                            <th>店舗</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($itemSummaryRows ?? []) as $row)
                        <tr>
                            <td>{{ $row['日付'] }}</td>
                            <td>{{ $row['日別順'] }}</td>
                            <td>{{ $row['患者名'] }}</td>
                            <td>{{ $row['保険証'] }}</td>
                            <td>{{ $row['回収日'] }}</td>
                            <td>{{ $row['項目'] }}</td>
                            <td>{{ $row['メニュー'] }}</td>
                            <td class="text-right">{{ $row['自費'] }}</td>
                            <td class="text-right">{{ $row['保険負担'] }}</td>
                            <td class="text-right">{{ $row['請求金額'] }}</td>
                            <td class="text-right">{{ $row['レセ差額'] }}</td>
                            <td>{{ $row['店舗'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                <a href="{{ route('office.store_daily_report') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
